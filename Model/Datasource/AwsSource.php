<?php

class AwsSource {

/**
 * Holds the config for communicating with Amazon Web Services
 *
 * @var array
 */
	protected $_config = array(
		'AWS_KEY'            => '',
		'AWS_SECRET_KEY'     => '',
		'AWS_ACCOUNT_ID'     => '',
		'AWS_CANONICAL_ID'   => '',
		'AWS_CANONICAL_NAME' => '',
		'AWS_CERTIFICATE_AUTHORITY' => false,
		'AWS_DEFAULT_CACHE_CONFIG'  => '',
		'AWS_MFA_SERIAL'            => '',
		'AWS_CLOUDFRONT_KEYPAIR_ID' => '',
		'AWS_CLOUDFRONT_PRIVATE_KEY_PEM' => '',
		'AWS_ENABLE_EXTENSIONS' => 'false',
	);

/**
 * Name of the model class to use for hydrating the server instances
 *
 * @var string
 */
	public $documentClass = 'Ec2.AmazonServer';

/**
 * Converts the configuration array into constants, this is required for using
 * the Amazon SDK
 *
 * @param array $config 
 */
	public function __construct($config = array()) {
		$this->setConfig($config);
		foreach ($this->_config as $key => $value) {
			!defined($key) && define($key, $value);
		}
	}

/**
 * Sets the configuration for the DataSource.
 * Merges the $config information with the _baseConfig and the existing $config property.
 *
 * @param array $config The configuration array
 * @return void
 */
	protected function setConfig($config = array()) {
		$this->_config = array_merge($this->_config, $config);
	}

/**
 * Returns an instance of a AmazonEc2 class for communicating with Amazon AWS
 *
 * @param string $region default region for the object
 * @return AmazonEc2
 */
	protected function getEC2Object($region = null) {
		if (!class_exists('AmazonEc2', false)) {
			require_once(App::pluginPath('Ec2') . 'Vendor/AWSSDKforPHP/sdk.class.php');
		}
		$object = new AmazonEc2();
		if ($region) {
			$object->set_region($region);
		}
		return $object;
	}

/**
 * Converts a response from Amazon into Model objects by filling their properties
 * or updating them
 *
 * @param array $instances 
 * @return array of objects
 */
	protected function hydrateSet($instances) {
		$class = $this->loadDocumentClass();
		$documents = array();
		foreach ($instances->item as $item) {
			$instance = $class::find('all', array(
				'conditions' => array('instanceId' => (string) $item->instanceId)
			))->findAndUpdate()->refresh()
				->field('instanceState')->set((string) $item->instanceState->name)
				->field('ipAddress')->set((string) $item->ipAddress)
				->field('dnsName')->set((string) $item->dnsName)
				->field('rootDeviceType')->set((string) $item->rootDeviceType)
			->getQuery()->execute();

			if (!$instance) {
				$instance = $this->hydrate($item, $class);
			}

			$documents[] = $instance;
		}
		return $documents;
	}

/**
 * Creates a new Model object out of an item extracted from an Amazon server response
 *
 * @param SimpleXMLElement $item
 * @param string $class name of the model class to be used for hydration
 * @return object instance of a Model class
 */
	protected function hydrate($item, $class) {
		$document = $class::create(array(
			'instanceId' => (string) $item->instanceId,
			'instanceState' => (string) $item->instanceState->name,
			'ipAddress' => (string) $item->ipAddress,
			'dnsName' => (string) $item->dnsName,
			'imageId' => (string) $item->imageId,
			'region' => (string) $item->placement->availabilityZone,
			'instanceType' => (string) $item->instanceType,
			'rootDeviceType' => (string) $item->rootDeviceType,
			'launchTime' => empty($item->launchTime) ? null : new DateTime((string) $item->launchTime)
		));
		$document->save();
		return $document;
	}

/**
 * Loads the class that will represent the server instances when hydrated
 * and returns the classname
 *
 * @return string
 */
	protected function loadDocumentClass() {
		list($plugin, $class) = pluginSplit($this->documentClass, true);
		App::uses($class, $plugin . 'Model');
		return $class;
	}

	protected function updateServerProperty($property, $instances, $path = null) {
		$class = $this->loadDocumentClass();
		$documents = array();
		if (!$path) {
			$path == $property;
		}
		foreach ($instances->item as $instance) {
			$value = $instance->xpath($path);
			if (!empty($value[0])) {
				$value = (string) $value[0];
			}
			$server = $class::find('first', array(
				'conditions' => array('instanceId' => (string) $instance->instanceId)
			));
			if ($server) {
				$server->{$property} = $value;
				$server->save();
				$documents[] = $server;
			}
		}
		return $documents;
	}

/**
 * Auxiliary function to bridge calls to the Amazon SDK
 *
 * @param string $action name of the action to perform, it will be suffixed internally with `_instances`
 * @param array $servers accepts a list of server instances as model objects, or a list of instanceId strings
 * @return SimpleXmlElement the response from the server
 * @throws CakeException if the Amazon API does not responds with a good http status
 */
	public function execute($action, $servers) {
		$isDocument = false;
		if (!is_array($servers)) {
			$servers = array($servers);
		}
		if (is_object(current($servers))) {
			$isDocument = true;
			$ids = Set::extract($servers, '{s}.instanceId');
		} else {
			$ids = $servers;
		}
		$response = $this->getEC2Object()->{$action . '_instances'}($ids);
		if (!$response->isOK()) {
			throw new CakeException(
				$this->_errorMessage('Failed to ' . $action . ' instance(s) ' . implode(', ', $ids), $response)
			);
		}
		return $response->body;
	}

/**
 * Returns a array with all created server instances
 *
 * @param array $options array with options for this method. Accepts the following:
 *
 *	- refreshOnly: if set to true, this function will not return any data, it will just refresh
 *		the properties on the Model classes or create the new ones if necessary.
 *
 * @return array of objects representing each server instance
 */
	public function instances($options = array()) {
		$response = $this->getEC2Object()->describe_instances();
		if (!$response->isOK()) {
			throw new CakeException($this->_errorMessage('Failed to describe instances', $response));
		}
		$documents = array();
		foreach ($response->body->reservationSet->item as $i) {
			$documents = array_merge($documents, $this->hydrateSet($i->instancesSet));
		}
		if (!empty($options['refreshOnly'])) {
			return $documents;
		}
	}

/**
 * Creates and starts server instances
 *
 * @param Object $server model class representing the Server instance and its options
 * @param array $options options to be passed to the `run_instances` call
 * @param int $min minimum number of servers to start, if it is not possible to start this number the call will fail
 * @param int $max hard limit for the number of servers that should be started with this config
 * @return array of object instances representing each server created
 * @throws CakeException if something goes wrong communicating with Amazon
 */
	public function run($server, $options = array(), $min = 1, $max = 1) {
		$response = $this->getEC2Object($server->region)->run_instances(
			$server->imageId,
			$min,
			$max,
			$options
		);
		if (!$response->isOK()) {
			throw new CakeException($this->_errorMessage('Failed to run instance', $response));
		}
		$documents = $this->hydrateSet($response->body->instancesSet);
		return $documents;
	}


/**
 * Terminates server instances
 *
 * @param array $servers accepts a list of server instances as model objects, or a list of instanceId strings
 * @return void
 */
	public function terminate($servers) {
		$response = $this->execute('terminate', $servers);
		return $this->updateServerProperty('instanceState', $response->instancesSet, 'currentState/name');
	}

/**
 * Start the server instances
 *
 * @param string $servers 
 * @return void
 * @author Jose Lorenzo Rodriguez
 */
	public function start($servers) {
		$response = $this->execute('start', $servers);
		debug($response->asXML());
		return $this->updateServerProperty('instanceState', $response->instancesSet, 'currentState/name');
	}

	public function stop($servers) {
		$response = $this->execute('stop', $servers);
		return $this->updateServerProperty('instanceState', $response->instancesSet);
	}

	public function reboot($servers) {
		$response = $this->execute('reboot', $servers);
		return $this->updateServerProperty('instanceState', $response->instancesSet);
	}

/**
 * Generate an error message with the supplied string and CFResponse object
 *
 * @param string $message Message
 * @param CFReponse $response CFResponse from Amazon
 * @return string Error message
 */
	protected function _errorMessage($message, $response) {
		return $message . "\n" . (string) $response->body;
	}
}