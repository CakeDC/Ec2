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