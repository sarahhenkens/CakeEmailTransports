<?php
/**
 * Send mail using the Postmark service
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

class PostmarkTransport extends AbstractTransport {

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
	protected $_apiUri = 'api.postmarkapp.com/email';

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
			'apiKey' => false,
			'secure' => false,
			'tag' => false,
			'debug' => false
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
		$this->_postmarkSend();

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

		$headers = $this->_cakeEmail->getHeaders(array('from', 'sender', 'replyTo', 'returnPath', 'to', 'cc', 'bcc', 'subject'));

		$map = array('From', 'To', 'Cc', 'Bcc', 'Subject');
		foreach ($map as $header) {
			if (!empty($headers[$header])) {
				$this->_data[$header] = $headers[$header];
			}
		}

		if (!empty($headers['Reply-To'])) {
			$this->_data['ReplyTo'] = $headers['Reply-To'];
		}

		$tag = false;
		if (isset($headers['X-Tag'])) {
			$tag = $headers['X-Tag'];
		} elseif (isset($this->_config['tag'])) {
			$tag = $this->_config['tag'];
		}

		if ($tag !== false) {
			$this->_data['Tag'] = $this->_config['tag'];
		}

		$map = array('X-Mailer', 'MIME-Version', 'Content-Transfer-Encoding');
		foreach ($map as $header) {
			if (!empty($headers[$header])) {
				$this->_addHeader($header, $headers[$header]);
			}
		}

		$emailFormat = $this->_cakeEmail->emailFormat();

		if ($emailFormat == 'both' || $emailFormat == 'text') {
			$this->_data['TextBody'] = $this->_cakeEmail->message('text');
		}

		if ($emailFormat == 'both' || $emailFormat == 'html') {
			$this->_data['HtmlBody'] = $this->_cakeEmail->message('html');
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
			$this->_data['Attachments'][] = array(
				'Name' => $filename,
				'Content' => $content,
				'ContentType' => $info['mimetype']
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
		$this->_data['Headers'][] = array('Name' => $name, 'Value' => $value);
	}
	
/**
 * Posts the data to the postmark API endpoint
 *
 * @return array
 * @throws CakeException
 */
	protected function _postmarkSend() {
		$this->_generateSocket();

		$protocol = $this->_config['secure'] ? 'https' : 'http';
		$uri = $protocol . '://' . $this->_apiUri;

		$apiKey = $this->_config['debug'] === true ? 'POSTMARK_API_TEST' : $this->_config['apiKey'];

		if (is_string($this->_config['debug'])) {
			$this->_data['To'] = $this->_config['debug'];
			if (isset($this->_data['Cc'])) {
				unset($this->_data['Cc']);
			}
			if (isset($this->_data['Bcc'])) {
				unset($this->_data['Bcc']);
			}
		}

		$request = array(
			'header' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-Postmark-Server-Token' => $apiKey
			)
		);

		$return = $this->_socket->post($uri, json_encode($this->_data), $request);

		$response = json_decode($return);

		if ($this->_socket->response->code != '200') {
			throw new CakeException($response->Message);
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
