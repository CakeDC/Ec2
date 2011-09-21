<?php
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

App::import('Vendor', 'CFRuntime', null, null, 'AWSSDKforPHP/sdk.class.php');
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
	public $options;

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
 * Constructor
 *
 */
	public function __construct() {
		$this->options = array();
	}

/**
 * Start a new instance
 *
 * @return boolean True if the operation was a success
 */
	public function run() {
		if (!$this->imageId) {
			throw new EC2_Exception('AmazonServer has no Image ID');
		}
		$ec2 = $this->_getEC2Object();
		$response = $ec2->run_instances(
			$this->imageId,
			$this->minimum,
			$this->maximum,
			$this->options
		);
		return $this->_amazonResponseOK($response);
	}

/**
 * Get a list of all available instances
 *
 * @return void
 * @todo Replace this with a find() implementation
 */
	public function describe() {
		if (!$this->instanceId) {
			throw new EC2_Exception('AmazonServer has no instance Id');
		}
		$ec2 = $this->_getEC2Object();
		$response = $ec2->describe_instances();
		var_dump($response);
		return $this->_amazonResponseOK($response);
	}

/**
 * Resume a paused (stopped) instance. Only works for EBS backed instances
 *
 * @return boolean True if the operation was a success
 */
	public function start() {
		if (!$this->instanceId) {
			throw new EC2_Exception('AmazonServer has no instance Id');
		}
		$ec2 = $this->_getEC2Object();
		$response = $ec2->start_instances($this->instanceId);
		return $this->_amazonResponseOK($response);
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
		$ec2 = $this->_getEC2Object();
		$response = $ec2->stop_instances($this->instanceId);
		return $this->_amazonResponseOK($response);
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
		$ec2 = $this->_getEC2Object();
		$response = $ec2->reboot_instances($this->instanceId);
		return $this->_amazonResponseOK($response);
	}

/**
 * Determine if the response from Amazon was "Ok"
 *
 * @param CFResponse $response Response object
 * @return boolean True if success
 */
	protected function _amazonResponseOk(CFResponse $response) {
		return $response->isOK();
	}

/**
 * Get an EC2 Object to work work, and set its region if available.
 *
 * @return AmazonEC2
 */
	protected function _getEC2Object() {
		$ec2 = new AmazonEC2();
		if (!empty($this->region)) {
			$ec2->set_region($this->region);
		}
		return $ec2;
	}
}
