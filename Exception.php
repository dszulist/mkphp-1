<?php

/**
 * MK_Exception
 *
 * Obsługa wyjątków dla JSON-a
 *
 * @category MK
 * @package	MK_Exception
 */
class MK_Exception extends Exception {

	/**
	 * Rozbudowany raport błędu w htmlu. Powiadomienie o błędzie wysłane na e-mail.
	 *
	 * @param array $errContext Dodatkowe informacji o błędzie, jeżeli są potrzebne do przekazania
	 * @return string
	 */
	public function getExtendedMessage($errContext=array()) {
		$retArray = array(
			'success' => false,
			'message' => $this->getMessage()
		);

		$_file = $this->getFile();
		$_line = strval($this->getLine());
		$_trace = MK_Error::getExtendedTrace($this);

		$mkDb = new MK_Db_PDO();
		if(is_object($mkDb)) {
			$mkDb->transFail();
			$dbError = $mkDb->getErrorMsg();
			if (empty($dbError)) {
				$debugMsg = MK_Error::handler(E_NOTICE, $retArray['message'], $_file, $_line, $errContext, $_trace);
			} else {
				$debugMsg = MK_Error::getDataBase($dbError, $_file, $_line, $_trace);
			}
		}

		$retArray['debug'] = (DEVELOPER === true) ? $debugMsg : '';

		if (MK::isAjaxExecution(true)) {
			return json_encode($retArray);
		}

		return $retArray[ (DEVELOPER === true) ? 'debug' : 'message' ];
	}

}