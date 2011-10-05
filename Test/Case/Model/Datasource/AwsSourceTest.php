<?php
App::uses('AwsSource', 'Ec2.Model/Datasource');
App::uses('ConnectionManager', 'Model');
App::uses('AmazonServer', 'Ec2.Model');

/**
 * This class requires a $awsTest datasource defined in database.php
 *
 */
class AwsSourceTest extends CakeTestCase {

	public $fixtures = array('plugin.ec2.amazon_server');

	public function setUp() {
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

	protected function createStub($method, $responseFile) {
		$sourceClass = get_class($this->getMock('AwsSource', array('getEC2Object')));
		$this->config['datasource'] = $sourceClass;
		$this->Source = ConnectionManager::create('__awsSourceTest', $this->config);

		$ec2 = $this->getMock('AmazonEC2', array($method));
		$body = new SimpleXMLIterator(file_get_contents($this->fixturePath . $responseFile));
		$response = $this->getMock('CFResponse', array('isOk'), array('', $body, 200));

		$ec2->expects($this->once())->method($method)
			->will($this->returnValue($response));
		$response->expects($this->once())->method('isOk')
			->will($this->returnValue(true));
		$this->Source->expects($this->once())->method('getEC2Object')->will($this->returnValue($ec2));
	}

	public function testInstances() {
		$this->createStub('describe_instances', 'describe_response.xml');
		$instances = $this->Source->instances();
		$this->assertEquals(2, count($instances));

		$this->assertEquals('AmazonServer', get_class($instances[0]));
		$this->assertEquals('AmazonServer', get_class($instances[1]));
		$this->assertEquals('i-f00917ba', $instances[0]->instanceId);
		$this->assertEquals('i-f37917ba', $instances[1]->instanceId);
		$this->assertEquals('a-region', $instances[0]->region);
		$this->assertEquals('us-east-1a', $instances[1]->region);
	}

	public function testRun() {
		$this->createStub('run_instances', 'run_response.xml');
		$server = AmazonServer::create(array(
			'region' => 'us-east-1',
			'imageId' => 'ami-61be7908'
		));
		$options = array(
			'InstanceType' => 't1.micro',
			'SecurityGroupId' => 'sg-c04edaa9',
		);
		$instances = $this->Source->run($server, $options);
		$this->assertEquals(1, count($instances));
		$this->assertEquals('i-481f4b28', $instances[0]->instanceId);
		$this->assertEquals('pending', $instances[0]->instanceState);
	}

	public function testRunTwoInstances() {
		$this->createStub('run_instances', '2_instances_response.xml');
		$server = AmazonServer::create(array(
			'region' => 'us-east-1',
			'imageId' => 'ami-61be7908'
		));
		$options = array(
			'InstanceType' => 't1.micro',
			'SecurityGroupId' => 'sg-c04edaa9',
		);
		$instances = $this->Source->run($server, $options);
		$this->assertEquals(2, count($instances));
		$this->assertEquals('i-4a12252a', $instances[0]->instanceId);
		$this->assertEquals('pending', $instances[0]->instanceState);
		$this->assertEquals('i-4c5e352c', $instances[1]->instanceId);
		$this->assertEquals('pending', $instances[1]->instanceState);
	}

	public function testTerminate() {
		$this->createStub('terminate_instances', 'terminate_response.xml');
		$server = AmazonServer::find('first');
		$instances = $this->Source->terminate(array($server));
		$this->assertEquals('shutting-down', $instances[0]->instanceState);
	}

	public function testStart() {
		$this->createStub('start_instances', 'start_response.xml');
		$server = AmazonServer::find('first');
		$instances = $this->Source->start(array($server));
		$this->assertEquals('pending', $instances[0]->instanceState);
	}

	public function testStop() {
		$this->createStub('stop_instances', 'stop_response.xml');
		$server = AmazonServer::find('first');
		$instances = $this->Source->stop(array($server));
		$this->assertEquals('stopping', $instances[0]->instanceState);
	}

	public function testReboot() {
		$this->createStub('reboot_instances', 'reboot_response.xml');
		$server = AmazonServer::find('first');
		$result = $this->Source->reboot(array($server));
		$this->assertTrue($result);
	}
}