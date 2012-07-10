<?php

/**
 * MK_Soap_AutoDiscover
 *
 * Klasa do automatycznego genewowania wsdl'i na podstawie klas PHP, oraz do tworzenia Soap server
 *
 * @category    MK_Soap
 * @package        MK_Soap_AutoDiscover
 * @author        bskrzypkowiak
 */
Class MK_Soap_AutoDiscover extends Zend_Soap_AutoDiscover
{

	/**
	 * Nazwa instancji webservice
	 * @var String
	 */
	private $_serviceInstance;

	/**
	 * Mapowanie nazwy klasy na nazwy w WSDL'u
	 * @var array\null
	 */
	private $_classMap = null;

	/**
	 * Tablica z opcjami instancji serwera
	 * @var array\null
	 */
	private $_serverOptions = null;

	/**
	 * Target namespace przechopwywany w <port>
	 * @var null
	 */
	private $_targetNamespace = null;

	/**
	 * Strategia do generowania WSDL'a
	 * @var string
	 */
	public $strategy = 'Zend_Soap_Wsdl_Strategy_ArrayOfTypeSequence';

	/**
	 * Konstruktor sprawdza czy są podane wymagane dane w _GET i uruchamia mechanizm generowania wsdl'a
	 *
	 * @throws MK_Exception
	 * @return MK_Soap_AutoDiscover
	 */
	public function __construct()
	{

		$this->setParamsFromEnv();

		if (!isset($_GET['instance'])) {
			throw new MK_Exception("Brak podanej instancji serwera");
		}
		$this->setInstanceName();

		if (property_exists($this->_serviceInstance, 'classmap') === true) {
			$classmap = new ReflectionProperty($this->_serviceInstance, 'classmap');
			$classmap = $classmap->getValue();
			//alternatywne rozwiazanie:
//			$vars = get_class_vars($this->_serviceInstance);
//			$classmap = $vars['classmap'];
			if (is_array($classmap)) {
				$this->setServerOptions($classmap);
			}
		}

		return $this;
	}

	/**
	 * Uruchamia wsdl/soapws
	 *
	 * @param array $classMap
	 * @param string $namespace
	 * @param null $uri
	 * @param null $wsdlClass
	 */
	public function startService(array $classMap = array(), $namespace = '', $uri = null, $wsdlClass = null)
	{
		$this->setClassMap($classMap);

		if (isset($_GET['wsdl'])) {
			$this->handleWSDL($this->strategy, $namespace, $uri, $wsdlClass);
		}

		$this->handleSOAP($namespace);
	}

	/**
	 * Zwraca uri zawierajacego instancje oraz proźbę o wsdl'a
	 *
	 * @param Bool $wsdl
	 *
	 * @return String
	 */
	public function getUri($wsdl = false)
	{
		return parent::getUri() . (($wsdl) ? "?wsdl&instance=" . $this->_serviceInstance : '');
	}

	/**
	 * Ustawia target Namespace
	 *
	 * @param String $tns
	 *
	 * @return \MK_Soap_AutoDiscover
	 */
	public function setTargetNamespace($tns)
	{
		$this->_targetNamespace = $tns;
		return $this;
	}

	/**
	 * Zwraca ustawiony targetnamespace lub standardowy jezeli nie jest ustawiony
	 *
	 * @return string
	 */
	private function getTargetNamespace()
	{
		if ($this->_targetNamespace === null) {
			return $this->getUri() . "?instance=" . $this->_serviceInstance;
		}
		return $this->_targetNamespace;
	}

	/**
	 * Ustawiamy rodzaj webservice
	 *
	 * @param String|null $instance
	 */
	public function setInstanceName($instance = null)
	{

		if (empty($_GET['instance']) && !empty($_ENV['instance'])) {
			$_GET['instance'] = $_ENV['instance'];
		}
		if ($instance === null && empty($_GET['instance'])) {
			return;
		}
		if ($instance === null && !empty($_GET['instance'])) {
			$instance = $_GET['instance'];
		}

		$this->_serviceInstance = $instance;
	}

	/**
	 * Jeżeli nie ma podanych getów a w apache2 ustawiono ENV dla instance i wsdl
	 * to przenosi te ustawienia z ENV do GET
	 */
	private function setParamsFromEnv()
	{
		$instance = getenv('instance');
		$wsdl = getenv('wsdl');

		if ($instance !== false) {
			$_GET['instance'] = $instance;
		}
		if ($wsdl !== false) {
			$_GET['wsdl'] = $wsdl;
		}
	}

	/**
	 * Ustawia opcje do uruchamiania instancji serwera
	 *
	 * @param array $options
	 *
	 * @return MK_Soap_AutoDiscover
	 */
	public function setServerOptions(array $options)
	{
		$this->_serverOptions = $options;
		return $this;
	}

	/**
	 * Ustawia właściwość zawierającą tablice z mapowaniem klas
	 *
	 * @param array $map
	 */
	public function setClassMap(array $map)
	{
		$this->_classMap = $map;
	}

	/**
	 * Zwraca tablice z lista zmapowanych klas na nazwy w wsdlu
	 *
	 * @return array\null
	 */
	private function getClassMap()
	{
		return $this->_classMap;
	}

	/**
	 * Zwraca nazwe klasy serwera, zmapowana na wartość jeżeli jest ustawiona $_classMap
	 *
	 * @return string
	 */
	private function getMapedClass()
	{
		if (!empty($this->_classMap) && array_key_exists($this->_serviceInstance, $this->_classMap)) {
			return $this->_classMap[$this->_serviceInstance];
		}
		return $this->_serviceInstance;
	}


	/**
	 * Tworzy instancje i stara się obsłużyć rządanie
	 *
	 * @param String $namespace
	 */
	private function handleSOAP($namespace)
	{
		$soap = new Zend_Soap_Server($this->getUri(true), $this->_serverOptions);
		$soap->setClass($namespace . $this->getMapedClass());
		$soap->handle(null);
		die;
	}

	/**
	 * Tworzy i zwraca WSDL'a na podstawie klasy o nazwie branej z
	 *
	 * @param $strategy
	 * @param $namespace
	 * @param $uri
	 * @param $wsdlClass
	 */
	function handleWSDL($strategy, $namespace, $uri, $wsdlClass)
	{
		parent::__construct($strategy, $uri, $wsdlClass);
		$this->setClass($this->getMapedClass(), $namespace);
		$this->handle();
		die;
	}

	/**
	 * Set the Class the SOAP server will use
	 *
	 * @param string $class Class Name
	 * @param string $namespace Class Namspace - przestrzeń nazw w której jest klasa instancji serwera
	 * @param array $argv Arguments to instantiate the class - Not Used
	 *
	 * @return Zend_Soap_AutoDiscover
	 */
	public function setClass($class, $namespace = '', $argv = null)
	{

		$wsdl = new $this->_wsdlClass($class, $this->getUri(), $this->_strategy);

		// The wsdl:types element must precede all other elements (WS-I Basic Profile 1.1 R2023)
		$wsdl->addSchemaTypeSection();

		$port = $wsdl->addPortType($class . 'Port');
		$binding = $wsdl->addBinding($class . 'Binding', 'tns:' . $class . 'Port');

		$wsdl->addSoapBinding($binding, $this->_bindingStyle['style'], $this->_bindingStyle['transport']);
		$wsdl->addService($class . 'Service', $class . 'Port', 'tns:' . $class . 'Binding', $this->getTargetNamespace());

		foreach ($this->_reflection->reflectClass($namespace . $class)->getMethods() as $method) {
			$this->_addFunctionToWsdl($method, $wsdl, $port, $binding);
		}
		$this->_wsdl = $wsdl;

		return $this;
	}
}
