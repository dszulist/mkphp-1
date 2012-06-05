<?php

/**
 * MK_Filter_Html
 *
 * Filtrowanie - wykorzystuje HTML Purifier
 *
 * echo MK_Filter_Html::getInstance()->purify('html');
 * //lub
 * echo MK_Filter_Html::purifyHtml('html');
 * //lub bez zakodowanych encji
 * echo MK_Filter_Html::purifyText('html');
 *
 * @category	MK_Filter
 * @package		MK_Filter_Html
 */
class MK_Filter_Html {

	/**
	 * @var
	 */
	private static $_singleton;

	/**
	 * Tworzy i zwraca instancję klasy HTMLPurifier
	 *
	 * @return Object
	 */
	public static function getInstance() {
		if (!is_object(self::$_singleton)) {
			require_once(MK_DIR_VENDORS . DIRECTORY_SEPARATOR . 'htmlpurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.auto.php');

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Core.Encoding', 'UTF-8');
			$config->set('HTML.Doctype', 'HTML 4.01 Transitional');

			validate_directory(MK_DIR_TEMP . DIRECTORY_SEPARATOR . 'HTMLPurifier');
			$config->set('Cache.SerializerPath', MK_DIR_TEMP . DIRECTORY_SEPARATOR . 'HTMLPurifier');

			self::$_singleton = new HTMLPurifier($config);
		}

		return self::$_singleton;
	}

	/**
	 * Filtrowanie treści i pozostanie w postaci Źródła HTML (zamiast "&amp;" będzie "&")
	 *
	 * @param string $html
	 * @return string
	 */
	public static function purifyText($html) {
		return html_entity_decode(self::getInstance()->purify($html), ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Filtrowanie treści i pozostanie w postaci zaenkodowanego HTML-a (np. "&amp;")
	 *
	 * @param string $html
	 * @return string
	 */
	public static function purifyHtml($html) {
		return self::getInstance()->purify($html);
	}

}