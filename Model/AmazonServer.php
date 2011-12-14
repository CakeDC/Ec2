<?php
/**
 * Copyright 2011, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2011, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

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
	protected $instanceId;

/**
 * Region in which the instance operates
 *
 * @ODM\String
 * @var string
 */
	protected $region;

/**
 * The Amazon Image ID (AMI) to use
 *
 * @ODM\String
 * @var string
 */
	protected $imageId;

/**
 * The instance type
 *
 * @ODM\String
 * @var string
 */
	protected $instanceType;

/**
 * protected IP Address
 *
 * @ODM\String
 * @var string
 */
	protected $ipAddress;

/**
 * protected DNS
 *
 * @ODM\String
 * @var string
 */
	protected $dnsName;

/**
 * Server instance state
 *
 * @ODM\String
 * @var string
 */
	protected $instanceState;

/**
 * Root device type
 *
 * @ODM\String
 * @var string
 */
	protected $rootDeviceType;

/**
 * Time the instance was launched
 *
 * @ODM\Date
 * @var DateTime
 */
	protected $launchTime;

/**
 * Options for running
 *
 * @ODM\Hash
 * @var array
 */
	protected $options = array();

/**
 * Creation date and time
 *
 * @ODM\Date
 * @var DateTime
 */
	protected $created;

/**
 * Modified date and time
 *
 * @ODM\Date
 * @var DateTime
 */
	protected $modified;

	public static $findMethods = array(
		'instances' => true
	);

	public function getId() {
		return $this->id;
	}

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

/**
 * Returns the instanceId property
 *
 * @return string
 */
	public function getInstanceId() {
		return $this->instanceId;
	}

/**
 * Sets the value for the instanceId property
 *
 * @param string $value
 * @return void
 */
	public function setInstanceId($value) {
		$this->instanceId = $value;
	}

/**
 * Returns the region property
 *
 * @return string
 */
	public function getRegion() {
		return $this->region;
	}

/**
 * Sets the value for the region property
 *
 * @param string $value
 * @return void
 */
	public function setRegion($value) {
		$this->region = $value;
	}

/**
 * Returns the imageId property
 *
 * @return string
 */
	public function getImageId() {
		return $this->imageId;
	}

/**
 * Sets the value for the imageId property
 *
 * @param string $value
 * @return void
 */
	public function setImageId($value) {
		$this->imageId = $value;
	}

/**
 * Returns the instanceType property
 *
 * @return string
 */
	public function getInstanceType() {
		return $this->instanceType;
	}

/**
 * Sets the value for the instanceType property
 *
 * @param string $value
 * @return void
 */
	public function setInstanceType($value) {
		$this->instanceType = $value;
	}

/**
 * Returns the ipAddress property
 *
 * @return string
 */
	public function getIpAddress() {
		return $this->ipAddress;
	}

/**
 * Sets the value for the ipAddress property
 *
 * @param string $value
 * @return void
 */
	public function setIpAddress($value) {
		$this->ipAddress = $value;
	}

/**
 * Returns the dnsName property
 *
 * @return string
 */
	public function getDnsName() {
		return $this->dnsName;
	}

/**
 * Sets the value for the dnsName property
 *
 * @param string $value
 * @return void
 */
	public function setDnsName($value) {
		$this->dnsName = $value;
	}

/**
 * Returns the instanceState property
 *
 * @return string
 */
	public function getInstanceState() {
		return $this->instanceState;
	}

/**
 * Sets the value for the instanceState property
 *
 * @param string $value
 * @return void
 */
	public function setInstanceState($value) {
		$this->instanceState = $value;
	}

/**
 * Returns the rootDeviceType property
 *
 * @return string
 */
	public function getRootDeviceType() {
		return $this->rootDeviceType;
	}

/**
 * Sets the value for the rootDeviceType property
 *
 * @param string $value
 * @return void
 */
	public function setRootDeviceType($value) {
		$this->rootDeviceType = $value;
	}

/**
 * Returns the launchTime property
 *
 * @return DateTime
 */
	public function getLaunchTime() {
		return $this->launchTime;
	}

/**
 * Sets the value for the launchTime property
 *
 * @param DateTime $value
 * @return void
 */
	public function setLaunchTime($value) {
		$this->launchTime = $value;
	}

/**
 * Returns the options property
 *
 * @return hash
 */
	public function getOptions() {
		return $this->options;
	}

/**
 * Sets the value for the options property
 *
 * @param hash $value
 * @return void
 */
	public function setOptions($value) {
		$this->options = $value;
	}

/**
 * Returns the created property
 *
 * @return DateTime
 */
	public function getCreated() {
		return $this->created;
	}

/**
 * Sets the value for the created property
 *
 * @param DateTime $value
 * @return void
 */
	public function setCreated($value) {
		$this->created = $value;
	}

/**
 * Returns the modified property
 *
 * @return DateTime
 */
	public function getModified() {
		return $this->modified;
	}

/**
 * Sets the value for the modified property
 *
 * @param DateTime $value
 * @return void
 */
	public function setModified($value) {
		$this->modified = $value;
	}

}