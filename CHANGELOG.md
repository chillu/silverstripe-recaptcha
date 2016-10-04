# 0.1:
- initial release

# 0.2
- switched to static configuration of API keys
- session-based errors passed through to recaptcha
- using custom HTTP POST generation (HTTP::sendPostRequest() has performance issues here)

# 0.3
- Now sits ontop of Spam Protection Field which has automatic hooks into default forms.
	Also enables recaptcha to be included in a userform
	
# 0.4 (2010-05-14)
- Using curl for HTTP communication
- Fixed timeout problems
- Added basic unit tests

# 0.5 (2016-10-10)
- Use YML configuration (e.g. `Recaptcha.api_verify_server`) instead of `$js_options` class static
- Removed `$useSSL` option (now always on SSL)
- Removed `$valid_languages`, use `hl` parameter in the options (see https://developers.google.com/recaptcha/docs/display)
- Removed `$useAjaxAPI` (Recaptcha v2 works differently now)
- `<noscript>` fallback requires additional configuration now (https://developers.google.com/recaptcha/docs/faq)