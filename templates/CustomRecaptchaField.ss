<div id="recaptcha_widget">
	<div id="recaptcha_image"></div>
	<div class="recaptcha_only_if_incorrect_sol" style="color: red">Incorrect, please try again</div>
	<span class="recaptcha_only_if_image">Enter the words above:</span>
	<span class="recaptcha_only_if_audio">Type what you hear:</span><br />
	<input type="text" id="recaptcha_response_field" name="recaptcha_response_field">
	<div class="retryoptions">
		<a href="javascript:Recaptcha.reload()">Get another CAPTCHA</a>
	</div>
	<div class="recaptcha_only_if_image">
		<a href="javascript:Recaptcha.switch_type(&#39;audio&#39;)">Get an audio CAPTCHA</a>
	</div>
	<div class="recaptcha_only_if_audio">
		<a href="javascript:Recaptcha.switch_type(&#39;image&#39;)">Get an image CAPTCHA</a>
	</div>
	<a class="help" href="javascript:Recaptcha.showhelp()">Help</a>
</div>