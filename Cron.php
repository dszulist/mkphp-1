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

    public function __construct() {
        
    }

    /**
     * Funkcja sprawdza czy funkcja o podanej nazwie istnieje w MK_Cron, jeżeli istnieje to ją wykonuje
     * 
     * @param string $nazwa 
     * @param array $args
     */
    public function __call($method = '', array $args=array()) {

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
    public function writeToConsole($text, $endOfLine = PHP_EOL) {
        echo $text . $endOfLine;
    }

    /**
     * Uruchamia zadania z crona
     */
    public function setup() {


        //pobieramy dane do powtarzających się akcji
        $_tasks_tab = $this->getTasksDates();

        $tasks_for_execution = array();

        //wykonumjemy zadania wg harmonogramu        
        $unlocked = $this->getUnlockedTasks();
        $year = date("Y");

        foreach ($unlocked as $ret) {

            $error = false;
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
                $error = true;
                $info = 'Cron - Class: ' . $ret['class_name'] . ' or method: ' . $ret['method_name'] . ' not found';
            }

            if ($error) {
                $this->setLockCron($info, $ret['id']);
            }
        }

        if (!empty($tasks_for_execution)) {
            $this->executeTasks($tasks_for_execution);
        }
    }

    public function executeTasks($tasks_for_execution) {
        
        foreach ($tasks_for_execution as $ket => $ret) {
            $error = false;
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
                    $error = true;
                }
            } catch (Exception $e) {
                $this->writeToConsole($e->getMessage());
            }

            if ($error) {
                $this->db->RollbackTrans();
            } else {
                $this->db->CommitTrans();
                //czyszczenie blokady crona
                if ($ret['lock_datetime'] != '') {
                    $this->db->Execute("UPDATE cron_setup SET lock_datetime = NULL WHERE id = ?", array($ret['id']));
                }
                $info = 'Cron - class: ' . $ret['class_name'] . ' - method: ' . $ret['method_name'] . ' execution correct';
//                    $logs = new Log();
//                    $logs->add($info, '5', '', true);
            }

            $this->db->Execute("INSERT INTO cron_tasks (id, cron_id, createdate, info) values (default, ?, ?, ?)", array($ret['id'], $this->db->DBTimeStamp(time()), $info));

            //odblokowanie zadania po jego wykonaniu
            $this->enabledisableCronBlockage('0', $ret['id']);

            if ($error) {
                $this->setLockCron($info, $ret['id']);
            }
        }
    }

}