<?php

App::uses('AwsSource', 'Ec2.Model/Datasource');
App::uses('ConnectionManager', 'Model');
App::uses('AmazonServer', 'Ec2.Model');

class AmazonServerTest extends CakeTestCase {

	public $fixtures = array('plugin.ec2.amazon_server');

	public function  setUp() {
		$objects = ConnectionManager::enumConnectionObjects();
		$this->skipIf(empty($objects['awsTest']), 'You need to define the awsTest datasource');
		$this->config = $objects['awsTest'];
		$this->fixturePath = CakePlugin::path('Ec2') . 'Test' . DS . 'Fixture' . DS;
		if (!class_exists('AmazonEc2', false)) {
			require(App::pluginPath('Ec2') . 'Vendor/AWSSDKforPHP/sdk.class.php');
		}
	}

	public function tearDown() {
		ConnectionManager::drop('__awsSourceTest');
	}

	protected function createStub($methods, $responseFile) {
		$sourceClass = get_class($this->getMock('AwsSource', array('getEC2Object')));
		$this->config['datasource'] = $sourceClass;
		$this->Source = ConnectionManager::create('__awsSourceTest', $this->config);
		AmazonServer::$useAmazonConfig = '__awsSourceTest';

		$ec2 = $this->getMock('AmazonEC2', $methods);
		$body = new SimpleXMLIterator(file_get_contents($this->fixturePath . $responseFile));
		$response = $this->getMock('CFResponse', array('isOk'), array('', $body, 200));

		$ec2->expects($this->once())->method('describe_instances')
			->will($this->returnValue($response));
		$response->expects($this->once())->method('isOk')
			->will($this->returnValue(true));
		$this->Source->expects($this->once())->method('getEC2Object')->will($this->returnValue($ec2));
	}

	public function testInstances() {
		$this->createStub(array('describe_instances'), 'describe_response.xml');
		$instances = AmazonServer::find('instances', array('refresh' => true));
		$this->assertEquals(2, $instances->count());
		$instances = array_values($instances->toArray());
		$this->assertEquals('i-f00917ba', $instances[0]->instanceId);
		$this->assertEquals('i-f37917ba', $instances[1]->instanceId);
		$this->assertEquals('a-region', $instances[0]->region);
		$this->assertEquals('us-east-1a', $instances[1]->region);
	}

}