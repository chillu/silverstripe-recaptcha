# reCAPTCHA FormField Module

[![Build Status](https://secure.travis-ci.org/chillu/silverstripe-recaptcha.png)](http://travis-ci.org/chillu/silverstripe-recaptcha)

## Introduction

Provides a FormField which allows form to validate for non-bot submissions
using Google's reCAPTCHA service

## Maintainer Contact

 * Ingo Schommer (Nickname: ischommer, chillu)
   <ingo (at) silverstripe (dot) com>

## Requirements

 * SilverStripe framework 3.1 or newer
 * curl PHP module
 * Requires [spamprotection](http://silverstripe.org/spam-protection-module/) module

## Installation

 * Copy the `recaptcha` directory into your main SilverStripe webroot
 * Run ?flush=1

This should go in your `mysite/_config/recaptha.yml`. You can get an free API key at [https://www.google.com/recaptcha](https://www.google.com/recaptcha/admin/create)

```
RecaptchaField:
  public_api_key: "your-site-key"
  private_api_key: "your-secret-key"
```

If using on a site requiring a proxy server for outgoing traffic then you can set these additional
options in your `mysite/_config/recaptcha.yml` by adding. 
```
  proxy_server: "proxy_address"
  proxy_auth: "username:password"
```

To use the noscript fallback method, add the key `noscript_enabled: true` to your yml.

To change the language, add it to an array of options to your yml
```
  options: 
    hl: NL
    theme: dark
    type: audio
    size: compact
```

See https://developers.google.com/recaptcha/docs/display#render_param for all available parameters

## Usage

### As a Standalone Field

If you want to use reCAPTCHA field by itself, you can simply just include it as a field in your form.

	$recaptchaField = new RecaptchaField('MyCaptcha');
	$recaptchaField->options = array('theme' => 'light'); // optional
	
See [reCAPTCHA docs](https://developers.google.com/recaptcha/docs/display#render_param) for more configuration options.

### Integration with spamprotection module

This requires the [spamprotection](https://github.com/silverstripe/silverstripe-spamprotection) module to be installed, see its documentation for details. You can use this field to protect any built informs on your website, including user comments in the [[:modules:blog]] module. 

Configuration example in `mysite/_config/spamprotection.yml`

	---
	name: spamprotection
	---
	FormSpamProtectionExtension:
	  default_spam_protector: RecaptchaProtector
  

Then once you have setup this config you will need to include the spam protector field as per the instructions on the [spamprotection](https://github.com/silverstripe/silverstripe-spamprotection) page.

## Known issues:

### Problems with page doctype XHTML

reCAPTCHA current does not work if the page doctype is XHTML. The API returns 
Javascript which uses "document.write", which is not supported in XHTML. 
A work-around is to always use the no-script version of the module (modify the
relevant lines in RecaptchaField.php), or to switch your webpage's doctype to 
HTML 4. See: http://www.w3schools.com/tags/tag_DOCTYPE.asp
