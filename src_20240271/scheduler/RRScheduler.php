<?php
require_once __DIR__ . '/../model/Process.php';

class RRScheduler {
    protected $processes;
    public $gantt = [];
    public $result = [];
    public $queueHistory = [];
    public $systemMetrics = [];

    public function __construct($processes) {
        $this->processes = $processes;
        usort($this->processes, function($a, $b) {
            return $a->at == $b->at ? $a->bt <=> $b->bt : $a->at <=> $b->at;
        });
    }

    public function run($quantum) {
        $time = 0;
        $idleTime = 0;
        $completed = 0;
        $n = count($this->processes);
        $queue = [];
        $in_queue = array_fill(0, $n, false);
        
        $rem_bt = array_map(function($p) { return $p->bt; }, $this->processes);
        $first_rt = array_fill(0, $n, -1);

        for ($i = 0; $i < $n; $i++) { 
            if ($this->processes[$i]->at <= $time) { 
                array_push($queue, $i); 
                $in_queue[$i] = true; 
            } 
        }

        while ($completed < $n) {
            if (empty($queue)) {
                $this->queueHistory[] = [
                    'time' => $time, 
                    'active' => 'IDLE', 
                    'waiting' => []
                ];
                $time++;
                $idleTime++;
                $this->gantt[] = [
                    'id' => 'IDLE', 
                    'start' => $time-1, 
                    'end' => $time, 
                    'duration' => 1
                ];
                
                for ($i = 0; $i < $n; $i++) {
                    if ($this->processes[$i]->at <= $time && !$in_queue[$i] && $rem_bt[$i] > 0) { 
                        array_push($queue, $i); 
                        $in_queue[$i] = true; 
                    }
                }
                continue;
            }

            $idx = array_shift($queue);
            $p = $this->processes[$idx];
            
            $wait_names_now = array_map(function($q_idx) { 
                return $this->processes[$q_idx]->id; 
            }, $queue);
            
            $this->queueHistory[] = [
                'time' => $time, 
                'active' => $p->id, 
                'waiting' => $wait_names_now
            ];

            if ($first_rt[$idx] == -1) {
                $first_rt[$idx] = $time - $p->at;
            }

            $start = $time;
            $spent = min($quantum, $rem_bt[$idx]);
            $time += $spent;
            $rem_bt[$idx] -= $spent;
            $end = $time;

            $last = end($this->gantt);
            if ($last && $last['id'] == $p->id) {
                $this->gantt[count($this->gantt) - 1]['end'] = $end;
                $this->gantt[count($this->gantt) - 1]['duration'] += $spent;
            } else {
                $this->gantt[] = [
                    'id' => $p->id, 
                    'start' => $start, 
                    'end' => $end, 
                    'duration' => $spent
                ];
            }

            for ($i = 0; $i < $n; $i++) {
                if ($this->processes[$i]->at <= $time && $this->processes[$i]->at > $start && !$in_queue[$i] && $rem_bt[$i] > 0) {
                    array_push($queue, $i); 
                    $in_queue[$i] = true;
                }
            }

            if ($rem_bt[$idx] > 0) {
                array_push($queue, $idx);
            } else {
                $tat = $end - $p->at;
                $this->result[] = [
                    'id' => $p->id, 
                    'at' => $p->at, 
                    'bt' => $p->bt, 
                    'wt' => $tat - $p->bt, 
                    'tat' => $tat, 
                    'rt' => $first_rt[$idx]
                ];
                $completed++;
            }
        }
        
        usort($this->result, function($a, $b) { 
            return strcmp($a['id'], $b['id']); 
        });
        
        return $this->calcAverages($time, $idleTime);
    }

    protected function calcAverages($totalTime, $idleTime) {
        $wt = 0;
        $tat = 0;
        $rt = 0;
        $n = count($this->result) ?: 1;
        $totalBurst = 0;
        
        foreach ($this->result as $r) { 
            $wt += $r['wt']; 
            $tat += $r['tat']; 
            $rt += $r['rt']; 
            $totalBurst += $r['bt']; 
        }
        
        $utilization = ($totalTime > 0) ? round(($totalBurst / $totalTime) * 100, 2) : 0;
        $throughput = ($totalTime > 0) ? round($n / $totalTime, 3) : 0;
        
        $this->systemMetrics = [
            'utilization' => $utilization, 
            'throughput' => $throughput, 
            'total_time' => $totalTime
        ];
        
        return [
            'wt' => round($wt/$n, 2), 
            'tat' => round($tat/$n, 2), 
            'rt' => round($rt/$n, 2)
        ];
    }
}
?>
