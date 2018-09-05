<?php
namespace Esmi\socket;

trait socketUtils
{
 
    function setdb($d) {
        $this->db = $d;
        //$g->setforceShow(false);
    }

    function getResult() {
        return $this->result;
    }
    function getreport($input) {

        $r = array();
        if (!$this->write($input)) {
            $r['status'] = "error";
            $r['message'] = "無法寫入socket! 請重啟報表程式試看看";
        }
        else {
            $b = $this->read();
            //echo $b . " b_end\r\n";
            if ($b) {
                $r['status'] = "OK";
                $r['buffer'] = $b;
            }
            else {
                $r = $this->error();
            }
        }
        return $r;
    }
    function buffer($r) {
        if (isset($r['buffer']))
            echo $r['buffer'];
    }

    function chkeckReport($r) {
        return $r ? $r : $this->error();
    }
}
?>