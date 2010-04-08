# Recaptcha FormField Module

## Introduction

Provides a FormField which allows form to validate for non-bot submissions
by giving them a challenge to decrypt an image.

## Maintainer Contact

 * Ingo Schommer (Nickname: ischommer, chillu)
   <ingo (at) silverstripe (dot) com>

## Requirements

 * SilverStripe 2.3 or newer
 * Requires [SpamProtectionModule](http://silverstripe.org/spam-protection-module/)

## Developer Documentation

 * http://doc.silverstripe.com/doku.php?id=modules:spamprotection
 * http://doc.silverstripe.com/doku.php?id=modules:recaptcha

## Installation

 * Copy the `recaptcha` directory into your main SilverStripe webroot
 * run ?flush=1

## Known issues:

ReCAPTCHA current does not work if the page doctype is XHTML. The API returns 
Javascript which uses "document.write", which is not supported in XHTML. 
A work-around is to always use the no-script version of the module (modify the
relevant lines in RecaptchaField.php), or to switch your webpage's doctype to 
HTML 4. See: http://www.w3schools.com/tags/tag_DOCTYPE.asp