<?php
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
App::uses('CakeDocument', 'MongoCake.Model');

/** @ODM\Document */
class AmazonServer extends CakeDocument {

/**
 * Which datasource configuration to use for connecting to Amazon
 *
 * @var string
 */
	public static $useAmazonConfig = 'aws';

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
 * The instance type
 *
 * @ODM\String
 * @var string
 */
	public $instanceType;

/**
 * Public IP Address
 *
 * @ODM\String
 * @var string
 */
	public $ipAddress;

/**
 * Public DNS
 *
 * @ODM\String
 * @var string
 */
	public $dnsName;

/**
 * Server instance state
 *
 * @ODM\String
 * @var string
 */
	public $instanceState;

/**
 * Root device type
 *
 * @ODM\String
 * @var string
 */
	public $rootDeviceType;

/**
 * Time the instance was launched
 *
 * @ODM\Date
 * @var DateTime
 */
	public $launchTime;

/**
 * Options for running
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

	public static $findMethods = array(
		'instances' => true
	);

/**
 * Start a new instance
 *
 * @return CFSimpleXML Response object
 */
	public function run() {
		if (!$this->imageId) {
			throw new CakeException('AmazonServer has no Image ID');
		}
		return static::getAmazonSource()->run($this, $this->options);
	}

/**
 * Gets a list of all available instances
 *
 * @return array
 */
	protected static function _findInstances($state, $query) {
		if ($state === 'before') {
			if (!empty($query['refresh'])) {
				static::getAmazonSource()->instances(array('refreshOnly' => true));
				static::getDataSource()->commit();
			}
		}
		return $query;
	}

/**
 * Terminates a group of instances by id
 *
 * @return CFSimpleXML Response Object
 */
	public static function terminateAll(array $ids) {
		if (empty($ids)) {
			throw new CakeException('No instances specified to be terminated');
		}

		return static::getAmazonSource()->terminate($ids);
	}

/**
 * Terminates the instance
 *
 * @return CFSimpleXML Response Object
 */
	public function terminate() {
		if (!$this->instanceId) {
			throw new CakeException('AmazonServer has no instance Id');
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
			throw new CakeException('AmazonServer has no instance Id');
		}
		return static::getAmazonSource()->start($this);
	}

/**
 * Pause the instance Only works for EBS backed instances
 *
 * @return boolean True if the operation was a success
 */
	public function stop() {
		if (!$this->instanceId) {
			throw new CakeException('AmazonServer has no instance Id');
		}
		return static::getAmazonSource()->stop($this);
	}

/**
 * Reboot an instance
 *
 * @return boolean True if the operation was successful
 */
	public function reboot() {
		if (!$this->instanceId) {
			throw new CakeException('AmazonServer has no instance Id');
		}
		return static::getAmazonSource()->reboot($this);
	}

/**
 * Get an EC2 Object to work work, and set its region if available.
 *
 * @return AmazonEC2
 */
	protected static function getAmazonSource() {
		return ConnectionManager::getDataSource(static::$useAmazonConfig);
	}
}