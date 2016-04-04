<?php
	namespace WebPay;
	
	use Nette\FileNotFoundException;

	use Nette\Application\UI\Control;

	class WebPay extends Control {
		public $url;
		public $publicKey;
		public $privateKey;
		public $password;
		public $digest;
		public $params;
		
		public function __construct() {
			$this->setParams();
		}
		
		public function setUrl ($url) {
			$this->url = $url;
		}
		
		public function setMerchantNumber ($number) {
			$this->params[1] = $number;
		}
		
		public function setCurrency ($code) {
			$this->params[5] = (string) $code;
		}
		
		public function setOrderDescription ($text) {
			$this->params[9] = $text;
		}
		
		public function setPublicKey ($file) {
			if (!is_file($file)) throw new FileNotFoundException ('File '.$file.' not found!');
			
			$f = fopen($file, 'r');
			$this->publicKey = fread($f, filesize($file));
			fclose($f);
		}
		
		public function setPrivateKey ($file, $password) {
			if (!is_readable($file)) throw new FileNotFoundException ('File '.$file.' not found!');
			
			$f = fopen($file, "r");
			$this->privateKey = fread($f, filesize($file));
			fclose($f);
			
			$this->password = $password;
		}
		
		public function setParams () {
			$this->params = array (
								2 => 'CREATE_ORDER',
								6 => 0,
							);
		}
		
		public function setRedirectUrl ($url) {
			$this->params[8] = $url;
		}
		
		public function generateLink ($price, $orderNumber) {
			$this->params[4] = ($price * 100);
			$this->params[3] = $orderNumber;
			
			$this->sign();
			
			$flags = array (
						1 => 'MERCHANTNUMBER',
						2 => 'OPERATION',
						3 => 'ORDERNUMBER',
						4 => 'AMOUNT',
						5 => 'CURRENCY',
						6 => 'DEPOSITFLAG',
						8 => 'URL',
						9 => 'DESCRIPTION',
						11 => 'DIGEST'
					);
			
			if (isset($this->digest)) {
				$this->params[11] = $this->digest;
				
				foreach ($this->params as $key => $param) {
					$this->params[$key] = $flags[$key].'='.urlencode($param); 
				}
				
				return $this->url . "?" . implode('&', $this->params);
			}
			else {
				return false;
			}
		}
		
		public function sign () {			
			ksort($this->params);
			
			$digestValue = implode('|', $this->params);
			
			$keyid = openssl_get_privatekey($this->privateKey, $this->password);
			openssl_sign($digestValue, $this->digest, $keyid);
			$this->digest = base64_encode($this->digest);
			openssl_free_key($keyid);
		}
		
		public function verify ($key) {
			$pubcertid = openssl_get_publickey($this->publicKey);
			$digest = base64_decode($this->digest);
			$data = implode('|', $this->params);
			
			$result = openssl_verify($data, $digest, $pubcertid);
			openssl_free_key($pubcertid);
			
			return $result == 1 ? true : false;
		}
		
		public function getResponse () {
			$this->digest = $_GET['DIGEST'];
		
			unset($_GET['DIGEST']);
			unset($_GET['DIGEST1']);
			$this->params = $_GET;
			
			if ($this->verify($this->publicKey) && $_GET['PRCODE'] == 0 && $_GET['SRCODE'] == 0) {
				return true;
			}
			else return false;
		}
	}