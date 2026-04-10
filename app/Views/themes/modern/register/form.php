<?= $this->extend('themes/modern/register/layout') ?>
<?= $this->section('content') ?>
<?php helper('form'); ?>
<div class="register-card">
	<div class="register-header">
		<div class="logo-wrapper">
			<img src="<?php echo $config->baseURL . 'public/images/' . $setting_aplikasi['logo_register'] ?>?r=<?=time()?>" alt="Logo" class="register-logo">
		</div>
		<h1 class="register-title">Buat Akun Baru</h1>
		<p class="register-subtitle">Daftar dengan email aktif untuk mulai menggunakan aplikasi.</p>
	</div>

	<?php if (!empty($message)) : ?>
		<div class="alert alert-danger modern-alert">
			<i class="fa fa-exclamation-circle"></i>
			<?php
			if (is_array($message) && isset($message['message']) && is_array($message['message'])) {
				echo implode('<br>', array_map('esc', $message['message']));
			} elseif (is_array($message) && !empty($message['message'])) {
				echo $message['message'];
			}
			?>
		</div>
	<?php endif; ?>

	<form action="<?= current_url() ?>" method="post" accept-charset="utf-8" class="register-form" id="register-form" novalidate>
		<div class="form-field">
			<label for="nama-field" class="field-label">
				<i class="fa fa-user"></i> Nama Lengkap
			</label>
			<input type="text" id="nama-field" name="nama" value="<?= esc(set_value('nama')) ?>" class="modern-input" placeholder="Masukkan nama lengkap" autocomplete="name" required>
			<small class="field-feedback" data-feedback-for="nama"></small>
		</div>

		<div class="form-field">
			<label for="email-field" class="field-label">
				<i class="fa fa-envelope"></i> Email
			</label>
			<input type="email" id="email-field" name="email" value="<?= esc(set_value('email', '')) ?>" class="modern-input" placeholder="nama@email.com" autocomplete="email" inputmode="email" required>
			<small class="field-feedback" data-feedback-for="email"></small>
			<p class="field-note">Email ini akan menjadi identitas akun Anda untuk login.</p>
		</div>

		<div class="form-field">
			<label for="password-field" class="field-label">
				<i class="fa fa-lock"></i> Password
			</label>
			<input type="password" id="password-field" name="password" class="modern-input" placeholder="Buat password" autocomplete="new-password" required>
			<small class="field-feedback" data-feedback-for="password"></small>
			<div class="pwstrength_viewport_progress"></div>
			<p class="field-note">Gunakan password yang kuat, minimal 9 karakter dengan kombinasi huruf besar, huruf kecil, dan angka.</p>
		</div>

		<div class="form-field">
			<label for="password-confirm-field" class="field-label">
				<i class="fa fa-shield-alt"></i> Konfirmasi Password
			</label>
			<input type="password" id="password-confirm-field" name="password_confirm" class="modern-input" placeholder="Ulangi password" autocomplete="new-password" required>
			<small class="field-feedback" data-feedback-for="password_confirm"></small>
		</div>

		<div class="register-actions">
			<button type="submit" name="submit" value="submit" class="btn-register-primary" id="register-submit" disabled>
				<span>Register</span>
				<i class="fa fa-arrow-right"></i>
			</button>
			<?= csrf_formfield() ?>
		</div>

		<div class="register-links">
			<a href="<?= $config->baseURL ?>login">
				<i class="fa fa-arrow-left"></i> Kembali ke Login
			</a>
		</div>
	</form>
</div>

<style>
:root {
	--register-primary: #4e73df;
	--register-primary-hover: #2e59d9;
	--register-text: #212529;
	--register-muted: #6c757d;
	--register-border: #e3e6f0;
}

.register-card {
	background: #ffffff;
	border: 1px solid var(--register-border);
	border-radius: 16px;
	padding: 28px 22px;
	box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}

.register-header {
	text-align: center;
	margin-bottom: 24px;
}

.logo-wrapper {
	margin-bottom: 16px;
}

.register-logo {
	max-height: 76px;
	width: auto;
}

.register-title {
	margin: 0;
	font-size: 1.5rem;
	font-weight: 700;
	color: var(--register-text);
}

.register-subtitle {
	margin: 10px auto 0;
	max-width: 340px;
	font-size: 0.95rem;
	line-height: 1.6;
	color: var(--register-muted);
}

.modern-alert {
	border-radius: 10px;
	padding: 12px 14px;
	margin-bottom: 18px;
	font-size: 0.92rem;
}

.register-form {
	display: flex;
	flex-direction: column;
	gap: 18px;
}

.form-field {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.field-label {
	font-size: 0.9rem;
	font-weight: 600;
	color: var(--register-text);
}

.field-label i {
	color: var(--register-primary);
	margin-right: 6px;
}

.modern-input {
	width: 100%;
	border: 1px solid var(--register-border);
	border-radius: 10px;
	padding: 13px 14px;
	font-size: 0.96rem;
	color: var(--register-text);
	background: #ffffff;
	transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.modern-input:focus {
	outline: none;
	border-color: var(--register-primary);
	box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.12);
}

.field-note {
	margin: 0;
	font-size: 0.82rem;
	line-height: 1.55;
	color: var(--register-muted);
}

.field-feedback {
	display: none;
	margin: 0;
	font-size: 0.8rem;
	line-height: 1.45;
}

.form-field.is-invalid .modern-input {
	border-color: #dc3545;
	box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}

.form-field.is-invalid .field-feedback {
	display: block;
	color: #dc3545;
}

.form-field.is-valid .modern-input {
	border-color: #198754;
	box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12);
}

.form-field.is-valid .field-feedback {
	display: block;
	color: #198754;
}

.register-actions {
	margin-top: 4px;
}

.btn-register-primary {
	width: 100%;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	padding: 14px 16px;
	border: none;
	border-radius: 10px;
	background: var(--register-primary);
	color: #ffffff;
	font-size: 0.96rem;
	font-weight: 600;
	box-shadow: 0 8px 18px rgba(78, 115, 223, 0.18);
	transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-register-primary:disabled {
	background: #b7c3e6;
	box-shadow: none;
	cursor: not-allowed;
	transform: none;
}

.btn-register-primary:hover {
	background: var(--register-primary-hover);
	transform: translateY(-1px);
	box-shadow: 0 10px 24px rgba(78, 115, 223, 0.24);
}

.register-links {
	display: flex;
	flex-direction: column;
	gap: 10px;
	align-items: center;
	padding-top: 6px;
}

.register-links a {
	color: var(--register-primary);
	text-decoration: none;
	font-size: 0.88rem;
	font-weight: 500;
}

.register-links a:hover {
	text-decoration: underline;
}

@media (min-width: 576px) {
	.register-card {
		padding: 34px 32px;
	}

	.register-links {
		flex-direction: row;
		justify-content: space-between;
	}
}
</style>
<?= $this->endSection() ?>
