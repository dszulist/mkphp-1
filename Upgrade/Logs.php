<?php
/**
 * MK_Upgrade_Logs
 *
 * Obsługa logów z aktualizacji
 *
 * @category    MK_Upgrade
 * @package        MK_Upgrade_Logs
 */
class MK_Upgrade_Logs {

	/**
	 * Odczytywanie informacji o X ostatnio wykonanych aktualizacjach
	 *
	 * @param int    $numberOfupgrades (default:5)
	 * @param string $prefix
	 *
	 * @return array
	 */
	public static function getInfo($numberOfupgrades = 5, $prefix = '') {

		$upgradeLogs = glob(MK_DIR_UPDATE_LOGS . DIRECTORY_SEPARATOR . '*.log');
		$upgradeLogs = array_reverse(array_slice($upgradeLogs, -($numberOfupgrades)));
		$upgradeLogsArray = array();

		foreach($upgradeLogs as $i => $row) {
			$lastLine = exec(("tail -n 1 $row"));
			$upgradeLogsArray["{$prefix}{$i}_FILE"] = $row;
			$upgradeLogsArray["{$prefix}{$i}_LAST"] = $lastLine;
		}

		return $upgradeLogsArray;
	}

}
