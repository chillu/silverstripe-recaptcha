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
 * @todo Does NOT work when the form is submitted via ajax, see http://recaptcha.net/apidocs/captcha/client.html for details on implementation.
 * 
 * @see http://recaptcha.net
 * @see http://recaptcha.net/api/getkey
 * 
 * @module recaptcha
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
	 * Note: Only available if {@link $useJavascript} is turned on.
	 * 
	 * @see http://recaptcha.net/apidocs/captcha/client.html
	 * @var array
	 */
	public $jsOptions = array();
	
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
	public static $api_verify_server = 'api-verify.recaptcha.net';
	
	/**
	 * Javascript-address which includes necessary logic from the recaptcha-server.
	 * Your public key is automatically inserted.
	 * 
	 * @var string
	 */
	public static $recaptcha_js_url = "http://api.recaptcha.net/challenge?k=%s";
	
	/**
	 * URL to use when {@link $useAjaxAPI} is true.
	 *
	 * @var string
	 */
	public static $recaptcha_ajax_url = "http://api.recaptcha.net/js/recaptcha_ajax.js";
	
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
	
	
	public function Field() {
		if(empty(self::$public_api_key) || empty(self::$private_api_key)) {
			user_error('RecaptchaField::FieldHolder() Please specify valid Recaptcha Keys', E_USER_ERROR);
		}
		
		if(!empty($this->jsOptions)) {
			Requirements::customScript("var RecaptchaOptions = " . $this->getJsOptionsString());
		}
		
		$previousError = Session::get("FormField.{$this->form->FormName()}.{$this->Name()}.error");
		Session::clear("FormField.{$this->form->FormName()}.{$this->Name()}.error");

		// iframe (fallback)
		$iframeURL = sprintf(
			"%s/noscript?k=%s",
			($this->useSSL) ? self::$api_ssl_server : self::$api_server,
			self::$public_api_key
		);
		if(!empty($previousError)) $iframeURL .= "&error={$previousError}";
		
		// js (main logic)
		$jsURL = sprintf(self::$recaptcha_js_url, self::$public_api_key);
		if(!empty($previousError)) $jsURL .= "&error={$previousError}";
	
		
		if($this->useAjaxAPI) {
			Requirements::javascript(self::$recaptcha_ajax_url);
			$html = '
				<script type="text/javascript">
					//<![CDATA[
					Recaptcha.create("' . self::$public_api_key . '",
					"' . $this->Name() . '", {
					   theme: "red",
					   callback: Recaptcha.focus_response_field
					});
				//]]>
				</script>
			';
		} else {
			$html = '
				<script type="text/javascript" src="' . $jsURL . '">
				</script>
			';
		}
		
		// noscript fallback
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
		
		// get the payload of the response and split it by newlines
		$response = explode("\r\n\r\n", $response, 2);
		
		list($isValid, $error) = explode("\n", $response[1]);
		
		if($isValid != 'true') {
			if(trim($error) != 'incorrect-captcha-sol') {
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
        $host = self::$api_verify_server;
        $port = 80;
		$path = '/verify';
		$req = http_build_query(array(
			'privatekey' => self::$private_api_key,
			'remoteip' => $_SERVER["REMOTE_ADDR"],
			'challenge' => $challengeStr,
			'response' => $responseStr,
		));

		$http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        if(false == ($fs = fsockopen($host, $port, $errno, $errstr, 10))) {
			user_error ('RecaptchaField::recaptchaHTTPPost(): Could not open socket');
        }
        fwrite($fs, $http_request);

        $response = '';
        while(!feof($fs))
                $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);

        return $response;
}
	
}
?>
