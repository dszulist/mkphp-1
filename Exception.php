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
	 * Rozbudowany raport błędu dla MK_Exception i MK_Db_Exception.
	 * Zapisanie zdarzenia w pliku tekstowym i wysłanie do logs.madkom.pl (dla developer:false)
	 *
	 * try {
	 *	// code
	 * } catch (MK_Db_Exception $e) {
	 *	//MK_Error::setMoreTraceIgnorePath(array('Spirb->loadModule'));
	 *	die($e->getExtendedMessage());
	 * } catch (MK_Exception $e) {
	 *	die($e->getExtendedMessage());
	 * }
	 *
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

		$retArray['debug'] = (MK_DEVELOPER === true) ? '<pre>' . $debugMsg . '</pre>' : '';

		if (MK::isAjaxExecution(true)) {
			return json_encode($retArray);
		}

		return $retArray[(MK_DEVELOPER === true) ? 'debug' : 'message'];
	}

}