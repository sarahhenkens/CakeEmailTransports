<?php
/**
 * Send mail using the postageapp.com service
 *
 * PHP 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2012-2012, Jelle Henkens.
 * @package       CakeEmailTransports.Network.Email
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AbstractTransport', 'Network/Email');
App::uses('HttpSocket', 'Network/Http');
App::uses('String', 'Utility');

class PostageappTransport extends AbstractTransport {

/**
 * Socket
 *
 * @var HttpSocket
 */
	protected $_socket;

/**
 * CakeEmail
 *
 * @var CakeEmail
 */
	protected $_cakeEmail;

/**
 * The API endpoint URI.
 * @var string 
 */
	protected $_apiUri = 'https://api.postageapp.com/v.1.0/send_message.json';

/**
 * Holds the data to be sent to the API
 * @var array 
 */
	protected $_data = array();

/**
 * Content of email to return
 *
 * @var array
 */
	protected $_content = array();

/**
 * Set the configuration
 *
 * @param array $config
 * @return void
 */
	public function config($config = array()) {
		$default = array(
			'apiKey' => false
		);

		$this->_config = $config + $default;
	}

/**
 * Send mail
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 * @throws CakeException
 */
	public function send(CakeEmail $email) {
		$this->_cakeEmail = $email;

		$this->_prepareData();
		$this->_prepareAttachments();
		$this->_postageappSend();

		return $this->_content;
	}

/**
 * Prepares the data array.
 * Adds headers and content
 *
 * @return void 
 */
	protected function _prepareData() {
		$this->_data = array();

		if (count($this->_cakeEmail->cc()) > 0) {
			throw new CakeException('Postageapp transport does not support cc');
		}

		if (count($this->_cakeEmail->bcc()) > 0) {
			throw new CakeException('Postageapp transport does not support bcc');
		}

		if (count($this->_cakeEmail->sender()) > 0) {
			throw new CakeException('Postageapp transport does not support sender');
		}

		$headers = $this->_cakeEmail->getHeaders(array('from', 'sender', 'replyTo', 'returnPath', 'to', 'subject'));

		$this->_data['recipients'] = $headers['To'];

		$map = array('From', 'Subject', 'Reply-To', 'X-Mailer', 'MIME-Version', 'Content-Transfer-Encoding');
		foreach ($map as $header) {
			if (!empty($headers[$header])) {
				$this->_addHeader($header, $headers[$header]);
			}
		}

		$emailFormat = $this->_cakeEmail->emailFormat();

		if ($emailFormat == 'both' || $emailFormat == 'text') {
			$this->_data['content']['text/plain'] = $this->_cakeEmail->message('text');
		}

		if ($emailFormat == 'both' || $emailFormat == 'html') {
			$this->_data['content']['text/html'] = $this->_cakeEmail->message('html');
		}
	}

/**
 * Reads attached files and adds them to the data
 *
 * @return void 
 */
	protected function _prepareAttachments() {
		$attachments = $this->_cakeEmail->attachments();

		if (empty($attachments)) {
			return;
		}

		foreach ($attachments as $filename => $info) {
			$content = $this->_readFile($info['file']);
			$this->_data['attachments'][$filename] = array(
				'content' => $content,
				'Content_type' => $info['mimetype']
			);
		}
	}

/**
 * Reads a file from the filesystem
 *
 * @param string $file Absolute path to the file
 * @return string base64 encoded data 
 */
	protected function _readFile($file) {
		$handle = fopen($file, 'rb');
		$data = fread($handle, filesize($file));
		$data = chunk_split(base64_encode($data));
		fclose($handle);
		return $data;
	}

/**
 * Adds a custom header to the data
 *
 * @param string $name
 * @param string $value
 * @return void
 */
	protected function _addHeader($name, $value) {
		$this->_data['headers'][$name] = $value;
	}

/**
 * Posts the data to the postmark API endpoint
 *
 * @return array
 * @throws CakeException
 */
	protected function _postageappSend() {
		$this->_generateSocket();

		$request = array(
			'header' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			)
		);

		$data = array(
			'api_key' => $this->_config['apiKey'],
			'uid' => String::uuid(),
			'arguments' => $this->_data
		);

		$return = $this->_socket->post($this->_apiUri, json_encode($data), $request);

		$response = json_decode($return);

		if ($this->_socket->response->code != '200') {
			throw new CakeException($response->response->message);
		}

		$this->_content = array(
			'headers' => array(),
			'message' => $this->_data,
			'response' => $response
		);
	}

/**
 * Helper method to generate socket
 *
 * @return void
 */
	protected function _generateSocket() {
		$this->_socket = new HttpSocket();
	}
}
