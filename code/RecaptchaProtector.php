<?php

/**
 * Protecter class to handle spam protection interface 
 *
 * @package recaptcha
 */

class RecaptchaProtector implements SpamProtector {
	
	protected $field;
	
	/**
	 * Return the Field that we will use in this protector
	 * 
	 * @return string
	 */
	function getFieldName() {
		return 'RecaptchaField';
	}
	
	/**
	 * @return bool 
	 */
	function updateForm($form, $before=null, $fieldsToSpamServiceMapping=null) {

		$this->field = new RecaptchaField("RecaptchaField", "Captcha", null, $form);

		if ($before && $form->Fields()->fieldByName($before)) {
			$form->Fields()->insertBefore($this->field, $before);
		}
		else {
			$form->Fields()->push($this->field);
		}
		
		return $form->Fields();
	}
	
	function setFieldMapping($fieldToPostTitle, $fieldsToPostBody=null, $fieldToAuthorName=null, $fieldToAuthorUrl=null, $fieldToAuthorEmail=null, $fieldToAuthorOpenId=null) {
	
	}
	
	function sendFeedback($object = null, $feedback = "") {
		return false;
	}
}
?>