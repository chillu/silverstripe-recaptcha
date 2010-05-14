<?php
/**
 * @package recaptcha
 */

class RecatpchaFieldTest extends SapphireTest {
	
	function testValidate() {
		$form = new Form(new Controller(), 'Form', new FieldSet(), new FieldSet());
		$f = new RecaptchaField('MyField');
		$f->setHTTPClient(new RecatpchaFieldTest_HTTPClient());
		$f->setForm($form);
		$v = new RequiredFields();
		
		$_REQUEST['recaptcha_challenge_field'] = 'valid';
		$_REQUEST['recaptcha_response_field'] = 'response';
		$this->assertTrue($f->validate($v));
		
		$_REQUEST['recaptcha_challenge_field'] = 'invalid';
		$_REQUEST['recaptcha_response_field'] = 'response';
		$this->assertFalse($f->validate($v));
		
		unset($_REQUEST['recaptcha_challenge_field']);
		unset($_REQUEST['recaptcha_response_field']);
	}
}

class RecatpchaFieldTest_HTTPClient extends RecaptchaField_HTTPClient implements TestOnly {
	function post($url, $postVars) {
		if($postVars['challenge'] == 'valid') {
			return new SS_HTTPResponse("true\nNo errors");
		}
		
		if($postVars['challenge'] == 'invalid') {
			return new SS_HTTPResponse("false\nincorrect-captcha-sol");
		}
		
		return new SS_HTTPResponse();
	}
}