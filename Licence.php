<?php

/**
 * MK_Licence
 *
 * Klasa do zarzadzania i obsługi licencjami aplikacji
 *
 * @category	MK
 * @package		MK_Licence
 * @throws		MK_Exception
 * @author		bskrzypkowiak
 */
class MK_Licence {

	/**
	 * Sprawdanie licencji
	 *
	 * @param String $licence
	 * @param String $statusInconsistencyLicenseKey
	 * @return type
	 */
	function verify($licence, $statusInconsistencyLicenseKey) {
		if (MK_DEVELOPER === true) {
			return true;
		}

		$expireDate = substr($licence, 0, 4) . '-' . substr($licence, 4, 2) . '-' . substr($licence, 6, 2);

		if (!$this->isValidSignature($licence)
				|| ( $statusInconsistencyLicenseKey == 'stop_application' && strtotime($expireDate) < strtotime(date('Y-m-d')))) {
			throw new MK_Exception('Błąd krytyczny. Niezgodna sygnatura licencji! <br/> Skontaktuj się z administratorem.');
		}
	}

	/**
	 * Sprawdzanie czy jest aktywne wsparcie techniczne, oraz czy jest poprawna sygnatura licencji
	 *
	 * @param String $taskListPathFile
	 * @param String $licence
	 * @throws MK_Exception
	 */
	function canUpgrade($taskListPathFile, $licence) {
		if (!empty($taskListPathFile)) {

			if ($this->isSupportActive($licence)) {
				throw new MK_Exception('Wygasło wsparcie techniczne. Proszę o kontakt z administratorem');
			}

			if (!$this->isValidSignature($licence)) {
				throw new MK_Exception('Błąd krytyczny. Niezgodna sygnatura licencji!');
			}
		}
	}

	/**
	 * Sprawdza czy jest aktywne wsparcie techniczne dla daty umieszocznej w kluczu licencyjnym
	 *
	 * @param String $licence
	 * @return Boolean
	 */
	function isSupportActive($licence) {
		$expireDate = substr($licence, 0, 4) . '-' . substr($licence, 4, 2) . '-' . substr($licence, 6, 2);
		return (strtotime($expireDate) < strtotime(date('Y-m-d')));
	}

	/**
	 * Sprawdza popeawność sygnatury licencji
	 *
	 * @param String $licence
	 * @return Boolean
	 */
	function isValidSignature($licence) {
		$expireDate = substr($licence, 0, 4) . '-' . substr($licence, 4, 2) . '-' . substr($licence, 6, 2);
		//20121231.md5($expireDate . ' ' . exec('hostname') . ' ' . APP_PATH);
		$validLicence = str_replace('-', '', $expireDate) . md5($expireDate . ' ' . exec('hostname') . ' ' . APP_PATH);

		return ((strlen($licence) == 40 && strcmp($validLicence, $licence) == 0));
	}

}