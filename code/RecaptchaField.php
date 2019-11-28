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
 * @see https://www.google.com/recaptcha/
 * @see https://www.google.com/recaptcha/admin
 */
class RecaptchaField extends FormField
{

    /**
     * Javasript-object formatted as a string,
     * which can contain options about the used theme/language etc.
     * <example>
     * "array('theme' => 'white')
     * </example>
     *
     * @see https://developers.google.com/recaptcha/docs/display
     * @var array
     */
    public $options = array();

    /**
     * @var RecaptchaField_HTTPClient
     */
    public $client;

    /**
     * Your public API key for a specific domain (get one at https://www.google.com/recaptcha/admin)
     *
     * @var string
     */
    private static $public_api_key = '';

    /**
     * Your private API key for a specific domain (get one at https://www.google.com/recaptcha/admin)
     *
     * @var string
     */
    private static $private_api_key = '';

    /**
     * Your proxy server details including the port
     *
     * @var string
     */
    private static $proxy_server = '';

    /**
     * Your proxy server authentication
     *
     * @var string
     */
    private static $proxy_auth = '';

    /**
     * Verify API server address (relative)
     *
     * @var string
     */
    private static $api_verify_server = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Javascript-address which includes necessary logic from the recaptcha-server.
     * Your public key is automatically inserted.
     *
     * @var string
     */
    private static $recaptcha_js_url = 'https://www.google.com/recaptcha/api.js';

    /**
     * @var string
     */
    private static $recaptcha_noscript_url = 'https://www.google.com/recaptcha/api/fallback?k=%s';

    /**
     * Default the noscript option to false
     * @var bool
     */
    private static $noscript_enabled = false;

    /**
     * @var string
     */
    private static $httpclient_class = 'RecaptchaField_HTTPClient';

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        // do not need a fallback title if none was defined.
        if (empty($title)) {
            $this->title = '';
        }
    }

    public function Field($properties = array())
    {
        $privateKey = self::config()->get('private_api_key');
        $publicKey = self::config()->get('public_api_key');
        if (empty($publicKey) || empty($privateKey)) {
            user_error('RecaptchaField::FieldHolder() Please specify valid Recaptcha Keys', E_USER_ERROR);
        }

        $previousError = Session::get("FormField.{$this->form->FormName()}.{$this->getName()}.error");
        Session::clear("FormField.{$this->form->FormName()}.{$this->getName()}.error");

        $recaptchaJsUrl = self::config()->get('recaptcha_js_url');
        // js (main logic)
        $jsURL = sprintf($recaptchaJsUrl, $publicKey);
        if (!empty($previousError)) {
            $jsURL .= "&error={$previousError}";
        }

        // turn options array into data attributes
        $optionString = '';
        $config = self::config()->get('options') ?: array();
        foreach ($config as $option => $value) {
            $optionString .= ' data-' . htmlentities($option) . '="' . htmlentities($value) . '"';
        }

        Requirements::javascript($jsURL);
        $fieldData = ArrayData::create(
            array(
                'public_api_key' => self::config()->get('public_api_key'),
                'name'           => $this->getName(),
                'options'        => $optionString
            )
        );
        $html = $fieldData->renderWith('recaptcha');
        if (self::config()->get('noscript_enabled')) {
            // noscript fallback
            $noscriptData = ArrayData::create(
                array(
                    'public_api_key' => self::config()->get('public_api_key')
                )
            );
            $resultHTML = $noscriptData->renderWith('recaptcha_noscript');
            $html .= $resultHTML;
        }
        return $html;
    }

    /**
     * Validate by submitting to external service
     *
     * @todo implement socket timeout handling (or switch to curl?)
     * @param Validator $validator
     * @return boolean
     */
    public function validate($validator)
    {
        /** @var array $postVars */
        if(SapphireTest::is_running_test()) {
            $postVars = $_REQUEST;
        } else {
            $postVars = Controller::curr()->getRequest()->postVars();
        }
        // don't bother querying the recaptcha-service if fields were empty
        if (!array_key_exists('g-recaptcha-response', $postVars) || empty($postVars['g-recaptcha-response'])) {
            $validator->validationError(
                $this->name,
                _t(
                    'RecaptchaField.EMPTY',
                    "Please answer the captcha question",
                    "Recaptcha (https://www.google.com/recaptcha) protects this website "
                    . "from spam and abuse."
                ),
                "validation",
                false
            );

            return false;
        }

        $response = $this->recaptchaHTTPPost($postVars['g-recaptcha-response']);

        if (!$response) {
            $validator->validationError(
                $this->name,
                _t(
                    'RecaptchaField.NORESPONSE',
                    'The recaptcha service gave no response. Please try again later.',
                    'Recaptcha (https://www.google.com/recaptcha) protects this website '
                    . 'from spam and abuse.'
                ),
                'validation',
                false
            );
            return false;
        }

        // get the payload of the response and decode it
        $response = json_decode($response, true);

        if ($response['success'] != 'true') {
            // Count some errors as "user level", meaning they raise a validation error rather than a system error
            $userLevelErrors = array('missing-input-response', 'invalid-input-response');
            $error = implode(', ', $response['error-codes']);
            if (count(array_intersect($response['error-codes'], $userLevelErrors)) === 0) {
                user_error("RecatpchaField::validate(): Recaptcha-service error: '{$error}'", E_USER_ERROR);
                return false;
            } else {
                // Internal error-string returned by recaptcha, e.g. "incorrect-captcha-sol".
                // Used to generate the new iframe-url/js-url after form-refresh.
                Session::set("FormField.{$this->form->FormName()}.{$this->getName()}.error", trim($error));
                $validator->validationError(
                    $this->name,
                    _t(
                        'RecaptchaField.VALIDSOLUTION',
                        "Your answer didn't match",
                        'Recaptcha (https://www.google.com/recaptcha) protects this website '
                        . 'from spam and abuse.'
                    ),
                    'validation',
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
     * @param string $responseStr
     * @return string Raw HTTP-response
     */
    protected function recaptchaHTTPPost($responseStr)
    {
        $postVars = array(
            'secret'   => self::config()->get('private_api_key'),
            'remoteip' => $_SERVER['REMOTE_ADDR'],
            'response' => $responseStr,
        );
        $client = $this->getHTTPClient();
        $response = $client->post(self::config()->get('api_verify_server'), $postVars);

        return $response->getBody();
    }

    /**
     * @param RecaptchaField_HTTPClient
     * @return $this
     */
    public function setHTTPClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return RecaptchaField_HTTPClient
     */
    public function getHTTPClient()
    {
        if (!$this->client) {
            $class = self::config()->get('httpclient_class');
            $this->client = new $class();
        }

        return $this->client;
    }
}

/**
 * Simple HTTP client, mainly to make it mockable.
 */
class RecaptchaField_HTTPClient extends SS_Object
{

    /**
     * @param String $url
     * @param $postVars
     * @return String HTTPResponse
     */
    public function post($url, $postVars)
    {
        $ch = curl_init($url);
        $proxyServer = Config::inst()->get('RecaptchaField', 'proxy_server');
        if (!empty($proxyServer)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
            $proxyAuth = Config::inst()->get('RecaptchaField', 'proxy_auth');
            if (!empty($proxyAuth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
            }
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'reCAPTCHA/PHP');
        // we need application/x-www-form-urlencoded
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postVars));
        $response = curl_exec($ch);

        if (class_exists('SS_HTTPResponse')) {
            $responseObj = new SS_HTTPResponse();
        } else {
            // 2.3 backwards compat
            $responseObj = new HttpResponse();
        }
        $responseObj->setBody($response); // 2.2. compat
        $responseObj->addHeader('Content-Type', 'application/json');
        return $responseObj;
    }
}
