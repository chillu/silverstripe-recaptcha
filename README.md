# Recaptcha FormField Module

## Introduction

Provides a FormField which allows form to validate for non-bot submissions
by giving them a challenge to decrypt an image.

## Maintainer Contact

 * Ingo Schommer (Nickname: ischommer, chillu)
   <ingo (at) silverstripe (dot) com>

## Requirements

 * SilverStripe 3.0.0 or newer
 * curl PHP module
 * Requires [SpamProtectionModule](http://silverstripe.org/spam-protection-module/)

## Developer Documentation

 * http://doc.silverstripe.com/doku.php?id=modules:spamprotection
 * http://doc.silverstripe.com/doku.php?id=modules:recaptcha

## Installation

 * Copy the `recaptcha` directory into your main SilverStripe webroot
 * Run ?flush=1

This should go in your `mysite/_config.php`. You can get an free API key at [http://www.google.com/recaptcha](https://www.google.com/recaptcha/admin/create)

	RecaptchaField::$public_api_key = '<publickey>';
	RecaptchaField::$private_api_key = '<privatekey>';

## Usage

### As a Standalone Field

If you want to use Recaptcha field by itself, you can simply just include it as a field in your form.

	$recaptchaField = new RecaptchaField('MyCaptcha');
	$recaptchaField->jsOptions = array('theme' => 'clean'); // optional
	
See [Recaptcha API docs](https://developers.google.com/recaptcha/intro) for more configuration options.

### Integration with Spamprotection module

This requires the [[:modules:spamprotection|spamprotection module]] to be installed, see its documentation for details. You can use this field to protect any built informs on your website, including user comments in the [[:modules:blog]] module. 

Configuration example in `mysite/_config.php`

	SpamProtectorManager::set_spam_protector('RecaptchaProtector');

Then once you have setup this config you will need to include the spam protector field as per the instructions on the [[modules:spamprotection|spamprotection module]] page.

## Known issues:

### Problems with page doctype XHTML

ReCAPTCHA current does not work if the page doctype is XHTML. The API returns 
Javascript which uses "document.write", which is not supported in XHTML. 
A work-around is to always use the no-script version of the module (modify the
relevant lines in RecaptchaField.php), or to switch your webpage's doctype to 
HTML 4. See: http://www.w3schools.com/tags/tag_DOCTYPE.asp

### Problems with IE9
There is an issue that with certain site configurations, forms just won't submit in IE9.    
Several threads are pointing to that IE9 and reCaptcha just won't work together, and this thread suggests to force Internet Explorer in IE8 mode:
http://answers.microsoft.com/en-us/ie/forum/ie9-windows_7/ie9-is-not-capturing-recaptcha-form-fields/6479d1f0-6f67-e011-8dfc-68b599b31bf5?msgId=44883943-036d-e011-8dfc-68b599b31bf5&page=1

What can be done to circumvent this (but isn't optimal, as many sites look much better in IE9/10 than in IE8), is to force all `UserDefineForms` pages to be rendered in IE8 mode like described below (off course that can be extended to include other page types as well).    
Though it's not optimal at all, it can be a good trade-off, considering that users are at least able to submit forms.

```php
/*
 * Forcing the browser to render in IE8 mode for UserDefineForms
 * This is due to problems with IE9 and reCaptcha
 */
public function ForceIE8(){
	$class = $this->ClassName;
	if ($class == 'UserDefinedForm') {
		return true;
	}
}
```
...and adding this as first in the head tag

```html
<% if ForceIE8 %>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" >
<% end_if %>
```



