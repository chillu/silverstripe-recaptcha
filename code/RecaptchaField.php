<?php
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
 * CAUTION: Does NOT work when the form is submitted via ajax,
 * see http://recaptcha.net/apidocs/captcha/client.html for details on implementation.
 * 
 * @see http://recaptcha.net
 * @see http://recaptcha.net/api/getkey
 * @version 0.1
 */
class RecaptchaField extends DatalessField {
	
	/**
	 * Your public API key for a specific domain (get one at http://recaptcha.net/api/getkey)
	 *
	 * @var string
	 */
	public $publicApiKey = '';
	
	/**
	 * Your private API key for a specific domain (get one at http://recaptcha.net/api/getkey)
	 *
	 * @var string
	 */
	public $privateApiKey = '';
	
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
	 * Note: Only available if {@link $useJavascript} is turned on.
	 * 
	 * @see http://recaptcha.net/apidocs/captcha/client.html
	 * @var array
	 */
	public $jsOptions = array();
	
	/**
	 * Show error message either through recaptcha-widget
	 * (default) or through a Silverstripe validator.
	 *
	 * @var boolean
	 */
	public $useInternalValidator = false;
	
	/**
	 * Internal error-string returned by recaptcha,
	 * e.g. "incorrect-captcha-sol". Used to generate the
	 * new iframe-url after form-refresh.
	 *
	 * @see http://recaptcha.net/apidocs/captcha/
	 * @var unknown_type
	 */
	protected $errorString = '';
	
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

	/**
	 * Standard API server addess
	 *
	 * @var string
	 */
	public static $api_server = "http://api.recaptcha.net";
	
	/**
	 * Secure API server address
	 *
	 * @var string
	 */
	public static $api_ssl_server = "https://api-secure.recaptcha.net";
	
	/**
	 * Verify API server address (relative)
	 *
	 * @var string
	 */
	public static $api_verify_server = "api-verify.recaptcha.net";
	
	/**
	 * Javascript-address which includes necessary logic from the recaptcha-server.
	 * Your public key is automatically inserted.
	 * 
	 * @var string
	 */
	public static $recaptcha_js_url = "http://api.recaptcha.net/challenge?k=%s";
	
	
	public function Field() {
		if(empty($this->publicApiKey) || empty($this->privateApiKey)) {
			user_error('RecaptchaField::FieldHolder() Please specify valid Recaptcha Keys', E_USER_ERROR);
		}
		
		if(!empty($this->jsOptions)) {
			Requirements::customScript("var RecaptchaOptions = " . $this->getJsOptionsString());
		}
		
		$iframeURL = sprintf(
			"%s/noscript?k=%s",
			($this->useSSL) ? self::$api_ssl_server : self::$api_server,
			$this->publicApiKey
		);
		if(!empty($this->errorString)) $iframeURL .= "&error={$this->errorString}";
		
		$html = '
			<script type="text/javascript" src="' . sprintf(self::$recaptcha_js_url, $this->publicApiKey) . '">
			</script>
		';
		$html .= '<noscript>
			<iframe src="' .$iframeURL . '" height="300" width="500" frameborder="0">
			</iframe>
			<br />
			<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
			<input type="hidden" name="recaptcha_response_field" value="manual_challenge">
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
		// try to auto-detect language-settings
		$lang = substr(i18n::get_locale(), 0, 2);
		if(!isset($this->jsOptions['lang']) && in_array($lang, self::$valid_languages)) {
			$this->jsOptions['lang'] = $lang;
		}
		
		$js = "{";
		foreach($this->jsOptions as $k => $v) {
			$js .= "'{$k}':'{$v}'";
			if(current($this->jsOptions) < count($this->jsOptions)) $js .= ',';
		}
		$js .= "}";
		
		return $js;
	}
	
	/**
	 * Validate by submitting to external service
	 *
	 * @todo Add more detailed feedback if using {@link useInternalValidator}
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
			if($this->useInternalValidator) {
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
			}
			return false;
		}

		$response = HTTP::sendPostRequest(
			self::$api_verify_server,
			'/verify',
			array(
				'privatekey' => $this->privateApiKey,
				'remoteip' => $_SERVER["REMOTE_ADDR"],
				'challenge' => $_REQUEST['recaptcha_challenge_field'],
				'response' => $_REQUEST['recaptcha_response_field'],
			)
		);
		
		// get the payload of the response and split it by newlines
		$response = explode("\r\n\r\n", $response, 2);
		list($misc, $isValid, $error) = explode("\n", $response[1]);
		
		if($isValid != 'true') {
			if($this->useInternalValidator) {
				$validator->validationError(
					$this->name, 
					_t(
						'RecaptchaField.VALIDSOLUTION', 
						"Your answer didn't match the question, please try again",
						PR_MEDIUM,
						"Recaptcha (http://recaptcha.net) provides two words in an image, and expects a user to type them in a textfield"
					), 
					"validation", 
					false
				);
			} else {
				$this->errorString = trim($error);
			}
			return false;				
		}
		
		return true;
	}
	
}
?>