<?php

/**
 * MK_Crypt_Certificate
 *
 * Klasa do obsługi certyfikatów
 *
 * @category    MK_Crypt
 * @package        MK_Crypt_Certificate
 * @author        mkozlowski
 *
 */
Class MK_Crypt_Certificate
{

	/**
	 * Tablica przechowująca dane certyfikatu po jego przeparsowaniu
	 * @var array
	 */
	private $certificate;

	/**
	 * Wartość certyfikatu zapisana w postaci PEM
	 * @var string
	 */
	private $pemCertificate;

	/**
	 * Przeparsowuje certyfikat oraz ustawia wartość PEM'ową
	 *
	 * @param $cert
	 *
	 * @return MK_Crypt_Certificate
	 * @throws Exception
	 */
	public function set($cert)
	{
		$this->certificate = openssl_x509_parse($cert);

		if ($this->certificate === false) {
			throw new Exception('Nie udało się zczytać certyfikatu');
		}

		$this->pemCertificate = $cert;

		return $this;
	}

	/**
	 * Sprawdza czy dla podanego czasu (timestamp) certyfikat jest jeszcze ważny.
	 * Domyślnie sprawdza dla momentu wywołania metody.
	 *
	 * @param $time
	 *
	 * @return bool
	 */
	public function validate($time = null)
	{

		if (is_null($time)) {
			$time = time();
		}

		if ($time <= $this->certificate['validFrom_time_t']) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Zwraca pełną tablice z informacjami o certyfikacie.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return $this->certificate;
	}

	/**
	 * Zwraca czas od kiedy jest ważny certyfikat. Domyślna forma to timestamp.
	 * Można podać format w jakim wartość ma zostać zwrócona.
	 *
	 * @param string $format format musi być zgodny z funkcją date()
	 *
	 * @return string
	 */
	public function getValidateFrom($format = null)
	{
		if (is_null($format)) {
			return $this->certificate['validFrom_time_t'];
		}
		else {
			return date($format, $this->certificate['validFrom_time_t']);
		}
	}

	/**
	 * Zwraca czas do kiedy jest ważny certyfikat. Domyślna forma to timestamp.
	 * Można podać format w jakim wartość ma zostać zwrócona.
	 *
	 * @param string $format format musi być zgodny z funkcją date()
	 *
	 * @return string
	 */
	public function getValidateTo($format = null)
	{
		if (is_null($format)) {
			return $this->certificate['validTo_time_t'];
		}
		else {
			return date($format, $this->certificate['validTo_time_t']);
		}
	}

	/**
	 * Domyślnie zwraca publiczny klucz gotowy do użycia.
	 * Jeśli detail ustawiony na true to zwraca szczegóły dotyczące klucza publicznego.
	 * Jeśli detail ustawiony na klucz tablicy to zwraca konkretną wartość.
	 *
	 * @param string $detail
	 *
	 * @return resource
	 */
	public function getPublicKey($detail = null)
	{
		$key = openssl_pkey_get_public($this->pemCertificate);
		if (is_null($detail)) {
			return $key;
		}
		else {
			$details = openssl_pkey_get_details($key);
			if ($detail === true) {
				return $details;
			}
			else {
				return $details[$detail];
			}
		}
	}

	/**
	 * Zwraca informacje dotyczące wystawcy w postaci tablicy albo wartość dla podanego klucza.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getIssuer($key = null)
	{
		if (is_null($key)) {
			return $this->certificate['issuer'];
		}
		else {
			return $this->certificate['issuer'][$key];
		}
	}

	/**
	 * Zwraca informacje dotyczące podmiot w postaci tablicy albo wartość dla podanego klucza.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getSubject($key = null)
	{
		if (is_null($key)) {
			return $this->certificate['subject'];
		}
		else {
			return $this->certificate['subject'][$key];
		}
	}

	/**
	 * Zwraca numer seryjny certyfikatu
	 *
	 * @return string
	 */
	public function getSerial()
	{
		return $this->certificate['serialNumber'];
	}

	/**
	 * Zwraca numer wersji certyfikatu
	 *
	 * @return integer
	 */
	public function getVersion()
	{
		return $this->certificate['version'];
	}

	/**
	 * Zwraca ile jeszcze czasu zostało do wygaśnięcia certyfikatu.
	 * Domyślnie sprawdza, to dla czasu w którym wywoływana jest metoda
	 * i zwraca ilość w dniach.
	 *
	 * @param int $timestamp data
	 * @param string $type format odpowiedzi, domyślnie "d" - w dniach
	 *
	 * @return int
	 * @throws Exception
	 */
	public function getTimeToExpire($timestamp = null, $type = 'd')
	{
		//TODO: obsłużyć inne przypadki czasu np. w - tygodnie, m - miesiące
		if (is_null($timestamp)) {
			$timestamp = time();
		}

		if (is_int($timestamp)) {
			$servedTypes = array('d');
			if (in_array($type, $servedTypes)) {
				$pattern = '#\..*#';
				switch ($type) {
					case 'd':
						$score = ($this->certificate['validTo_time_t'] - $timestamp) / (24 * 60 * 60);
						return (int)preg_replace($pattern, '', $score);
				}
			}
			else {
				throw new Exception('Przekazany typ nie został obsłużony');
			}
		}
		else {
			throw new Exception('Został przekazany błędny parametr');
		}
	}

}
