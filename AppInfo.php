<?php

/**
 * MK_AppInfo
 *
 * Obsługa odczytywania informacji o aplikacji (appinfo)
 *
 * @category MK
 * @package	MK_AppInfo
 * @author jkonefal
 */
class MK_AppInfo {

	/**
	 * Odczytywanie informacji o aplikacji (appinfo)
	 *
	 * @param string $dirPath
	 * @return array
	 */
	public static function load($dirPath) {
		$currentDir = getcwd();
		$changeDir = $dirPath !== $currentDir;
		if (!file_exists($dirPath . DIRECTORY_SEPARATOR . 'index.php')) {
			$dirPath .= DIRECTORY_SEPARATOR . 'public';
			if (!file_exists($dirPath . DIRECTORY_SEPARATOR . 'index.php')) {
				exit('Brak pliku index.php w lokalizacji ' . $dirPath);
			}
			if ($changeDir) {
				chdir($dirPath);
			}
		} else {
			if ($changeDir) {
				chdir($dirPath);
			}
		}
		$appInfo = self::_parseIni(shell_exec('php5 index.php -mappinfo'));
		if ($changeDir) {
			chdir($currentDir);
		}
		return $appInfo;
	}

	/**
	 * Parsowanie pliku INI ze string-a.
	 *
	 * @param string $string
	 * @return array
	 */
	private static function _parseIni($string) {
		$array = array();
		$lines = explode("\n", $string);
		foreach ($lines as $line) {
			$statement = preg_match("/^(?!;)(?P<key>[\w+\.\-]+?)\s*=\s*(?P<value>.+?)\s*$/", $line, $match);
			if ($statement) {
				$key = $match['key'];
				$value = $match['value'];
				if (preg_match("/^\".*\"$/", $value) || preg_match("/^'.*'$/", $value)) {
					$value = mb_substr($value, 1, mb_strlen($value) - 2);
				}
				$array[$key] = $value;
			}
		}
		return $array;
	}

}