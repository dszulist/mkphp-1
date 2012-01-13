<?php

/**
 * MK_Filter_Html
 *
 * Filtrowanie - wykorzystuje HTML Purifier
 *
 * echo MK_Filter_Html::getInstance()->purify('html');
 *
 * @category	MK_Filter
 * @package		MK_Filter_Html
 */
class MK_Filter_Html {

	private static $_singleton;

	/**
	 * Tworzy i zwraca instancję klasy HTMLPurifier
	 *
	 * @return Object
	 */
	public static function getInstance() {
		if (!is_object(self::$_singleton)) {
			require_once(DIR_LIBS . DIRECTORY_SEPARATOR . 'htmlpurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.auto.php');

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Core.Encoding', 'UTF-8');
			$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
			
			validate_directory(MK_DIR_TEMP . DIRECTORY_SEPARATOR . 'HTMLPurifier');
			$config->set('Cache.SerializerPath', MK_DIR_TEMP . DIRECTORY_SEPARATOR . 'HTMLPurifier');

			self::$_singleton = new HTMLPurifier($config);
		}

		return self::$_singleton;
	}

}