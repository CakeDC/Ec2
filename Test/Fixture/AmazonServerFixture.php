<?php
App::uses('DocumentTestFixture', 'MongoCake.TestSuite/Fixture');

class AmazonServerFixture extends DocumentTestFixture {

	public $plugin = 'Ec2';
	public $records = array(
		array(
			'instanceId' => 'i-f00917ba',
			'instanceState' => 'pending',
			'ipAddress' => '',
			'dnsName' => '',
			'imageId' => 'ami-ga36fbc3',
			'region' => 'a-region'
		)
	);

}