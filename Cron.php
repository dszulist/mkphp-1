<?php

/**
 * MK_Cron
 *
 * Klasa posiada metody do pracy cronem systemów UNIX'owych
 *
 * @category MK
 * @package MK_Cron
 */
class MK_Cron {

    public function __construct($nazwa = '') {        
        echo 'wlazło'.PHP_EOL;
       // $this->disableCronBlockageAllTasks();        
    }

    /**
     * Funkcja sprawdza czy funkcja o podanej nazwie istnieje w MK_Cron, jeżeli istnieje to ją wykonuje
     * 
     * @param string $nazwa 
     * @param array $args
     */
    public function __call($method = '',array $args=array()) {
        
        if (method_exists($this, $method) === true) {
            $this->$method($args);
            exit;
        }        
        
        $this->writeToConsole('Error! Ządana metoda nie istnieje.');
        exit;
    }

    /**
     * 
     * Wypisuje informacje na ekran
     * 
     * @param String $text
     * @param String $endOfLine
     */
    private function writeToConsole($text, $endOfLine = PHP_EOL) {
        echo $text . $endOfLine;
    }

    /**
     * Uruchamia zadania z crona
     */
    private function setup() {

        $this->db = Db::getInstance();
        //pobieramy dane do powtarzających się akcji
        $sql = 'SELECT ct.* FROM cron_tasks ct ORDER BY ct.id ASC';
        $res = $this->db->Execute($sql, array());
        while ($ret = $res->FetchRow()) {
            $_tasks_tab[$ret['cron_id']] = $ret['createdate'];
        }

        $logs = new Log();
        $tasks_for_execution = array();
        //wykonumjemy zadania wg harmonogramu
        $sql = "SELECT cs.* FROM cron_setup cs WHERE (lock_datetime IS NULL OR lock_datetime < now()) AND cron_lock = ? ORDER BY cs.cron_sequence ASC";
        $res = $this->db->Execute($sql, array('0'));
        while ($ret = $res->FetchRow()) {
            $_error = false;
            $info = '';
            if (method_exists($ret['class_name'], $ret['method_name']) === true) {
                $current_time = time();
                $__temp_tab['cron_minute'] = 'minute';
                $__temp_tab['cron_hour'] = 'hour';
                $__temp_tab['cron_day'] = 'day';
                $__temp_tab['cron_month'] = 'month';
                $__temp_tab['cron_day_of_week'] = 'day_of_week';

                $_temp_tab['cron_minute'] = 'i';
                $_temp_tab['cron_hour'] = 'H';
                $_temp_tab['cron_day'] = 'j';
                $_temp_tab['cron_month'] = 'n';
                $_temp_tab['cron_day_of_week'] = 'N'; //N = 1 (for Monday) through 7 (for Sunday)
                $_execute_task = true;

                foreach ($_temp_tab as $k => $v) {
                    if ($ret[$k] == '*') {
                        $ret[$k] = date($v, $current_time);
                    } elseif (strpos($ret[$k], '/') !== false) {
                        $_exp = explode("/", $ret[$k]);
                        if ($k == 'day_of_week') {
                            $check_day_of_week_ct = date($v, $current_time);
                            if ($check_day_of_week_ct == $_exp['1']) {
                                $ret['day'] = date('j', $current_time);
                            } else {
                                $_execute_task = false;
                            }
                            continue;
                        }
                        $_check = (int) date($v, $current_time);

                        if (is_int($_check / $_exp['1']) OR $_check == '0') {
                            if (isset($_tasks_tab[$ret['id']])) {
                                $next_task_time = strtotime($_tasks_tab[$ret['id']] . " + " . $_exp['1'] . " " . $__temp_tab[$k]);
                                if ($next_task_time < $current_time) {
                                    $ret[$k] = date($v, $current_time);
                                } else {
                                    $ret[$k] = date($v, strtotime($_tasks_tab[$ret['id']] . " + " . $_exp['1'] . " " . $__temp_tab[$k]));
                                }
                            } else {
                                $ret[$k] = date($v, $current_time);
                            }
                        } else {
                            $_execute_task = false;
                        }
                    }
                }
                $year = date("Y");
                if ($_execute_task === true) {
                    //blokada przed wykonywaniem się zadania kilka razy w ciągu minuty
                    if (isset($_tasks_tab[$ret['id']])) {
                        $execution_last_task = date("Y-m-d, H:i", strtotime($_tasks_tab[$ret['id']]));
                        if ($execution_last_task == date("Y-m-d, H:i", $current_time)) {
                            continue;
                        }
                    }
                    $cron_job_time_minute = date("Y-m-d, H:i", mktime($ret['cron_hour'], $ret['cron_minute'], 0, $ret['cron_month'], $ret['cron_day'], $year));

                    if ($cron_job_time_minute == date("Y-m-d, H:i", $current_time)) {
                        $tasks_for_execution[$ret['id']] = $ret;
                    }
                }
            } else {
                $_error = true;
                $info = 'Cron - Class: ' . $ret['class_name'] . ' or method: ' . $ret['method_name'] . ' not found';
            }
            if ($_error) {
                $this->setLockCron($info, $ret['id']);
            }
        }

        if (!empty($tasks_for_execution)) {
            foreach ($tasks_for_execution as $ket => $ret) {
                $_error = false;
                $info = '';

                //zablokowanie zadania przed jego wykonaniem
                $this->enabledisableCronBlockage('1', $ret['id']);

                $this->db->BeginTrans();
                try {
                    if (method_exists($ret['class_name'], $ret['method_name']) === true) {
                        $obj = new $ret['class_name']();
                        $obj->$ret['method_name']();
                    } else {
                        $info = 'Cron - Errors in class: ' . $ret['class_name'] . ' - method: ' . $ret['method_name'];
                        throw new Exception($info);
                        $_error = true;
                    }
                } catch (Exception $e) {
                    $this->writeToConsole($e->getMessage());
                }
                if ($_error) {
                    $this->db->RollbackTrans();
                } else {
                    $this->db->CommitTrans();
                    //czyszczenie blokady crona
                    if ($ret['lock_datetime'] != '') {
                        $this->db->Execute("UPDATE cron_setup SET lock_datetime = NULL WHERE id = ?", array($ret['id']));
                    }
                    $info = 'Cron - class: ' . $ret['class_name'] . ' - method: ' . $ret['method_name'] . ' execution correct';
                    $logs->add($info, '5', '', true);
                }
                $createdate = $this->db->DBTimeStamp(time());
                $this->db->Execute("INSERT INTO cron_tasks (id, cron_id, createdate, info) values (default, ?, ?, ?)", array($ret['id'], $createdate, $info));

                //odblokowanie zadania po jego wykonaniu
                $this->enabledisableCronBlockage('0', $ret['id']);

                if ($_error) {
                    $this->setLockCron($info, $ret['id']);
                }
            }
        }
    }


    /**
     * Wysyła wiadomosc email z informacjami o błędzie
     * 
     * @param String $info - informacja o błędzie
     */
    public function sendErrorEmail($info) {

        $date = strftime("%a, %d %b %Y %H:%M:%S");
        $email = '<br/><br/><table width="100%" border="1">';

        if (isset($_SERVER["HTTP_HOST"])){
            $email .= '<tr><td><strong>Host:</strong></td><td>' . $_SERVER["HTTP_HOST"] . '</td></tr>';
        }

        $email .= '<tr><td><strong>Baza danych:</strong></td><td>' . DB_NAME . '</td></tr>' .
                '<tr><td><strong>Data wystąpnienia:</strong></td><td>' . $date . '</td></tr>';

        if (isset($_SERVER["REQUEST_URI"])){
            $email .= '<tr><td><strong>REQUEST_URI:</strong></td><td>' . $_SERVER["REQUEST_URI"] . '</td></tr>';
        }
        if (isset($_SERVER["HTTP_USER_AGENT"])){
            $email .= '<tr><td><strong>HTTP_USER_AGENT:</strong></td><td>' . $_SERVER["HTTP_USER_AGENT"] . '</td></tr>';
        }
        if (isset($_SERVER["REMOTE_ADDR"])){
            $email .= '<tr><td><strong>REMOTE_ADDR:</strong></td><td>' . $_SERVER["REMOTE_ADDR"] . '</td></tr>';
        }

        $email .= '<tr><td colspan="2"><strong>Wiadomość:</strong></td></tr>' .
                '<tr><td colspan="2"><strong>Informacje szczegółowe:</strong></td></tr>' .
                '<tr><td colspan="2"><pre>' . $info . '</pre></td></tr>' .
                '</table>';

        $headers = 'Content-type: text/html; charset=utf-8' . PHP_EOL
                 . 'Subject: ERROR ' . DB_NAME . PHP_EOL
                 . 'Date: ' . $date . PHP_EOL
                 . 'From: team_docflow@madkom.pl' . PHP_EOL;
        
        mail('team_docflow@madkom.pl', 'ERROR - CRON -' . DB_NAME, $email, $headers);
    }

}