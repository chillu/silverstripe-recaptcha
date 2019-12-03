<?php

namespace SilverStripe\Recaptcha;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\Validator;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Provides an {@link FormField} which allows form to validate for non-bot submissions
 * by giving them a challenge to decrypt an image.
 * Generation and validation of captchas is handled on external server.
 * This field doesn't save anything back to the form,
 * and only submits recaptcha-related form-data to an external server.
 *
 * @see https://www.google.com/recaptcha/
 * @see https://www.google.com/recaptcha/admin
 *
 * @package recaptcha
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
     * @var RecaptchaFieldHttpClient
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
    private static $httpclient_class = RecaptchaFieldHttpClient::class;

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
        $request = Controller::curr()->getRequest();

        $privateKey = self::config()->get('private_api_key');
        $publicKey = self::config()->get('public_api_key');
        if (empty($publicKey) || empty($privateKey)) {
            user_error('SilverStripe\Recaptcha\RecaptchaField::FieldHolder() Please specify valid Recaptcha Keys', E_USER_ERROR);
        }

        $previousError = $request->getSession()->get("FormField.{$this->form->FormName()}.{$this->getName()}.error");
        $request->getSession()->clear("FormField.{$this->form->FormName()}.{$this->getName()}.error");

        // turn options array into data attributes
        $optionString = '';
        $config = array_merge(
        	self::config()->get('options') ?: array(),
        	$this->options
        );        

        foreach ($config as $option => $value) {
            $optionString .= ' data-' . htmlentities($option) . '="' . htmlentities($value) . '"';
        }

        $jsURL = self::config()->get('recaptcha_js_url');
        if(isset($config['hl'])) {
        	$jsURL = HTTP::setGetVar('hl', $config['hl'], $jsURL);
        }
        if (!empty($previousError)) {
            $jsURL = HTTP::setGetVar('error', $previousError, $jsURL);
        }

        Requirements::javascript($jsURL);

        $fieldData = ArrayData::create(
            array(
                'public_api_key' => self::config()->get('public_api_key'),
                'name'           => $this->getName(),
                'options'        => $optionString
            )
        );
        $html = $fieldData->renderWith('SilverStripe\Recaptcha\Recaptcha');
        if (self::config()->get('noscript_enabled')) {
            // noscript fallback
            $noscriptData = ArrayData::create(
                array(
                    'public_api_key' => self::config()->get('public_api_key')
                )
            );
            $resultHTML = $noscriptData->renderWith('SilverStripe\Recaptcha\Recaptcha_NoScript');
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
        /** @var HTTPRequest $request */
        $request = Controller::curr()->getRequest();
        $data = $request->postVars();

        // don't bother querying the recaptcha-service if fields were empty
        if (!array_key_exists('g-recaptcha-response', $data) || empty($data['g-recaptcha-response'])) {
            $validator->validationError(
                $this->name,
                _t(
                    'SilverStripe\Recaptcha\RecaptchaField.EMPTY',
                    "Please answer the captcha question",
                    "Recaptcha (https://www.google.com/recaptcha) protects this website "
                    . "from spam and abuse."
                ),
                "validation",
                false
            );

            return false;
        }

        $response = $this->recaptchaHttpPost($data['g-recaptcha-response']);

        if (!$response) {
            $validator->validationError(
                $this->name,
                _t(
                    'SilverStripe\Recaptcha\RecaptchaField.NORESPONSE',
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
                user_error("SilverStripe\Recaptcha\RecatpchaField::validate(): Recaptcha-service error: '{$error}'", E_USER_ERROR);
                return false;
            } else {
                // Internal error-string returned by recaptcha, e.g. "incorrect-captcha-sol".
                // Used to generate the new iframe-url/js-url after form-refresh.
                $request->getSession()->set("FormField.{$this->form->FormName()}.{$this->getName()}.error", trim($error));
                $validator->validationError(
                    $this->name,
                    _t(
                        'SilverStripe\Recaptcha\RecaptchaField.VALIDSOLUTION',
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
    protected function recaptchaHttpPost($responseStr)
    {
        $postVars = array(
            'secret'   => self::config()->get('private_api_key'),
            'remoteip' => $_SERVER['REMOTE_ADDR'],
            'response' => $responseStr,
        );
        $client = $this->getHttpClient();
        $response = $client->post(self::config()->get('api_verify_server'), $postVars);

        return $response->getBody();
    }

    /**
     * @param RecaptchaField_HTTPClient
     * @return $this
     */
    public function setHttpClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return RecaptchaFieldHttpClient
     */
    public function getHttpClient()
    {
        if (!$this->client) {
            $class = self::config()->get('httpclient_class');
            $this->client = new $class();
        }

        return $this->client;
    }
}