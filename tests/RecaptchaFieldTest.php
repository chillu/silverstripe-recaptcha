<?php

namespace SilverStripe\Recaptcha\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Recaptcha\RecaptchaField;

/**
 * Class RecaptchaFieldTest
 *
 * @package recaptcha
 */
class RecaptchaFieldTest extends SapphireTest
{
    public function testValidateWithValidResponse()
    {
        $form = Form::create(Controller::create(), 'Form', new FieldList(), new FieldList());
        $f = new RecaptchaField('MyField');
        $f->setHTTPClient(new RecaptchaFieldTestHttpClient());
        $f->setForm($form);
        $v = new RequiredFields();

        $origRequest = Controller::curr()->getRequest();
        $origSession = $origRequest->getSession();

        $request = new HTTPRequest('POST', '/', [], [
            'g-recaptcha-response' => 'valid',
        ]);
        $request->setSession($origSession);
        Controller::curr()->setRequest($request);
        $this->assertTrue($f->validate($v));

        Controller::curr()->setRequest($origRequest);
    }

    public function testValidateWithInvalidResponse()
    {
        $form = Form::create(Controller::create(), 'Form', new FieldList(), new FieldList());
        $f = new RecaptchaField('MyField');
        $f->setHTTPClient(new RecaptchaFieldTestHttpClient());
        $f->setForm($form);
        $v = new RequiredFields();

        $origRequest = Controller::curr()->getRequest();
        $origSession = $origRequest->getSession();

        $request = new HTTPRequest('POST', '/', [], [
            'g-recaptcha-response' => 'invalid',
        ]);
        $request->setSession($origSession);
        Controller::curr()->setRequest($request);
        $this->assertFalse($f->validate($v));

        Controller::curr()->setRequest($origRequest);
    }
}