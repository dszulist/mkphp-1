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
		$command = shell_exec('php5 index.php -mappinfo');
		$appInfo = self::parseIni($command); // Na potrzeby logs.madkom.pl (PHP4)
		if ($changeDir) {
			chdir($currentDir);
		}
		return $appInfo;
	}

	/**
	 * Parsowanie pliku INI ze string-a.
	 * Na potrzeby logs.madkom.pl (PHP4) skopiowane z http://www.php.net/manual/en/function.parse-ini-string.php#97621
	 * Po aktualizacji do PHP5 można usunąć i wykorzystać parse_ini_string()
	 *
	 * @param string $string
	 * @return array
	 */
	private static function parseIni($string) {
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