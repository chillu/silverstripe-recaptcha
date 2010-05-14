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