<?php
/**
 * @package recaptcha
 */

/**
 * Provides an {@link FormField} which allows form to validate for non-bot submissions
 * by giving them a challenge to decrypt an image.
 * Generation and validation of captchas is handled on external server.
 * This field doesn't save anything back to the form,
 * and only submits recaptcha-related form-data to an external server.
 * 
 * Your site language can be auto-detected for a number of available
 * languages. see http://doc.silverstripe.com/doku.php?id=i18n:
 * <example>
 * i18n::set_locale('de_DE');
 * </example>
 * 
 * @todo Does NOT work when the form is submitted via ajax, see http://recaptcha.net/apidocs/captcha/client.html for details on implementation.
 * 
 * @see http://recaptcha.net
 * @see http://recaptcha.net/api/getkey
 */
class RecaptchaField extends SpamProtectorField {
	
	/**
	 * Use secure connection for API-calls
	 *
	 * @var boolean
	 */
	public $useSSL = false;
	
	/**
	 * Javasript-object formatted as a string,
	 * which can contain options about the used theme/language etc.
	 * <example>
	 * "array('theme' => 'white')
	 * </example>
	 * 
	 * @see http://recaptcha.net/apidocs/captcha/client.html
	 * @var array
	 */
	public $jsOptions = array();
	
	/**
	 * Javasript-object formatted as a string,
	 * which can contain options about the used theme/language etc.
	 * <example>
	 * "array('theme' => 'white')
	 * </example>
	 * 
	 * This is similar to {@link $jsOptions}, but sets the default values used by all RecaptchaFields.
	 */
	public static $js_options = array();
	
	/**
	 * Use Ajax instead of iframe inclusion.
	 * 
	 * @see http://recaptcha.net/apidocs/captcha/client.html
	 * @var boolean
	 */
	public $useAjaxAPI = false;
	
	/**
	 * Your public API key for a specific domain (get one at http://recaptcha.net/api/getkey)
	 *
	 * @var string
	 */
	public static $public_api_key = '';
	
	/**
	 * Your private API key for a specific domain (get one at http://recaptcha.net/api/getkey)
	 *
	 * @var string
	 */
	public static $private_api_key = '';
	
	/**
	 * Verify API server address (relative)
	 *
	 * @var string
	 */
	public static $api_verify_server = 'www.google.com/recaptcha/api/verify';
	
	/**
	 * Javascript-address which includes necessary logic from the recaptcha-server.
	 * Your public key is automatically inserted.
	 * 
	 * @var string
	 */
	public static $recaptcha_js_url = "www.google.com/recaptcha/api/challenge?k=%s";
	
	/**
	 * URL to use when {@link $useAjaxAPI} is true.
	 *
	 * @var string
	 */
	public static $recaptcha_ajax_url = "www.google.com/recaptcha/api/js/recaptcha_ajax.js";
	
	/**
	 * @var string
	 */
	public static $recaptcha_noscript_url = "www.google.com/recaptcha/api/noscript?k=%s";
	
	/**
	 * @var string
	 */
	public static $httpclient_class = 'RecaptchaField_HTTPClient';
	
	/**
	 * All languages in which the recaptcha widget is available.
	 *
	 * @see http://recaptcha.net/apidocs/captcha/client.html
	 * @var array
	 */
	protected static $valid_languages = array(
		'en',
		'nl',
		'fr',
		'de',
		'pt',
		'ru',
		'es',
		'tr',
	);
	
	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null) {
		parent::__construct($name, $title, $value, $form, $rightTitle);
		
		$this->jsOptions = self::$js_options;
		
		// try to auto-detect language-settings
		$lang = substr(i18n::get_locale(), 0, 2);
		if(in_array($lang, self::$valid_languages)) $this->jsOptions['lang'] = $lang;
	}
	
	public function Field() {
		if(empty(self::$public_api_key) || empty(self::$private_api_key)) {
			user_error('RecaptchaField::FieldHolder() Please specify valid Recaptcha Keys', E_USER_ERROR);
		}

		$html = '';
		
		// Add javascript options as a <script> tag preceeding the JS that actually includes the
		// recaptcha
		if(!empty($this->jsOptions)) {
			$html .= "<script type=\"text/javascript\">//<![CDATA[\n"
				. "var RecaptchaOptions = " . $this->getJsOptionsString()
				. "//]]></script>";
		}
		
		$previousError = Session::get("FormField.{$this->form->FormName()}.{$this->Name()}.error");
		Session::clear("FormField.{$this->form->FormName()}.{$this->Name()}.error");

		// iframe (fallback)
		$iframeURL = ($this->useSSL) ? 'https://' : 'http://';
		$iframeURL .= sprintf(self::$recaptcha_noscript_url, self::$public_api_key);
		if(!empty($previousError)) $iframeURL .= "&error={$previousError}";
		
		// js (main logic)
		$jsURL = ($this->useSSL) ? 'https://' : 'http://';
		$jsURL .= sprintf(self::$recaptcha_js_url, self::$public_api_key);
		if(!empty($previousError)) $jsURL .= "&error={$previousError}";
	
		
		if($this->useAjaxAPI) {
			$ajaxURL = ($this->useSSL) ? 'https://' : 'http://';
			$ajaxURL .= self::$recaptcha_ajax_url;
			Requirements::javascript($ajaxURL);
			$html .= '
				<script type="text/javascript">
					//<![CDATA[
					Recaptcha.create("' . self::$public_api_key . '",
					"' . $this->Name() . '", {
					   callback: Recaptcha.focus_response_field
					});
				//]]>
				</script>
			';
		} else {
			$html .= '
				<script type="text/javascript" src="' . $jsURL . '">
				</script>
			';
		}
		
		// noscript fallback
		$html .= '<noscript>
			<iframe src="' .$iframeURL . '" height="300" width="500"></iframe>
			<br />
			<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
			<input type="hidden" name="recaptcha_response_field" value="manual_challenge" />
			</noscript>';
	
		return $html;
	}
	
	function FieldHolder() {
		$Title = $this->XML_val('Title');
		$Message = $this->XML_val('Message');
		$MessageType = $this->XML_val('MessageType');
		$Type = $this->XML_val('Type');
		$extraClass = $this->XML_val('extraClass');
		$Name = $this->XML_val('Name');
		$Field = $this->XML_val('Field');
		
		$messageBlock = (!empty($Message)) ? "<span class=\"message $MessageType\">$Message</span>" : "";

		return <<<HTML
<div id="$Name" class="field $Type $extraClass">{$Field}{$messageBlock}</div>
HTML;
	}
	
	/**
	 * Transform options from PHP-array to javascript-object encapsulated
	 * in a string.
	 * 
	 * @todo switch to json_encode once we move requirements to PHP 5.2+
	 * @return string
	 */
	public function getJsOptionsString() {
		$js = "{";
		$i=1;
		foreach($this->jsOptions as $k => $v) {
			$js .= "'{$k}':'{$v}'";
			if($i < count($this->jsOptions)) $js .= ',';
			$i++;
		}
		$js .= "}";

		return $js;
	}
	
	/**
	 * Validate by submitting to external service
	 *
	 * @todo implement socket timeout handling (or switch to curl?)
	 * @param Validator $validator
	 * @return boolean
	 */
	public function validate($validator) {
		// don't bother querying the recaptcha-service if fields were empty
		if(
			!isset($_REQUEST['recaptcha_challenge_field']) 
			|| empty($_REQUEST['recaptcha_challenge_field'])
			|| !isset($_REQUEST['recaptcha_response_field']) 
			|| empty($_REQUEST['recaptcha_response_field'])
		) {
			$validator->validationError(
				$this->name, 
				_t(
					'RecaptchaField.EMPTY', 
					"Please answer the captcha question",
					PR_MEDIUM,
					"Recaptcha (http://recaptcha.net) provides two words in an image, and expects a user to type them in a textfield"
				), 
				"validation", 
				false
			);
			
			return false;
		}

		$response = $this->recaptchaHTTPPost($_REQUEST['recaptcha_challenge_field'], $_REQUEST['recaptcha_response_field']);
		if(!$response) {
			$validator->validationError(
				$this->name, 
				_t(
					'RecaptchaField.NORESPONSE',
					"The recaptcha service gave no response. Please try again later.",
					PR_MEDIUM,
					"Recaptcha (http://recaptcha.net) provides two words in an image, and expects a user to type them in a textfield"
				), 
				"validation", 
				false
			);
			return false;			
		}
		
		// get the payload of the response and split it by newlines
		list($isValid, $error) = explode("\n", $response, 2);

		if($isValid != 'true') {
			// Count some errors as "user level", meaning they raise a validation error rather than a system error
			$userLevelErrors = array('incorrect-captcha-sol', 'invalid-request-cookie');
			if(!in_array(trim($error), $userLevelErrors)) {
				user_error("RecatpchaField::validate(): Recaptcha-service error: '{$error}'", E_USER_ERROR);
				return false;
			} else {
				// Internal error-string returned by recaptcha, e.g. "incorrect-captcha-sol". 
				// Used to generate the new iframe-url/js-url after form-refresh.
				Session::set("FormField.{$this->form->FormName()}.{$this->Name()}.error", trim($error)); 
				$validator->validationError(
					$this->name, 
					_t(
						'RecaptchaField.VALIDSOLUTION', 
						"Your answer didn't match the captcha words, please try again",
						PR_MEDIUM,
						"Recaptcha (http://recaptcha.net) provides two words in an image, and expects a user to type them in a textfield"
					), 
					"validation", 
					false
				);
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Fires off a HTTP-POST request
	 * 
	 * @see Based on http://recaptcha.net/plugins/php/
	 * @param string $challengeStr
	 * @param string $responseStr
	 * @return string Raw HTTP-response
	 */
	protected function recaptchaHTTPPost($challengeStr, $responseStr) {
		$postVars = array(
			'privatekey' => self::$private_api_key,
			'remoteip' => $_SERVER["REMOTE_ADDR"],
			'challenge' => $challengeStr,
			'response' => $responseStr,
		);
		$client = $this->getHTTPClient();
		$url = ($this->useSSL) ? 'https://' : 'http://';
		$url .= self::$api_verify_server;
		$response = $client->post($url, $postVars);

		return $response->getBody();
	}
	
	/**
	 * @param RecaptchaField_HTTPClient
	 */
	function setHTTPClient($client) {
		$this->client = $client;
	}
	
	/**
	 * @return RecaptchaField_HTTPClient
	 */
	function getHTTPClient() {
		if(!$this->client) {
			$class = self::$httpclient_class;
			$this->client = new $class();
		}
		
		return $this->client;
	}
}

/**
 * Simple HTTP client, mainly to make it mockable.
 */
class RecaptchaField_HTTPClient extends Object {
	
	/**
	 * @param String $url
	 * @param Array $data
	 * @return String HTTPResponse
	 */
	function post($url, $postVars) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'reCAPTCHA/PHP');
		// we need application/x-www-form-urlencoded
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postVars)); 
		$response = curl_exec($ch);

		if(class_exists('SS_HTTPResponse')) {
			$responseObj = new SS_HTTPResponse();
		} else {
			// 2.3 backwards compat
			$responseObj = new HTTPResponse();
		}
		$responseObj->setBody($response); // 2.2. compat
		
		return $responseObj;
	}
}