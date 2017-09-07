<?php
/**
 * @package recaptcha
 */

namespace SilverStripe\Recaptcha\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;

/**
 * Class RecaptchaFieldFunctionalTest
 * @package recaptcha
 * @author Ingo Schommer, SilverStripe Ltd.
 */
class RecaptchaFieldFunctionalTest extends FunctionalTest
{

    protected $orig = [];

    public function setUp()
    {
        parent::setUp();

        $this->orig['public_api_key'] = Config::inst()->get('RecaptchaField', 'public_api_key');
        $this->orig['private_api_key'] = Config::inst()->get('RecaptchaField', 'private_api_key');
        Config::modify()->set('RecaptchaField', 'public_api_key', 'test');
        Config::modify()->set('RecaptchaField', 'private_api_key', 'test');
        Config::modify()->set('RecaptchaField', 'noscript_enabled', true);
    }

    public function tearDown()
    {
        Config::modify()->set('RecaptchaField', 'public_api_key', $this->orig['public_api_key']);
        Config::modify()->set('RecaptchaField', 'private_api_key', $this->orig['private_api_key']);

        parent::tearDown();
    }

    public function testValidSubmission()
    {
        $this->get('RecaptchaFieldFunctionalTest_Controller');
        $data = [
            'g-recaptcha-response' => 'valid',
        ];
        $response = $this->submitForm('Form_Form', null, $data);
        $this->assertEquals('submitted', $response->getBody());
    }

    public function testInvalidSubmission()
    {
        $this->get('RecaptchaFieldFunctionalTest_Controller');
        $data = [
            'g-recaptcha-response' => 'invalid',
        ];
        $response = $this->submitForm('Form_Form', null, $data);
        $body = $response->getBody();
        $this->assertContains('Your answer didn&#039;t match the captcha', $body);
    }

}

