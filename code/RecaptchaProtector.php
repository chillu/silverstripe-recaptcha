<?php

namespace SilverStripe\Recaptcha;

use SilverStripe\Control\Director;
use SilverStripe\SpamProtection\SpamProtector;

/**
 * Protecter class to handle spam protection interface
 *
 * @package recaptcha
 */
class RecaptchaProtector implements SpamProtector
{

    /**
     * Return the Field that we will use in this protector
     *
     * @param string $name
     * @param string $title
     * @param null   $value
     *
     * @return string
     */
    public function getFormField($name = "RecaptchaField", $title = "Captcha", $value = null)
    {
        $field = new RecaptchaField($name, $title, $value);
        $field->useSSL = Director::is_https();

        return $field;
    }

    /**
     * Not used by Recaptcha
     */
    public function setFieldMapping($fieldMapping)
    {
    }

}
