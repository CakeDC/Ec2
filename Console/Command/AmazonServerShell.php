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

		debug($Server->run());

//		debug($Server->find('all'));
	}

	public function describe() {
		$Server = new AmazonServer();
		$instances = $Server->describe();
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
