<?php

/**
 * MK_Registry
 *
 * Klasa oparta na wzoru Rejestr
 *
 * @category MK
 * @package	MK_Registry
 * @author lwinnicki
 */
class MK_Registry extends ArrayObject {

	/**
	 * @var mixed
	 */
	private static $registry = null;

    /**
     *
     * @param array $array
     * @param int $flags
     * @internal param $ #P#C\ArrayObject.ARRAY_AS_PROPS|\type|? $flags
     */
	public function __construct(array $array = array(), $flags = parent::ARRAY_AS_PROPS) {
		parent::__construct($array, $flags);
	}

	/**
	 * Zwraca instancję reestru
	 * 
	 * @return MK_Registry
	 */
	public static function getInstance() {
		if (self::$registry === null) {
			self::setInstance(new MK_Registry());
		}
		return self::$registry;
	}

	/**
	 * Tworzy instancję Rejestru
	 *
	 * @param MK_Registry $registry
	 * @throws MK_Exception
	 */
	public static function setInstance(MK_Registry $registry) {
		if (self::$registry !== null) {
			throw new MK_Exception('Rejestr już jest utworzony');
		}
		self::$registry = $registry;
	}

	/**
	 * Zwraca wartość, o podanej nazwie, zapisaną w rejestrze.
	 * Jeżeli nie istnieje zwraca wyjątek
	 *
	 * @param String	 $index
	 * @throws MK_Exception
	 * @return Mixed
	 */
	public static function get($index) {
		$instance = self::getInstance();

		if (!$instance->offsetExists($index)) {
			throw new MK_Exception('Nie istnieje wartość dla klucza ' . $index);
		}
		return $instance->offsetGet($index);
	}

	/**
	 * Wstawia podaną wartość do rejestru o podanym kluczu
	 *
	 * @param String	 $index
	 * @param Mixed	   $value
	 */
	public static function set($index, $value) {
		$instance = self::getInstance();
		$instance->offsetSet($index, $value);
	}

	/**
	 * Sprawdza czy istnieje wartość o podanym indeksie
	 *
	 * @param String	 $index
	 * @return Boolean
	 */
	public static function isRegistered($index) {
		if (self::$registry === null) {
			return false;
		}
		return self::$registry->offsetExists($index);
	}

    /**
     * (non-PHPdoc)
     * @see ArrayObject::offsetExists()
     * @param $index
     * @return bool
     */
	public function offsetExists($index) {
		return array_key_exists($index, $this);
	}

}