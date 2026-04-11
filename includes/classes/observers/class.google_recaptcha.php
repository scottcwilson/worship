<?php
/**
*
* @package plugins
* @copyright Copyright 2003-2012 Zen Cart Development Team
* @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
*
* Designed for v1.5.6
* Observer class for Google reCaptcha
*/

class google_recaptcha extends base {
	/**
	 * @var array
	 */
	private $pages_to_checkcheck = array();

	function __construct() {

		//comment out any pages you do NOT want to check
		$pages_to_check[] =  'NOTIFY_CONTACT_US_CAPTCHA_CHECK';
		$pages_to_check[] =  'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK';
		$pages_to_check[] =  'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK';
		$this->attach($this,$pages_to_check);
	}

	function update(&$class, $eventID, $paramsArray = array()) {
		global $messageStack, $error, $privatekey;

		require_once __DIR__ . '/google/autoload.php';
		//$recaptcha = new \ReCaptcha\ReCaptcha($privatekey);
		// If file_get_contents() is locked down on your PHP installation to disallow
		// its use with URLs, then you can use the alternative request method instead.
		// This makes use of fsockopen() instead.
		//$recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\SocketPost());
		// This makes use of curl instead
		$recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\Curl());

      $response_set = isset($_POST['g-recaptcha-response']); 
      if ($response_set) { 
		  $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
      }
		if (!$response_set || !$resp->isSuccess()) {
			$event_array = array('NOTIFY_CONTACT_US_CAPTCHA_CHECK' => 'contact', 'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK' => 'create_account', 'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK' => 'review_text');
         if ($response_set) { 
			   $messageStack->add($event_array[$eventID], $resp->getErrors());
         } else {
			   $messageStack->add($event_array[$eventID], "Response not set");
         }
			$error = true;
		}
		return $error;
	}
}
