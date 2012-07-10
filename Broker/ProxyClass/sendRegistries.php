<?php
/**
 * Wysłanie rejestrów do Brokera
 */
class sendRegistries
{

	/**
	 * @var docflowRegistry (array of docflowRegistry)
	 */
	public $text;

	/**
	 * XML z rejestrami
	 *
	 * @param string $registries
	 */
	public function __construct($registries = NULL)
	{
		$this->text = $registries;
	}

}
