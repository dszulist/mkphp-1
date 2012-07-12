<?php
/**
 * @category   Zend
 * @package    Zend_Log
 * @subpackage Formatter
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class MK_Zend_Log_Formatter_Json extends Zend_Log_Formatter_Abstract
{
	/**
	 * Factory for Zend_Log_Formatter_Firebug classe
	 *
	 * @param array|Zend_Config $options useless
	 *
	 * @return Zend_Log_Formatter_Firebug
	 */
	public static function factory($options)
	{
		return new self;
	}

	/**
	 * @param  array $event event data
	 *
	 * @return mixed event message
	 */
	public function format($event)
	{
		if (is_array($event['message'])) {
			$event = array_merge($event, $event['message']);
			unset($event['message']);
		}
		return json_encode($event);
	}
}