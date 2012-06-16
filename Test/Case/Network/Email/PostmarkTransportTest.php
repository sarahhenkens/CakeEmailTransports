<?php
/**
 * PostMarkTransportTest file
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2012-2012, Jelle Henkens.
 * @package       CakeEmailTransports.Test.Network.Email
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('PostmarkTransport', 'CakeEmailTransports.Network/Email');
App::uses('HttpSocket', 'Network/Http');

/**
 * Help to test PostmarkTransport
 *
 */
class PostmarkTestTransport extends PostmarkTransport {

/**
 * Helper to change the socket
 *
 * @param HttpSocket $socket
 * @return void
 */
	public function setSocket(HttpSocket $socket) {
		$this->_socket = $socket;
	}

/**
 * Helper to change the CakeEmail
 *
 * @param object $cakeEmail
 * @return void
 */
	public function setCakeEmail($cakeEmail) {
		$this->_cakeEmail = $cakeEmail;
	}

/**
 * Disabled the socket change
 *
 * @return void
 */
	protected function _generateSocket() {
	}

/**
 * Magic function to call protected methods
 *
 * @param string $method
 * @param string $args
 * @return mixed
 */
	public function __call($method, $args) {
		$method = '_' . $method;
		return $this->$method();
	}

}

/**
 * Test case
 *
 */
class PostmarkTransportTest extends CakeTestCase {
/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		if (!class_exists('MockSocket')) {
			$this->getMock('HttpSocket', array(), array(), 'MockSocket');
		}
		$this->socket = new MockSocket();

		$this->PostmarkTransport = new PostmarkTestTransport();
		$this->PostmarkTransport->setSocket($this->socket);
	}
}