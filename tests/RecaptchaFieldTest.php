<?php

namespace SilverStripe\Recaptcha\Tests;

use SilverStripe\Control\Controller;
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
    public function testValidate()
    {
        $form = Form::create(Controller::create(), 'Form', new FieldList(), new FieldList());
        $f = new RecaptchaField('MyField');
        $f->setHTTPClient(new RecaptchaFieldTestHttpClient());
        $f->setForm($form);
        $v = new RequiredFields();

        $_REQUEST['g-recaptcha-response'] = 'valid';
        $this->assertTrue($f->validate($v));

        $_REQUEST['g-recaptcha-response'] = 'invalid';
        $this->assertFalse($f->validate($v));

        unset($_REQUEST['g-recaptcha-response']);
    }
}