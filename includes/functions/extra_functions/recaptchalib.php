<?php
/*
 * AUTHOR:
 *   David Allen 2015
 *   Contains added recaptcha_check() function for bypassing observer class if necessary
 */

// Get your v2 keys from https://www.google.com/recaptcha/admin
$publickey = '6LeciS8jAAAAAGn4ID3wg6uDaJ00KskCGsTwXO6S';
$privatekey='6LeciS8jAAAAAAkfjLOGK_XGRr_6Mn9MfLffo7EK';

/**
 * Creates the challenge HTML.
 * This is called from the browser, and the resulting reCAPTCHA HTML widget
 * is embedded within the HTML form it was called from.
 * @param boolean $fieldset Should the challenge be wrapped in a fieldset (optional, default is false)
 * @param string $theme Should the reCaptcha be shown in dark or light theme (optional, default is light)
 * @param string $size Should the reCaptcha be shown in compact or normal size (optional, default is normal)

 * @return string - The HTML to be embedded in the user's form.
 */
function recaptcha_get_html ($fieldset=false, $theme='light', $size='normal') {
	global $publickey;
	// abort early if not configured
	if ($publickey == 'ENT'.'ER_YOU'.'R_PUB'.'LIC_K'.'EY_HE'.'RE') return '';
	if (empty($publickey)) return '';

	$recaptcha_html='';
	if ($fieldset==true) {
		$recaptcha_html.='<fieldset>'."\n";
	}
	$parameters=' class="g-recaptcha" data-sitekey="'.$publickey.'"';
	if ($theme!='light') {
		$parameters.=' data-theme="dark"';
	}
	if ($size!='normal') {
		$parameters.=' data-size="compact"';
	}
	$recaptcha_html.='<script src="https://www.google.com/recaptcha/api.js" async defer></script>'."\n";
	//to display the reCaptcha in French comment out the above line and uncomment the line below. For other language codes see https://developers.google.com/recaptcha/docs/language
	//$recaptcha_html.='<script src="https://www.google.com/recaptcha/api.js?hl=fr" async defer></script>'."\n";

	$recaptcha_html.='<div'.$parameters.'></div>'."\n";
	if ($fieldset==true) {
		$recaptcha_html.='</fieldset>'."\n";
	}
	return $recaptcha_html;
}

function recaptcha_check($page = null) {
	global $messageStack, $error, $privatekey;

	if ($page === null) {
		$page = $GLOBALS['current_page_base'];
	}

	require_once DIR_FS_CATALOG . DIR_WS_CLASSES . 'google/autoload.php';
	//$recaptcha = new \ReCaptcha\ReCaptcha($privatekey);
	// If file_get_contents() is locked down on your PHP installation to disallow
	// its use with URLs, then you can use the alternative request method instead.
	// This makes use of fsockopen() instead.
	//$recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\SocketPost());
	// This makes use of curl instead
	$recaptcha = new \ReCaptcha\ReCaptcha($privatekey, new \ReCaptcha\RequestMethod\Curl());

	$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

	if (!$resp->isSuccess()) {
 		$event_array = array('contact_us' => 'contact', 'create_account' => 'create_account', 'reviews_write' => 'review_text', 'ask_a_question' => 'contact');
		$messageStack->add($event_array[$page], $resp->getErrors());
		$error = true;
	}
	return $error;
}
