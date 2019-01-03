!function($, window, document, _undefined)
{
	"use strict";

	XF.PasswordHideShow = XF.Element.newHandler({
		options: {
			showText: null,
			hideText: null
		},

		$password: null,
		$checkbox: null,
		$label: null,

		init: function()
		{
			this.$password = this.$target.find('.js-password');

			var $container = this.$target.find('.js-hideShowContainer');
			this.$checkbox = $container.find('input[type="checkbox"]');
			this.$label = $container.find('.iconic-label');

			this.$checkbox.on('change', XF.proxy(this, 'toggle'));
		},

		toggle: function(e)
		{
			var $checkbox = this.$checkbox,
				$password = this.$password,
				$label = this.$label;

			if ($checkbox.is(':checked'))
			{
				$password.attr('type', 'text');
				$label.html(this.options.hideText);
			}
			else
			{
				$password.attr('type', 'password');
				$label.html(this.options.showText);
			}
		}
	});

	XF.PasswordStrength = XF.Element.newHandler({
		options: {},

		$password: null,
		$meter: null,
		$meterText: null,

		language: {},

		init: function()
		{
			this.$password = this.$target.find('.js-password');
			this.$meter = this.$target.find('.js-strengthMeter');
			this.$meterText = this.$target.find('.js-strengthText');

			this.language = $.parseJSON($('.js-zxcvbnLanguage').first().html()) || {};

			this.$password.on('input', XF.proxy(this, 'input'));
		},

		input: function()
		{
			var password = this.$password.val(),
				result = zxcvbn(password),
				score = result.score, value,
				message = result.feedback.warning || '';

			if (password)
			{
				value = (score + 1) * 20;

				if (score >= 4)
				{
					message = 'This is a very strong password';
				}
				else if (score >= 3)
				{
					message = 'This is a reasonably strong password';
				}
				else if (!message)
				{
					message = 'The chosen password could be stronger';
				}
			}
			else
			{
				message = 'Entering a password is required';
				value = 0;
			}

			this.$meter.val(value);
			this.$meterText.text(this.language[message] || '');
		}
	});

	XF.Element.register('password-hide-show', 'XF.PasswordHideShow');
	XF.Element.register('password-strength', 'XF.PasswordStrength');
}
(jQuery, window, document);