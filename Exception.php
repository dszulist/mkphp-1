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
	 * @return string
	 */
	public function getExtendedMessage() {
		$retArray = array(
			'success' => false,
			'message' => $this->getMessage()
		);

		$_file = $this->getFile();
		$_line = strval($this->getLine());
		$_trace = MK_Error::getExtendedTrace($this);

		$mkDb = new MK_Db_PDO();
		if (is_object($mkDb)) {
			$mkDb->transFail();
			$dbError = $mkDb->getErrorMsg();
			if (empty($dbError)) {
				$debugMsg = MK_Error::fromException($retArray['message'], $_file, $_line, $_trace);
			} else {
				$debugMsg = MK_Error::fromDataBase($dbError, $_file, $_line, $_trace);
			}
		}

		$retArray['debug'] = (MK_DEVELOPER === true) ? $debugMsg : '';

		if (MK::isAjaxExecution(true)) {
			return json_encode($retArray);
		}

		return $retArray[(MK_DEVELOPER === true) ? 'debug' : 'message'];
	}

}