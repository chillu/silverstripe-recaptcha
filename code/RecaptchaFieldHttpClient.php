<?php

namespace SilverStripe\Recaptcha;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Simple HTTP client, mainly to make it mockable.
 */
class RecaptchaFieldHttpClient
{
    use Injectable, Configurable;

    /**
     * @param string $url
     * @param array  $postVars
     *
     * @return HTTPResponse
     */
    public function post($url, $postVars)
    {
        $ch = curl_init($url);
        if (!empty($server = RecaptchaField::config()->proxy_server)) {
            curl_setopt($ch, CURLOPT_PROXY, $server);
            if (!empty($auth = RecaptchaField::config()->proxy_auth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
            }
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'reCAPTCHA/PHP');
        // we need application/x-www-form-urlencoded
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postVars));
        $response = curl_exec($ch);

        $responseObj = new HTTPResponse($response, 200);
        $responseObj->addHeader('Content-Type', 'application/json');
        return $responseObj;
    }
}
