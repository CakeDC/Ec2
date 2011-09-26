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

		debug($Server->run());

//		debug($Server->find('all'));
	}

	public function describe() {
		$Server = new AmazonServer();
		$instances = $Server->describe();
		foreach ($instances as $i) {
			$i = $i->instancesSet->item;
			$this->out(sprintf('<info>Instance ( ID: %s )</info>', $i->instanceId));
			$this->out(sprintf('   - Status  : %s', $i->instanceState->name));
			$this->out(sprintf('   - Type    : %s', $i->instanceType));
			$this->out(sprintf('   - AMI     : %s', $i->imageId));
			$this->out(sprintf('   - Region  : %s', $i->placement->availabilityZone));
			$this->out(sprintf('   - Launched: %s', $i->launchTime));
			$this->out();
		}
	}
	
	public function stop() {
		if (count($this->args) != 1) {
			$this->out('Please specify "all" or an instance ID.');
			return;
		}
		
		if ($this->args[0] == 'all') {
			// Stop all
			// Need to get describe working right first.
			return;
		}
		
		$instanceId = $this->args[0];
		$Server = new AmazonServer();
		$Server->instanceId = $instanceId;
		$Server->terminate();
	}
	
}
