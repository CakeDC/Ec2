<?php

App::import('Vendor', 'CFRuntime', null, null, 'AWSSDKforPHP/sdk.class.php')

class AmazonServer extends AppModel {

	public function __call($method, $args) {
		$ec2 = new AmazonEC2();
		return $ec2->$method($args);
	}

}
