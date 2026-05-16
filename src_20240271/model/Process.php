<?php

class Process {
    public $id;
    public $at;
    public $bt;
    public $wt;
    public $tat;
    public $rt;

    public function __construct($id, $at, $bt) {
        $this->id = $id;
        $this->at = $at;
        $this->bt = $bt;
    }
}
?>
