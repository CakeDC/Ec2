<?php

App::uses('AmazonServer', 'Ec2.Model');

/**
 * AmazonServer Shell
 *
 * @package Ec2
 * @subpackage Ec2.Console.Command
 */
class AmazonServerShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser
			->addSubcommand('create', array(
				'help' => 'Creates and runs a new server instance',
				'parser' => array(
					'description' => 'This command is used to create and start server instances',
					'arguments' => array(
						'ami' => array(
							'help' => 'The AMI to use for the server',
							'required' => true
						)
					),
					'options' => array(
						'min' => array(
							'help' => 'The minimum amount of instances to create',
							'default' => 1
						),
						'max' => array(
							'help' => 'The maximum amount of instances to create',
							'default' => 1
						),
						'region' => array(
							'short' => 'r',
							'help' => 'Region where the server should be located',
							'default' => 'us-east-1',
						),
						'type' => array(
							'short' => 't',
							'help' => 'Type of instance to create',
							'default' => 't1.micro',
						),
						'security-group' => array(
							'short' => 's',
							'help' => 'The security group id to use'
						)
					)
				)
			))
			->addSubcommand('run', array(
				'help' => 'Starts an instance which settings are already stored in database',
				'parser' => array(
					'description' =>
						'This command is used to start server instances for the first time. ' .
						'It takes a database id to lookup the stored server settings and passes the information to amazon services.',
					'arguments' => array(
						'id' => array(
							'help' => 'The database id holding the settings for the instance',
							'required' => true
						)
					)
				)
			))
			->addSubcommand('describe', array(
				'help' => 'Returns all information from running instances'
			))
			->addSubcommand('start', array(
				'help' => 'starts an instance',
				'parser' => array(
					'description' => 'Use this command to start a known server instance',
					'arguments' => array(
						'id' => array(
							'help' => 'The server internal id',
							'required' => true
						)
					)
				)
			))
			->addSubcommand('stop', array(
				'help' => 'stops an instance',
				'parser' => array(
					'description' => 'Use this command to stop a known server instance',
					'arguments' => array(
						'id' => array(
							'help' => 'The server internal id',
							'required' => true
						)
					)
				)
			))
			->addSubcommand('reboot', array(
				'help' => 'reboots an instance',
				'parser' => array(
					'description' => 'Use this command to rebot a known server instance',
					'arguments' => array(
						'id' => array(
							'help' => 'The server internal id',
							'required' => true
						)
					)
				)
			))
			->addSubcommand('terminate', array(
				'help' => 'Shuts down an instance',
				'parser' => array(
					'description' => 'Use this command to stop a known server instance',
					'arguments' => array(
						'instanceID' => array(
							'help' => 'The Amazon server instance id to terminate. If the word <info>all</info> is passed ' .
								'all running instances will be stopped',
							'required' => true
						)
					)
				)
			));
		return $parser;
	}

	public function create() {
		$server = AmazonServer::create(array(
			'imageId' => $this->args[0],
			'region' => $this->params['region'],
			'options' => array(
				'InstanceType' => $this->params['type'],
				'SecurityGroupId' => $this->params['security-group'],
			)
		));
		$response = $server->run();
		$server->flush();
		$this->_instanceDetail(current($response));
	}
	
	public function run() {
		$Server = AmazonServer::find('first', array('conditions' => array('id' => $this->args[0])));
		if (!$Server) {
			$this->err('<error>Not a valid instance id: ' . $this->args[0] . '</error>');
			return;
		}
		$response = $Server->run();
		$Server->flush();
		$this->_instanceDetail(current($response));
	}

	public function describe() {
		$instances = AmazonServer::find('instances', array('refresh' => true));
		if (empty($instances)) {
			$this->out('<warning>You have no instances available</warning>');
			$this->out();
			return;
		}
		foreach ($instances as $i) {
			$this->_instanceDetail($i);
		}
	}
	
	protected function _instanceDetail($i) {
		$this->out(sprintf('<info>Instance ( ID: %s )</info>', $i->instanceId));
		$this->out(sprintf('   - Internal ID  : %s', $i->id));
		$this->out(sprintf('   - Status       : %s', $this->_decorateStatus($i->instanceState)));
		$this->out(sprintf('   - Type         : %s', $i->instanceType));
		$this->out(sprintf('   - AMI          : %s', $i->imageId));
		$this->out(sprintf('   - Region       : %s', $i->region));
		$this->out(sprintf('   - Device       : %s', $i->rootDeviceType));
		$this->out(sprintf('   - Launched     : %s', $i->launchTime->format('Y-m-d H:i:s')));
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

	public function start() {
		$server = AmazonServer::find('first', array(
			'conditions' => array('id' => $this->args[0])
		));
		if (!$server) {
			throw new NotFoundException('Could not find such server');
		}
		$response = $server->start();
		$server->flush();
		$this->_instanceDetail(current($response));
	}

	public function stop() {
		$server = AmazonServer::find('first', array(
			'conditions' => array('id' => $this->args[0])
		));
		if (!$server) {
			throw new NotFoundException('Could not find such server');
		}
		$response = $server->stop();
		$server->flush();
		$this->_instanceDetail(current($response));
	}

	public function reboot() {
		$server = AmazonServer::find('first', array(
			'conditions' => array('id' => $this->args[0])
		));
		if (!$server) {
			throw new NotFoundException('Could not find such server');
		}
		$success = $server->reboot();
		if ($success) {
			$this->out('<success>Rebooting server now</success>');
		} else {
			$this->out('<error>Could not reboot the server</error>');
		}
	}

	public function terminate() {
		if ($this->args[0] == 'all') {
			$instances = AmazonServer::instances();
			if (empty($instances)) {
				$this->out('<info>You have no instances available</info>');
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
		
		$instances = AmazonServer::terminateAll($ids);
		foreach ($instances as $i) {
			$this->out(sprintf(
				'<info>Instance ( ID: %s )</info> => %s',
				$i->instanceId,
				$this->_decorateStatus($i->instanceState)
			));
		}
	}
}
