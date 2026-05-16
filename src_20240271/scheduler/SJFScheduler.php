<?php
require_once __DIR__ . '/../model/Process.php';

class SJFScheduler {
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

    public function run() {
        $time = 0;
        $idleTime = 0;
        $completed = 0;
        $n = count($this->processes);
        
        $rem_bt = array_map(function($p) { return $p->bt; }, $this->processes);
        $is_comp = array_fill(0, $n, false);
        $first_rt = array_fill(0, $n, -1);
        $last_active = null;

        while ($completed < $n) {
            $idx = -1;
            $min_bt = PHP_INT_MAX;
            $available_now = [];
            
            for ($i = 0; $i < $n; $i++) {
                if ($this->processes[$i]->at <= $time && !$is_comp[$i]) {
                    $available_now[] = $this->processes[$i];
                    if ($rem_bt[$i] < $min_bt) {
                        $min_bt = $rem_bt[$i];
                        $idx = $i;
                    }
                }
            }

            $active_id = ($idx != -1) ? $this->processes[$idx]->id : 'IDLE';
            
            if ($active_id !== $last_active) {
                $waiting_names = [];
                foreach($available_now as $p) { 
                    if($idx == -1 || $p->id !== $active_id) {
                        $waiting_names[] = $p->id; 
                    }
                }
                $this->queueHistory[] = [
                    'time' => $time, 
                    'active' => $active_id, 
                    'waiting' => $waiting_names
                ];
                $last_active = $active_id;
            }

            if ($idx != -1) {
                $p = $this->processes[$idx];
                if ($first_rt[$idx] == -1) {
                    $first_rt[$idx] = $time - $p->at;
                }

                $start = $time;
                $time++;
                $rem_bt[$idx]--;

                $last_gantt = end($this->gantt);
                if ($last_gantt && $last_gantt['id'] == $p->id) {
                    $this->gantt[count($this->gantt) - 1]['end'] = $time;
                    $this->gantt[count($this->gantt) - 1]['duration']++;
                } else {
                    $this->gantt[] = [
                        'id' => $p->id, 
                        'start' => $start, 
                        'end' => $time, 
                        'duration' => 1
                    ];
                }

                if ($rem_bt[$idx] == 0) {
                    $is_comp[$idx] = true;
                    $completed++;
                    $tat = $time - $p->at;
                    $this->result[] = [
                        'id' => $p->id, 
                        'at' => $p->at, 
                        'bt' => $p->bt,
                        'wt' => $tat - $p->bt, 
                        'tat' => $tat, 
                        'rt' => $first_rt[$idx]
                    ];
                }
            } else {
                $start = $time;
                $time++;
                $idleTime++;
                
                $last_gantt = end($this->gantt);
                if ($last_gantt && $last_gantt['id'] == 'IDLE') {
                    $this->gantt[count($this->gantt) - 1]['end'] = $time;
                    $this->gantt[count($this->gantt) - 1]['duration']++;
                } else {
                    $this->gantt[] = [
                        'id' => 'IDLE', 
                        'start' => $start, 
                        'end' => $time, 
                        'duration' => 1
                    ];
                }
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
