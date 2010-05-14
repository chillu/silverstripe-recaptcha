# Recaptcha FormField Module

## Introduction

Provides a FormField which allows form to validate for non-bot submissions
by giving them a challenge to decrypt an image.

## Maintainer Contact

 * Ingo Schommer (Nickname: ischommer, chillu)
   <ingo (at) silverstripe (dot) com>

## Requirements

 * SilverStripe 2.3 or newer
 * curl PHP module
 * Requires [SpamProtectionModule](http://silverstripe.org/spam-protection-module/)

## Developer Documentation

 * http://doc.silverstripe.com/doku.php?id=modules:spamprotection
 * http://doc.silverstripe.com/doku.php?id=modules:recaptcha

## Installation

 * Copy the `recaptcha` directory into your main SilverStripe webroot
 * Run ?flush=1

This should go in your `mysite/_config.php`. You can get an free API key at [http://recaptcha.net/api/getkey](recatcha.net).

	RecaptchaField::$public_api_key = '<publickey>';
	RecaptchaField::$private_api_key = '<privatekey>';
	

## Usage


### As a Standalone Field

If you want to use Recaptcha field by itself, you can simply just include it as a field in your form.

	$recaptchaField = new RecaptchaField('MyCaptcha');
	$recaptchaField->jsOptions = array('theme' => 'clean'); // optional
	
See [http://recaptcha.net/apidocs/captcha/](Recaptcha API docs) for more configuration options.

### Integration with Spamprotection module

This requires the [[:modules:spamprotection|spamprotection module]] to be installed, see its documentation for details. You can use this field to protect any built informs on your website, including user comments in the [[:modules:blog]] module. 

Configuration example in `mysite/_config.php`

	SpamProtectorManager::set_spam_protector("RecaptchaProtector");

Then once you have setup this config you will need to include the spam protector field as per the instructions on the [[modules:spamprotection|spamprotection module]] page.

## Known issues:

ReCAPTCHA current does not work if the page doctype is XHTML. The API returns 
Javascript which uses "document.write", which is not supported in XHTML. 
A work-around is to always use the no-script version of the module (modify the
relevant lines in RecaptchaField.php), or to switch your webpage's doctype to 
HTML 4. See: http://www.w3schools.com/tags/tag_DOCTYPE.asp