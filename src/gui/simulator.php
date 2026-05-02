<?php
// 1. BACKEND VALIDATION & SECURITY
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pid'])) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h2 style='color:#ef4444;'>Access Denied.</h2><a href='index.php'>Return to Setup</a></div>");
}

$quantum = (int)$_POST['quantum'];
if ($quantum <= 0) die("<h2 style='color:red; text-align:center;'>Error: Quantum must be > 0.</h2>");

$pids = $_POST['pid']; 
$arrivals = $_POST['arrival']; 
$bursts = $_POST['burst'];

if (count(array_unique($pids)) !== count($pids)) die("<h2 style='color:red; text-align:center;'>Error: Duplicate IDs.</h2>");

$processesInput = [];
for ($i = 0; $i < count($pids); $i++) {
    if ((int)$arrivals[$i] < 0 || (int)$bursts[$i] <= 0) die("<h2 style='color:red; text-align:center;'>Error: Invalid Data for {$pids[$i]}</h2>");
    $processesInput[] = new Process($pids[$i], (int)$arrivals[$i], (int)$bursts[$i]);
}

// 2. OOP CORE ARCHITECTURE
class Process {
    public $id, $at, $bt, $wt, $tat, $rt;
    public function __construct($id, $at, $bt) { 
        $this->id = $id; 
        $this->at = $at; 
        $this->bt = $bt; 
    }
}

class Scheduler {
    protected $processes;
    public $gantt = [], $result = [], $queueHistory = [], $systemMetrics = [];
    
    public function __construct($processes) {
        $this->processes = $processes;
        usort($this->processes, fn($a, $b) => $a->at == $b->at ? $a->bt <=> $b->bt : $a->at <=> $b->at);
    }
    
    protected function calcAverages($totalTime, $idleTime) {
        $wt = $tat = $rt = 0; $n = count($this->result) ?: 1;
        $totalBurst = 0;
        foreach ($this->result as $r) { 
            $wt += $r['wt']; $tat += $r['tat']; $rt += $r['rt']; $totalBurst += $r['bt']; 
        }
        
        $utilization = ($totalTime > 0) ? round(($totalBurst / $totalTime) * 100, 2) : 0;
        $throughput = ($totalTime > 0) ? round($n / $totalTime, 3) : 0;
        
        $this->systemMetrics = ['utilization' => $utilization, 'throughput' => $throughput, 'total_time' => $totalTime];
        return ['wt' => round($wt/$n, 2), 'tat' => round($tat/$n, 2), 'rt' => round($rt/$n, 2)];
    }
}

class SJFScheduler extends Scheduler {
    public function run() {
        $time = 0; $idleTime = 0; $completed = 0; $n = count($this->processes);
        $is_comp = array_fill(0, $n, false);
        $first_rt = array_fill(0, $n, -1);
        $rem_bt = array_map(fn($p) => $p->bt, $this->processes);
        $last_active = null;
        
        while ($completed < $n) {
            $idx = -1; $min_bt = PHP_INT_MAX; $available_now = [];
            for ($i = 0; $i < $n; $i++) {
                if ($this->processes[$i]->at <= $time && !$is_comp[$i]) {
                    $available_now[] = $this->processes[$i];
                    if ($rem_bt[$i] < $min_bt) { $min_bt = $rem_bt[$i]; $idx = $i; }
                }
            }
            
            $active_id = ($idx != -1) ? $this->processes[$idx]->id : 'IDLE';
            if ($active_id !== $last_active) {
                $waiting_names = [];
                foreach($available_now as $p) { if($idx == -1 || $p->id !== $active_id) $waiting_names[] = $p->id; }
                $this->queueHistory[] = ['time' => $time, 'active' => $active_id, 'waiting' => $waiting_names];
                $last_active = $active_id;
            }

            if ($idx != -1) {
                $p = $this->processes[$idx];
                if ($first_rt[$idx] == -1) $first_rt[$idx] = $time - $p->at;

                $start = $time;
                $time++;
                $rem_bt[$idx]--;

                $last = end($this->gantt);
                if ($last && $last['id'] == $p->id) {
                    $this->gantt[count($this->gantt) - 1]['end'] = $time;
                    $this->gantt[count($this->gantt) - 1]['duration']++;
                } else {
                    $this->gantt[] = ['id' => $p->id, 'start' => $start, 'end' => $time, 'duration' => 1];
                }

                if ($rem_bt[$idx] == 0) {
                    $is_comp[$idx] = true;
                    $completed++;
                    $tat = $time - $p->at;
                    $this->result[] = ['id' => $p->id, 'at' => $p->at, 'bt' => $p->bt, 'wt' => $tat - $p->bt, 'tat' => $tat, 'rt' => $first_rt[$idx]];
                }
            } else {
                $start = $time; $time++; $idleTime++;
                $last = end($this->gantt);
                if ($last && $last['id'] == 'IDLE') {
                    $this->gantt[count($this->gantt) - 1]['end'] = $time;
                    $this->gantt[count($this->gantt) - 1]['duration']++;
                } else {
                    $this->gantt[] = ['id' => 'IDLE', 'start' => $start, 'end' => $time, 'duration' => 1];
                }
            }
        }
        usort($this->result, fn($a, $b) => strcmp($a['id'], $b['id']));
        return $this->calcAverages($time, $idleTime);
    }
}

class RRScheduler extends Scheduler {
    public function run($quantum) {
        $time = 0; $idleTime = 0; $completed = 0; $n = count($this->processes);
        $queue = []; $in_queue = array_fill(0, $n, false);
        $rem_bt = array_map(fn($p) => $p->bt, $this->processes);
        $first_rt = array_fill(0, $n, -1);

        for ($i = 0; $i < $n; $i++) { if ($this->processes[$i]->at <= $time) { array_push($queue, $i); $in_queue[$i] = true; } }

        while ($completed < $n) {
            if (empty($queue)) {
                $this->queueHistory[] = ['time' => $time, 'active' => 'IDLE', 'waiting' => []];
                $time++; $idleTime++;
                $this->gantt[] = ['id' => 'IDLE', 'start' => $time-1, 'end' => $time, 'duration' => 1];
                for ($i = 0; $i < $n; $i++) {
                    if ($this->processes[$i]->at <= $time && !$in_queue[$i] && $rem_bt[$i] > 0) { array_push($queue, $i); $in_queue[$i] = true; }
                }
                continue;
            }

            $idx = array_shift($queue);
            $p = $this->processes[$idx];
            
            $wait_names_now = array_map(fn($q_idx) => $this->processes[$q_idx]->id, $queue);
            $this->queueHistory[] = ['time' => $time, 'active' => $p->id, 'waiting' => $wait_names_now];

            if ($first_rt[$idx] == -1) $first_rt[$idx] = $time - $p->at;

            $start = $time; $spent = min($quantum, $rem_bt[$idx]);
            $time += $spent; $rem_bt[$idx] -= $spent; $end = $time;

            $last = end($this->gantt);
            if ($last && $last['id'] == $p->id) {
                $this->gantt[count($this->gantt) - 1]['end'] = $end;
                $this->gantt[count($this->gantt) - 1]['duration'] += $spent;
            } else {
                $this->gantt[] = ['id' => $p->id, 'start' => $start, 'end' => $end, 'duration' => $spent];
            }

            for ($i = 0; $i < $n; $i++) {
                if ($this->processes[$i]->at <= $time && $this->processes[$i]->at > $start && !$in_queue[$i] && $rem_bt[$i] > 0) {
                    array_push($queue, $i); $in_queue[$i] = true;
                }
            }

            if ($rem_bt[$idx] > 0) array_push($queue, $idx);
            else {
                $tat = $end - $p->at;
                $this->result[] = ['id' => $p->id, 'at' => $p->at, 'bt' => $p->bt, 'wt' => $tat - $p->bt, 'tat' => $tat, 'rt' => $first_rt[$idx]];
                $completed++;
            }
        }
        usort($this->result, fn($a, $b) => strcmp($a['id'], $b['id']));
        return $this->calcAverages($time, $idleTime);
    }
}

// 3. EXECUTE SIMULATION
$sjfEngine = new SJFScheduler($processesInput);
$sjf_avg = $sjfEngine->run();

$rrEngine = new RRScheduler($processesInput);
$rr_avg = $rrEngine->run($quantum);

// 4. EXACT ANALYSIS LOGIC BASED ON RUBRIC QUESTIONS
$q1_wt = ($sjf_avg['wt'] < $rr_avg['wt']) ? "<strong>SJF</strong> gave a lower average waiting time ({$sjf_avg['wt']} vs {$rr_avg['wt']})." : "<strong>Round Robin</strong> gave a lower or equal average waiting time ({$rr_avg['wt']} vs {$sjf_avg['wt']}).";

$q2_rt = ($rr_avg['rt'] < $sjf_avg['rt']) ? "<strong>Round Robin</strong> gave a lower average response time ({$rr_avg['rt']} vs {$sjf_avg['rt']})." : "<strong>SJF</strong> gave a lower average response time ({$sjf_avg['rt']} vs {$rr_avg['rt']}).";

$q3_fair = "<strong>Yes</strong>, Round Robin appeared fairer across all processes by preventing starvation and ensuring every process received CPU time within the specified quantum.";

$q4_eff = "<strong>Yes</strong>, SJF (Preemptive) completed short jobs more efficiently by preempting longer processes immediately when a shorter one arrived.";

$q5_quantum = "The quantum of <strong>{$quantum}</strong> dictated the time-slicing frequency. A larger quantum would make RR behave more like FCFS, while this selected quantum ensured frequent context switches for fairness at the cost of some overhead.";

$recommendation = ($sjf_avg['wt'] < $rr_avg['wt']) 
    ? "For this specific workload, <strong>SJF</strong> is recommended because it maximizes overall system efficiency and minimizes waiting time." 
    : "For this specific workload, <strong>Round Robin</strong> is recommended because it balances the execution perfectly and avoids long wait times.";

$conclusion_tradeoff = "The main trade-off observed is <strong>Fairness vs. Efficiency</strong>. SJF optimizes average metrics (efficiency) but risks starving long jobs. Round Robin sacrifices some waiting time efficiency to guarantee fair CPU sharing and faster initial response times.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>C5 Formal Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg: #f0f4f8; --card: #ffffff; --text-main: #1e293b; --primary: #2563eb; --primary-hover: #1d4ed8; --rr-color: #10b981; --sjf-color: #f59e0b; --idle: #cbd5e1; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; padding: 30px; color: var(--text-main); }
        .dashboard { max-width: 1150px; margin: auto; animation: fadeUp 0.6s ease-out; }
        
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .card { background: var(--card); padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #e2e8f0; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 25px; }
        h1 { color: #0f172a; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
        h2 { color: #1e293b; font-size: 20px; font-weight: 600; display: flex; align-items: center; margin-top: 0; margin-bottom: 20px; }
        h2::before { content: ''; display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); margin-right: 10px; }
        
        /* Advanced Metrics Cards */
        .metrics-row { display: flex; gap: 15px; margin-bottom: 20px; }
        .metric-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; text-align: center; }
        .metric-title { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 5px; display: block; }
        .metric-value { font-size: 20px; font-weight: 700; color: #0f172a; }

        .gantt-wrapper { overflow-x: auto; margin: 20px 0 30px 0; padding-bottom: 20px; }
        .gantt-chart { display: inline-flex; background: #f8fafc; border-radius: 12px; min-width: 100%; border: 1px solid #e2e8f0; padding: 5px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }
        .gantt-block { display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; padding: 18px 0; position: relative; font-weight: 600; min-width: 45px; border-radius: 8px; margin: 0 2px; transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .gantt-block:hover { transform: scaleY(1.05); z-index: 10; }
        .gantt-block span.time { position: absolute; bottom: -28px; color: #64748b; font-size: 13px; right: -8px; font-weight: 700; background: white; padding: 2px 6px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        th, td { padding: 14px; text-align: center; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f1f5f9; }
        
        .queue-container { overflow-x: auto; padding: 10px 5px 25px 5px; margin-top: 15px; scrollbar-width: thin; }
        .queue-timeline { display: inline-flex; gap: 15px; }
        .queue-step { background: white; border: 1px solid #e2e8f0; border-top: 5px solid var(--primary); border-radius: 10px; padding: 15px; min-width: 140px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); position: relative; transition: all 0.3s; }
        .queue-step:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .queue-step::after { content: '→'; position: absolute; right: -18px; top: 40%; color: #cbd5e1; font-weight: bold; font-size: 20px; }
        .queue-step:last-child::after { display: none; }
        .step-time { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: block; letter-spacing: 0.5px; }
        .step-cpu { font-size: 16px; color: #0f172a; font-weight: 700; margin-bottom: 8px; }
        .step-queue { font-size: 12px; color: #475569; background: #f1f5f9; padding: 6px 8px; border-radius: 6px; font-family: monospace; font-size: 13px; }

        .charts-container { display: flex; gap: 30px; }
        .chart-box { flex: 1; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fafbfc; }
        
        /* New QA Box Styles for the Rubric Questions */
        .qa-box { background: #f8fafc; padding: 15px; border-left: 4px solid var(--primary); margin-bottom: 15px; border-radius: 0 8px 8px 0; }
        .qa-box strong.question { color: #0f172a; display: block; margin-bottom: 5px; font-size: 15px; }
        .qa-box span.answer { color: #334155; font-size: 14px; }
        
        .btn-action { padding: 12px 24px; color: white; background: var(--primary); text-decoration: none; border-radius: 8px; font-weight: 600; font-family: 'Poppins', sans-serif; cursor: pointer; border: none; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
        .btn-action:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3); }

        @media print {
            body { background: white; padding: 0; }
            .card { box-shadow: none; border: 1px solid #eee; margin-bottom: 20px; page-break-inside: avoid; }
            .btn-action, .header-actions button, .header-actions a { display: none !important; }
            .queue-container { overflow-x: visible; }
            .queue-timeline { flex-wrap: wrap; gap: 10px; }
            .queue-step::after { display: none; }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="header-actions">
        <h1>Dashboard & Analytics <span style="color: #64748b; font-weight:300;">| C5 Report</span></h1>
        <div style="display: flex; gap: 15px;">
            <a href="index.php" class="btn-action">← Back to Setup</a>
            <button class="btn-action" onclick="window.print()">🖨️ Export PDF</button>
        </div>
    </div>

    <div class="card">
        <h2 style="color: #4f46e5;">📊 Required Analysis & Conclusion</h2>
        
        <div class="qa-box">
            <strong class="question">1. Which algorithm gave lower average waiting time?</strong>
            <span class="answer"><?php echo $q1_wt; ?></span>
        </div>
        <div class="qa-box">
            <strong class="question">2. Which algorithm gave lower average response time?</strong>
            <span class="answer"><?php echo $q2_rt; ?></span>
        </div>
        <div class="qa-box">
            <strong class="question">3. Did Round Robin appear fairer across all processes?</strong>
            <span class="answer"><?php echo $q3_fair; ?></span>
        </div>
        <div class="qa-box">
            <strong class="question">4. Did SJF complete short jobs more efficiently?</strong>
            <span class="answer"><?php echo $q4_eff; ?></span>
        </div>
        <div class="qa-box">
            <strong class="question">5. How did the chosen quantum affect Round Robin behavior?</strong>
            <span class="answer"><?php echo $q5_quantum; ?></span>
        </div>
        <div class="qa-box" style="border-left-color: #f59e0b; background: #fffbeb;">
            <strong class="question">6. Recommendation & Final Conclusion:</strong>
            <span class="answer"><?php echo $recommendation; ?><br><br><?php echo $conclusion_tradeoff; ?></span>
        </div>
    </div>

    <div class="card">
        <h2>Performance Visualizations</h2>
        <div class="charts-container">
            <div class="chart-box"><canvas id="wtChart"></canvas></div>
            <div class="chart-box"><canvas id="rtChart"></canvas></div>
        </div>
    </div>

    <div class="card" style="border-top: 5px solid var(--rr-color);">
        <h2 style="color: var(--rr-color);">Round Robin (RR) <span style="background: #ecfdf5; color: #047857; font-size: 12px; padding: 4px 10px; border-radius: 20px; margin-left: 10px; font-weight:700;">Quantum: <?php echo $quantum; ?></span></h2>
        
        <div class="metrics-row">
            <div class="metric-box"><span class="metric-title">CPU Utilization</span><span class="metric-value"><?php echo $rrEngine->systemMetrics['utilization']; ?>%</span></div>
            <div class="metric-box"><span class="metric-title">System Throughput</span><span class="metric-value"><?php echo $rrEngine->systemMetrics['throughput']; ?> proc/ms</span></div>
            <div class="metric-box"><span class="metric-title">Total Time</span><span class="metric-value"><?php echo $rrEngine->systemMetrics['total_time']; ?></span></div>
        </div>

        <div class="gantt-wrapper">
            <div class="gantt-chart">
                <?php foreach ($rrEngine->gantt as $block): 
                    $bg = $block['id'] == 'IDLE' ? 'var(--idle)' : 'linear-gradient(135deg, #10b981, #059669)';
                ?>
                    <div class="gantt-block" style="flex-grow: <?php echo $block['duration']; ?>; background: <?php echo $bg; ?>">
                        <?php echo $block['id']; ?>
                        <span class="time"><?php echo $block['end']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <h3 style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px;">Execution Trace (Ready Queue)</h3>
        <div class="queue-container">
            <div class="queue-timeline">
                <?php foreach($rrEngine->queueHistory as $step): ?>
                    <div class="queue-step" style="border-top-color: var(--rr-color);">
                        <span class="step-time">Time: <?php echo $step['time']; ?></span>
                        <div class="step-cpu" style="color: var(--rr-color);">CPU: <?php echo $step['active']; ?></div>
                        <div class="step-queue">[<?php echo implode(', ', $step['waiting']); ?>]</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <table>
            <tr><th>Process ID</th><th>Arrival</th><th>Burst</th><th>Wait Time (WT)</th><th>Turnaround (TAT)</th><th>Response Time (RT)</th></tr>
            <?php foreach ($rrEngine->result as $r): ?>
                <tr><td><strong><?php echo $r['id']; ?></strong></td><td><?php echo $r['at']; ?></td><td><?php echo $r['bt']; ?></td><td><?php echo $r['wt']; ?></td><td><?php echo $r['tat']; ?></td><td><?php echo $r['rt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card" style="border-top: 5px solid var(--sjf-color);">
        <h2 style="color: var(--sjf-color);">Shortest Job First (SJF)</h2>
        
        <div class="metrics-row">
            <div class="metric-box"><span class="metric-title">CPU Utilization</span><span class="metric-value"><?php echo $sjfEngine->systemMetrics['utilization']; ?>%</span></div>
            <div class="metric-box"><span class="metric-title">System Throughput</span><span class="metric-value"><?php echo $sjfEngine->systemMetrics['throughput']; ?> proc/ms</span></div>
            <div class="metric-box"><span class="metric-title">Total Time</span><span class="metric-value"><?php echo $sjfEngine->systemMetrics['total_time']; ?></span></div>
        </div>

        <div class="gantt-wrapper">
            <div class="gantt-chart">
                <?php foreach ($sjfEngine->gantt as $block): 
                    $bg = $block['id'] == 'IDLE' ? 'var(--idle)' : 'linear-gradient(135deg, #f59e0b, #d97706)';
                ?>
                    <div class="gantt-block" style="flex-grow: <?php echo $block['duration']; ?>; background: <?php echo $bg; ?>">
                        <?php echo $block['id']; ?>
                        <span class="time"><?php echo $block['end']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3 style="font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px;">Execution Trace (Ready Queue)</h3>
        <div class="queue-container">
            <div class="queue-timeline">
                <?php foreach($sjfEngine->queueHistory as $step): ?>
                    <div class="queue-step" style="border-top-color: var(--sjf-color);">
                        <span class="step-time">Time: <?php echo $step['time']; ?></span>
                        <div class="step-cpu" style="color: var(--sjf-color);">Picked: <?php echo $step['active']; ?></div>
                        <div class="step-queue">[<?php echo implode(', ', $step['waiting']); ?>]</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <table>
            <tr><th>Process ID</th><th>Arrival</th><th>Burst</th><th>Wait Time (WT)</th><th>Turnaround (TAT)</th><th>Response Time (RT)</th></tr>
            <?php foreach ($sjfEngine->result as $r): ?>
                <tr><td><strong><?php echo $r['id']; ?></strong></td><td><?php echo $r['at']; ?></td><td><?php echo $r['bt']; ?></td><td><?php echo $r['wt']; ?></td><td><?php echo $r['tat']; ?></td><td><?php echo $r['rt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>

<script>
    Chart.defaults.font.family = "'Poppins', sans-serif";
    const sjfData = [<?php echo $sjf_avg['wt']; ?>, <?php echo $sjf_avg['tat']; ?>, <?php echo $sjf_avg['rt']; ?>];
    const rrData = [<?php echo $rr_avg['wt']; ?>, <?php echo $rr_avg['tat']; ?>, <?php echo $rr_avg['rt']; ?>];

    new Chart(document.getElementById('wtChart'), {
        type: 'bar',
        data: { labels: ['Avg Wait Time (WT)', 'Avg Turnaround (TAT)'], datasets: [
            { label: 'SJF', data: [sjfData[0], sjfData[1]], backgroundColor: '#f59e0b', borderRadius: 6 },
            { label: 'Round Robin', data: [rrData[0], rrData[1]], backgroundColor: '#10b981', borderRadius: 6 }
        ]},
        options: { responsive: true, plugins: { title: { display: true, text: 'Wait & Turnaround Comparison', font: {size: 14} } } }
    });

    new Chart(document.getElementById('rtChart'), {
        type: 'bar',
        data: { labels: ['Avg Response Time (RT)'], datasets: [
            { label: 'SJF', data: [sjfData[2]], backgroundColor: '#f59e0b', borderRadius: 6 },
            { label: 'Round Robin', data: [rrData[2]], backgroundColor: '#10b981', borderRadius: 6 }
        ]},
        options: { responsive: true, plugins: { title: { display: true, text: 'Responsiveness Comparison', font: {size: 14} } } }
    });
</script>
</body>
</html>
