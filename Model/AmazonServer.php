<?php
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

// App::import('Ec2.Vendor', 'CFRuntime', array('file' => App::pluginPath('Ec2') . 'Vendor/AWSSDKforPHP/sdk.class.php'));
// App::import('Ec2.Vendor', 'AmazonEC2', array('file' => App::pluginPath('Ec2') . 'Vendor/AWSSDKforPHP/services/ec2.class.php'));

require(App::pluginPath('Ec2') . 'Vendor/AWSSDKforPHP/sdk.class.php');
//require(App::pluginPath('Ec2') . 'Vendor/AWSSDKforPHP/services/ec2.class.php');

App::uses('CakeDocument', 'MongoCake.Model');

/** @ODM\Document */
class AmazonServer extends CakeDocument {

/**
 * Server Id
 *
 * @ODM\Id
 * @var string
 */
	private $id;

/**
 * Amazon assigned instance ID
 *
 * @ODM\String
 * @var string
 */
	public $instanceId;

/**
 * Region in which the instance operates
 *
 * @ODM\String
 * @var string
 */
	public $region;

/**
 * The Amazon Image ID (AMI) to use
 *
 * @ODM\String
 * @var string
 */
	public $imageId;

/**
 * Minimum number of instances to start
 *
 * @ODM\Int
 * @var int
 */
	public $minimum = 1;
	
/**
 * Maximum number of instances to start
 *
 * @ODM\Int
 * @var int
 */
	public $maximum = 1;

/**
 * Public IP Address (DNS)
 *
 * @ODM\String
 * @var string
 */
	public $ipAddress;

/**
 * Run options
 *
 * @ODM\Hash
 * @var array
 */
	public $options = array();

/**
 * Creation date and time
 *
 * @ODM\Date
 * @var DateTime
 */
	public $created;

/**
 * Modified date and time
 *
 * @ODM\Date
 * @var DateTime
 */
	public $modified;


/**
 * Start a new instance
 *
 * @return CFSimpleXML Response object
 */
	public function run() {
		if (!$this->imageId) {
			throw new EC2_Exception('AmazonServer has no Image ID');
		}
		$ec2 = $this->_getEC2Object($this->region);
		$response = $ec2->run_instances(
			$this->imageId,
			$this->minimum,
			$this->maximum,
			$this->options
		);
		if (!$response->isOK()) {
			throw new EC2_Exception($this->_errorMessage('Failed to run instance', $response));
		}

		return $response->body;
	}

/**
 * Get a list of all available instances
 *
 * @return CFSimpleXML Response Object
 * @todo Replace this with a find() implementation
 */
	public static function instances() {
		$response = static::_getEC2Object()->describe_instances();
		if (!$response->isOK()) {
			throw new EC2_Exception($this->_errorMessage('Failed to describe instances', $response));
		}

		return $response->body->reservationSet->item;
	}

/**
 * Terminates a group of instances by id
 *
 * @return CFSimpleXML Response Object
 */
	public static function terminateAll(array $ids) {
		if (empty($ids)) {
			throw new EC2_Exception('No instances specified to be terminated');
		}

		$ec2 = static::_getEC2Object();
		$response = $ec2->terminate_instances($ids);
		if (!$response->isOK()) {
			throw new EC2_Exception(static::_errorMessage('Failed to terminate instance(s) ' . implode(', ', $ids), $response));
		}
		
		return $response->body;
	}

/**
 * Terminates the instance
 *
 * @return CFSimpleXML Response Object
 */
	public function terminate() {
		if (!$this->instanceId) {
			throw new EC2_Exception('AmazonServer has no instance Id');
		}
		if (empty($ids)) {
			$ids = array($this->instanceId);
		}
		return $this->terminateAll(array($this->instanceId));
	}

/**
 * Resume a paused (stopped) instance. Only works for EBS backed instances
 *
 * @return CFSimpleXML Object
 */
	public function start() {
		if (!$this->instanceId) {
			throw new EC2_Exception('AmazonServer has no instance Id');
		}
		$ec2 = $this->_getEC2Object($this->region);
		$response = $ec2->start_instances($this->instanceId);
		if (!$response->isOK()) {
			throw new EC2_Exception('Failed to start Amazon EC2 instance');
		}
		
		return $response->body;
	}

/**
 * Pause the instance Only works for EBS backed instances
 *
 * @return boolean True if the operation was a success
 */
	public function stop() {
		if (!$this->instanceId) {
			throw new EC2_Exception('AmazonServer has no instance Id');
		}
		$ec2 = $this->_getEC2Object($this->region);
		$response = $ec2->stop_instances($this->instanceId);
		return $response->isOK();
	}

/**
 * Reboot an instance
 *
 * @return boolean True if the operation was successful
 */
	public function reboot() {
		if (!$this->instanceId) {
			throw new EC2_Exception('AmazonServer has no instance Id');
		}
		$ec2 = $this->_getEC2Object($this>region);
		$response = $ec2->reboot_instances($this->instanceId);
		return $response->isOK();
	}

/**
 * Get an EC2 Object to work work, and set its region if available.
 *
 * @return AmazonEC2
 */
	protected static function _getEC2Object($region = null) {
		$ec2 = new AmazonEC2();
		if (!empty($region)) {
			$ec2->set_region($region);
		}
		return $ec2;
	}

/**
 * Generate an error message with the supplied string and CFResponse object
 *
 * @param string $message Message
 * @param CFReponse $response CFResponse from Amazon
 * @return string Error message
 */
	protected static function _errorMessage($message, CFReponse $response) {
		return $message . "\n" . $response->toString();
	}
}
