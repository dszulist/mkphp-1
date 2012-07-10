<?php
require_once (MK_DIR_VENDORS . DIRECTORY_SEPARATOR . 'CronParser' . DIRECTORY_SEPARATOR . 'CronParser.php');

/**
 * MK_Cron
 *
 * Klasa posiada metody do pracy cronem systemów UNIX'owych
 *
 * @category MK
 * @package MK_Cron
 */
class MK_Cron
{

	/**
	 * Połączenie z bazą danych i dostęp do tabeli crona
	 * @var MK_System_Cron
	 */
	private $db;

	/**
	 * Parser dla CRON-a => CronParser()
	 * @var CronParser
	 */
	private $parser;

	/**
	 * Miejsce zapisywania logów systemowych
	 * @var MK_Logs
	 */
	private $logs;

	/**
	 * Czas po jakim zadanie już wykonywane może zostać ponownie uruchomione
	 * @var int
	 */
	private $execLockTimeout = 300; // 5 m

	/**
	 * Czas po jakim zadanie z błędem może zostać ponownie uruchomione
	 * @var int
	 */
	private $errorLockTimeout = 3600; // 1 h

	/**
	 * @todo Kuba (jkonefal) da opis i ustawi odpowiednią hermetyzację :)
	 * @var MK_System_Cron
	 */
	public $model;

	/**
	 * Konstruktor
	 */
	public function __construct()
	{
		$this->model = new MK_System_Cron();
		$this->parser = new CronParser();
	}

	/**
	 * Ustawienie czasu po jakim zadanie już wykonywane może się ponownie uruchomić
	 *
	 * @param $seconds
	 */
	public function setExecLockTimeout($seconds)
	{
		$this->execLockTimeout = $seconds;
	}

	/**
	 * Ustawienie czasu po jakim zadanie z błędem może się ponownie uruchomić
	 *
	 * @param $seconds
	 */
	public function setErrorLockTimeout($seconds)
	{
		$this->errorLockTimeout = $seconds;
	}

	/**
	 * Zapisanie raportów do logs.madkom.pl
	 *
	 * @param $errorMsg
	 */
	private function saveLog($errorMsg)
	{
		if (!($this->logs instanceof MK_Logs)) {
			$this->logs = new MK_Logs(APP_PATH);
		}
		$this->logs->saveToFile('cron', $errorMsg);
	}

	/**
	 * Uruchomienie zadania
	 *
	 * @param string $phpClass uruchomienie new $phpClass();
	 * @param string $phpMethod uruchomienie $phpClass->$phpMethod();
	 * @param string $phpArgv uruchomienie $phpClass->$phpMethod($phpArgv1,$phpArgv2,...);
	 *
	 * @return mixed
	 */
	private function runTask($phpClass, $phpMethod, $phpArgv)
	{
		if (class_exists($phpClass)) {
			$obj = new $phpClass();
			if (is_callable(array($obj, $phpMethod)) === true) {
				$args = explode(' ', $phpArgv);
				$obj->{$phpMethod}($args);
				return true;
			} else {
				$this->saveLog("Nie można uruchomić '{$phpClass}->{$phpMethod}''");
			}
		} else {
			$this->saveLog("Nie można uruchomić '{$phpClass}''");
		}
		return false;
	}

	/**
	 * Uruchomienie zadań z listy znajdującej się w bazie danych
	 */
	public function cronTabList()
	{
		$cronTasks = $this->model->getActiveList();
		foreach ($cronTasks as $cronTask) {
			$currentTime = time();

			// Pomijamy zadanie, które spowodowało błąd - oczekujemy pewien czas i ponawiamy próbę uruchomienia
			$errorLockTime = strtotime($cronTask['error_lock']);
			if (!empty($cronTask['error_lock']) && ($errorLockTime + $this->errorLockTimeout) > $currentTime) {
				continue;
			}

			// Pomijamy zadanie, które jeszcze się wykonuje - oczekujemy pewien czas i ponawiamy próbę uruchomienia
			$execLockTime = strtotime($cronTask['exec_lock']);
			if (!empty($cronTask['exec_lock']) && ($execLockTime + $this->execLockTimeout) > $currentTime) {
				continue;
			}

			// Sprawdzamy czy możemy uruchomić zadanie
			if ($this->parser->calcLastRan($cronTask['expression'])) {
				$lastExecTime = strtotime($cronTask['last_exec']);
				// Zadanie będzie można uruchomić
				if ($this->parser->getLastRanUnix() > $lastExecTime) {
					$currentTimeStamp = date("Y-m-d H:i:s", $currentTime);
					// Oznaczanie zadania jako wykonywane
					$this->model->setTaskExecuted($cronTask['id'], false, $currentTime);
					$this->saveLog("Wykonywanie zadania '{$cronTask['task_name']}' ({$cronTask['id']}) o godzinie {$currentTimeStamp}");
					// Czy zadanie się wykonało poprawnie?
					if ($this->runTask($cronTask['php_class'], $cronTask['php_method'], $cronTask['php_argv'])) {
						$currentTime = time();
						$currentTimeStamp = date("Y-m-d H:i:s", $currentTime);
						$this->model->setTaskExecuted($cronTask['id'], true, $currentTime);
						$this->saveLog("Wykonano zadanie '{$cronTask['task_name']}' ({$cronTask['id']}) o godzinie {$currentTimeStamp}");
					} else {
						$this->model->setTaskError($cronTask['id'], $currentTime);
						$this->saveLog("Zadanie o ID {$cronTask['id']} nie wykonało się prawidłowo o godzinie {$currentTimeStamp}");
					}
				}
			} else {
				$this->saveLog("Zadanie '{$cronTask['task_name']}' ({$cronTask['id']}) ma nieprawidłowe ustawienia: '{$cronTask['expression']}'");
			}
		}
	}

}