<?php

App::uses('AmazonServer', 'Ec2.Model');

/**
 * AmazonServer Shell
 *
 * @package Ec2
 * @subpackage Ec2.Console.Command
 */
class AmazonServerShell extends Shell {
	
	public function run() {
		
		/*** TESTING DATA ***/
		
		$region = 'us-east-1';
		$ami = 'ami-61be7908';
		$min = 1;
		$max = 1;
		
		$Server = new AmazonServer();
		$Server->region = $region;
		$Server->imageId = $ami;
		$Server->minimum = $min;
		$Server->maximum = $max;
		$Server->options = array(
			'InstanceType' => 't1.micro',
			'SecurityGroupId' => 'sg-c04edaa9',
		);

		$Server->save();
		$Server->flush();

		/*** END TESTING DATA ***/

		$response = $Server->run();
		$this->_instanceDetail($response->instancesSet->item);
	}

	public function describe() {
		$Server = new AmazonServer();
		$instances = $Server->describe();
		if (empty($instances)) {
			$this->out('You have no instances available');
			$this->out();
			return;
		}
		foreach ($instances as $i) {
			$i = $i->instancesSet->item;
			$this->_instanceDetail($i);
		}
	}
	
	protected function _instanceDetail($i) {
		$this->out(sprintf('<info>Instance ( ID: %s )</info>', $i->instanceId));
		$this->out(sprintf('   - Status  : %s', $this->_decorateStatus($i->instanceState->name)));
		$this->out(sprintf('   - Type    : %s', $i->instanceType));
		$this->out(sprintf('   - AMI     : %s', $i->imageId));
		$this->out(sprintf('   - Region  : %s', $i->placement->availabilityZone));
		$this->out(sprintf('   - Device  : %s', $i->rootDeviceType));
		$this->out(sprintf('   - Launched: %s', $i->launchTime));
		$this->out();
	}
	
	protected function _decorateStatus($status) {
		$type = '';
		switch ($status) {
			case 'terminated':
				$type = 'question'; break;
			case 'pending':
			case 'shutting-down':
				$type = 'warning'; break;
			case 'running':
				$type = 'success'; break;
			default:
				return $status;
		}
		return sprintf('<%s>%s</%s>', $type, $status, $type);
	}
	
	public function terminate() {
		if (count($this->args) != 1) {
			$this->out('Please specify "all" or an instance ID.');
			return;
		}
		
		$Server = new AmazonServer();

		if ($this->args[0] == 'all') {
			$instances = $Server->describe();
			if (empty($instances)) {
				$this->out('You have no instances available');
				$this->out();
				return;
			}
			$ids = array();
			foreach ($instances as $i) {
				$i = $i->instancesSet->item;
				$ids[] = $i->instanceId;
			}
		} else {
			$ids = $this->args;
		}
		
		$response = $Server->terminate($ids);
		foreach ($response->instancesSet->item as $i) {
			$this->out(sprintf('<info>Instance ( ID: %s )</info> => %s' , $i->instanceId, $this->_decorateStatus($i->currentState->name)));
		}
	}
}
