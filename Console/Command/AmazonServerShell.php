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
	
	public function run() {
		$Server = AmazonServer::find('first', array('conditions' => array('id' => $this->args[0])));
		if (!$Server) {
			$this->err('<error>Not a valid instance id: ' . $this->args[0] . '</error>');
			return;
		}
		$response = $Server->run();
		$this->_instanceDetail($response->instancesSet->item);
	}

	public function describe() {
		$instances = AmazonServer::instances();
		if (empty($instances)) {
			$this->out('<warning>You have no instances available</warning>');
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
		
		$response = AmazonServer::terminateAll($ids);
		foreach ($response->instancesSet->item as $i) {
			$this->out(sprintf(
				'<info>Instance ( ID: %s )</info> => %s',
				$i->instanceId,
				$this->_decorateStatus($i->currentState->name)
			));
		}
	}
}
