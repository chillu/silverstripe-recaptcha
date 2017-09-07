<?php

namespace SilverStripe\Recaptcha\Tests;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Recaptcha\RecaptchaFieldHttpClient;

/**
 * Class RecaptchaFieldTestHttpClient
 *
 * @package recaptcha
 */
class RecaptchaFieldTestHttpClient extends RecaptchaFieldHttpClient implements TestOnly
{
    public function post($url, $postVars)
    {
        if ($postVars['response'] == 'valid') {
            $response = new HTTPResponse();
            $data = [
                'success'      => true,
                'challenge_ts' => date('c'),
                // timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
                'hostname'     => $_SERVER['HTTP_HOST'],
                // the hostname of the site where the reCAPTCHA was solved
            ];
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(Convert::array2json($data));
            return $response;
        }

        if ($postVars['response'] == 'invalid') {
            $response = new HTTPResponse();
            $data = [
                'success'      => false,
                'challenge_ts' => date('c'),
                'hostname'     => $_SERVER['HTTP_HOST'],
                'error-codes'  => [
                    'invalid-input-response'
                ]
            ];
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(Convert::array2json($data));
            return $response;
        }

        return new HTTPResponse();
    }
}