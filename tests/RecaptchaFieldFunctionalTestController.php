<?php

namespace SilverStripe\Recaptcha\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Recaptcha\RecaptchaField;

/**
 * Class RecaptchaFieldFunctionalTestController
 *
 * @package recaptcha
 */
class RecaptchaFieldFunctionalTestController extends Controller implements TestOnly
{

    private static $allowed_actions = [
        'Form'
    ];

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links('RecaptchaFieldFunctionalTest_Controller', $action);
    }

    public function Form()
    {
        $form = Form::create($this, 'Form', FieldList::create($f = RecaptchaField::create('MyRecaptchaField')), FieldList::create(FormAction::create('doSubmit', 'submit')));
        $f->setHTTPClient(new RecaptchaFieldTestHttpClient());

        return $form;
    }

    public function doSubmit($data, $form)
    {
        return 'submitted';
    }
}