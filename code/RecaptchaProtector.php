<?php
/**
 * @package recaptcha
 */

/**
 * Protecter class to handle spam protection interface 
 */
class RecaptchaProtector implements SpamProtector {
	
	/**
	 * Return the Field that we will use in this protector
	 * 
	 * @return string
	 */
	function getFormField($name = "RecaptchaField", $title = "Captcha", $value = null, $form = null, $rightTitle = null) {
		return new RecaptchaField($name, $title, $value, $form, $rightTitle);
	}
	
	/**
	 * Needed for the interface. Recaptcha does not have a feedback loop
	 *
	 * @return boolean
	 */
	function sendFeedback($object = null, $feedback = "") {
		return false;
	}
}
