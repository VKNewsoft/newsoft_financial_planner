jQuery(function ($) {
	"use strict";

	var $form = $('#register-form');
	if (!$form.length) {
		return;
	}

	var $submit = $('#register-submit');
	var $name = $('#nama-field');
	var $email = $('#email-field');
	var $password = $('#password-field');
	var $confirm = $('#password-confirm-field');

	function setFieldState($input, state, message) {
		var $field = $input.closest('.form-field');
		var $feedback = $field.find('.field-feedback');

		$field.removeClass('is-valid is-invalid');
		$feedback.text('');

		if (!state) {
			return;
		}

		$field.addClass(state);
		$feedback.text(message || '');
	}

	function validateName() {
		var value = $.trim($name.val());
		if (!value) {
			setFieldState($name, 'is-invalid', 'Nama lengkap wajib diisi.');
			return false;
		}

		if (value.length < 5) {
			setFieldState($name, 'is-invalid', 'Nama minimal 5 karakter.');
			return false;
		}

		setFieldState($name, 'is-valid', 'Nama sudah valid.');
		return true;
	}

	function validateEmail() {
		var value = $.trim($email.val());
		var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

		if (!value) {
			setFieldState($email, 'is-invalid', 'Email wajib diisi.');
			return false;
		}

		if (!emailPattern.test(value)) {
			setFieldState($email, 'is-invalid', 'Format email belum valid.');
			return false;
		}

		setFieldState($email, 'is-valid', 'Format email sudah valid.');
		return true;
	}

	function validatePassword() {
		var value = $password.val();
		if (!value) {
			setFieldState($password, 'is-invalid', 'Password wajib diisi.');
			return false;
		}

		if (value.length < 9) {
			setFieldState($password, 'is-invalid', 'Password minimal 9 karakter.');
			return false;
		}

		setFieldState($password, 'is-valid', 'Password siap digunakan.');
		return true;
	}

	function validateConfirmation() {
		var password = $password.val();
		var confirmation = $confirm.val();

		if (!confirmation) {
			setFieldState($confirm, 'is-invalid', 'Konfirmasi password wajib diisi.');
			return false;
		}

		if (password !== confirmation) {
			setFieldState($confirm, 'is-invalid', 'Konfirmasi password harus sama.');
			return false;
		}

		setFieldState($confirm, 'is-valid', 'Konfirmasi password sudah cocok.');
		return true;
	}

	function updateSubmitState() {
		var isValid = validateName() && validateEmail() && validatePassword() && validateConfirmation();
		$submit.prop('disabled', !isValid);
		return isValid;
	}

	$name.on('input blur', function () {
		validateName();
		updateSubmitState();
	});

	$email.on('input blur', function () {
		validateEmail();
		updateSubmitState();
	});

	$password.on('input blur', function () {
		validatePassword();
		validateConfirmation();
		updateSubmitState();
	});

	$confirm.on('input blur', function () {
		validateConfirmation();
		updateSubmitState();
	});

	$form.on('submit', function (event) {
		if (!updateSubmitState()) {
			event.preventDefault();
		}
	});

	$submit.prop('disabled', true);
});
