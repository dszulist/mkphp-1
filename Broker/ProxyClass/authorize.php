<?php
/**
 * Autoryzacja klienta na Brokerze
 */
class authorize
{

	/**
	 * @var integer
	 */
	public $synchronizationClientUID;

	/**
	 * @var string
	 */
	public $synchronizationClientPassword;

	/**
	 * @param $clientUid
	 * @param $clientPassword
	 */
	public function __construct($clientUid, $clientPassword)
	{
		$this->synchronizationClientUID = $clientUid;
		$this->synchronizationClientPassword = $clientPassword;
	}

}
