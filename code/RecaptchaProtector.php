<?php
/**
 * @package recaptcha
 */

/**
 * Protecter class to handle spam protection interface
 */
class RecaptchaProtector implements SpamProtector
{

    /**
     * Return the Field that we will use in this protector
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
