<?= $this->extend('themes/modern/register/layout') ?>
<?= $this->section('content') ?>
<div class="login-glass-container">
	<div class="login-header">
		<div class="logo-wrapper">
			<img src="<?php echo $config->baseURL . '/public/images/' . $setting_aplikasi['logo_login']?>" alt="Logo" class="login-logo">
		</div>
		<h1 class="login-title">Financial Planner</h1>
		<p class="login-tagline">Pantau arus kas pribadi, anggaran, dan target keuangan dari satu dashboard.</p>
		<?php if (!empty($desc)) {
			echo '<p class="login-subtitle">' . $desc . '</p>';
		}?>
	</div>
	<div class="login-body">
		<?php
		
		if (!empty($message)) {?>
			<div class="alert alert-danger modern-alert">
				<i class="fa fa-exclamation-circle"></i> <?=$message?>
			</div>
		<?php }
		?>
		<div class="login-form-wrapper">
			<form method="post" action="" class="modern-login-form" novalidate>
				<div class="form-field">
					<label for="username-field" class="field-label">
						<i class="fa fa-user"></i> Email
					</label>
					<input type="text" id="username-field" name="username" value="<?= esc(old('username', '')) ?>" class="modern-input" placeholder="Masukkan email Anda" aria-label="Email" autocomplete="username" required>
				</div>
				<div class="form-field">
					<label for="password-field" class="field-label">
						<i class="fa fa-lock"></i> Password
					</label>
					<div class="password-input-wrapper">
						<input id="password-field" type="password" name="password" class="modern-input" placeholder="Masukkan password" aria-label="Password" required>
						<button type="button" class="password-toggle-btn" aria-pressed="false" aria-label="Tampilkan password" title="Tampilkan / Sembunyikan password">
							<i class="fa fa-eye" aria-hidden="true"></i>
						</button>
					</div>
				</div>

				<div class="form-actions">
					<button id="btn-submit-login" type="submit" class="btn-login-primary" name="submit">
						<span>Masuk ke Dashboard</span>
						<i class="fa fa-arrow-right"></i>
					</button>
					<?php
						$form_token = $auth->generateFormToken('login_form_token');
					?>
					<?= csrf_formfield() ?>
				</div>
				<div class="login-links">
					<a href="<?=$config->baseURL?>recovery" class="link-recovery">
						<i class="fa fa-key"></i> Lupa Password?
					</a>
					<a href="<?=$config->baseURL?>register" class="link-register">
						<i class="fa fa-user-plus"></i> Daftar Akun
					</a>
				</div>
			</form>
		</div>
	</div>
</div>
<style>
/* Clean Modern Login - Light Theme */
:root {
	--login-primary: #0f766e;
	--login-primary-hover: #0b5e58;
	--login-accent: #f59e0b;
	--login-text: #0f172a;
	--login-muted: #64748b;
	--login-border: rgba(148, 163, 184, 0.28);
	--login-bg: rgba(255, 255, 255, 0.96);
}

.login-glass-container {
	background:
		linear-gradient(145deg, rgba(255,255,255,0.98), rgba(240,253,250,0.92));
	border: 1px solid var(--login-border);
	border-radius: 24px;
	padding: 28px 32px 32px;
	box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
	position: relative;
	max-width: 460px;
	overflow: hidden;
}

.login-glass-container:before {
	content: "";
	position: absolute;
	inset: -80px auto auto -40px;
	width: 180px;
	height: 180px;
	background: radial-gradient(circle, rgba(15,118,110,0.16), rgba(15,118,110,0));
}

.login-metrics {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	margin-bottom: 24px;
	position: relative;
	z-index: 1;
}

.login-metrics span {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 8px 12px;
	border-radius: 999px;
	font-size: 12px;
	font-weight: 700;
	letter-spacing: 0.2px;
	background: rgba(15,118,110,0.09);
	color: var(--login-primary);
}

.login-header {
	text-align: center;
	margin-bottom: 26px;
	position: relative;
	z-index: 1;
}

.logo-wrapper {
	margin-bottom: 16px;
}

.login-logo {
	max-height: 80px;
	width: auto;
}

.login-title {
	font-size: 30px;
	font-weight: 800;
	color: var(--login-text);
	margin: 0 0 10px 0;
	letter-spacing: -0.4px;
}

.login-tagline {
	color: var(--login-muted);
	font-size: 14px;
	line-height: 1.6;
	margin: 0 0 10px;
}

.login-subtitle {
	color: var(--login-muted);
	font-size: 14px;
	margin: 0;
}

.login-body {
	position: relative;
}

.modern-alert {
	background: #f8d7da;
	border: 1px solid #f5c2c7;
	border-radius: 8px;
	color: #842029;
	padding: 12px 16px;
	margin-bottom: 20px;
	font-size: 14px;
}

.modern-alert i {
	margin-right: 8px;
}

.login-form-wrapper {
	position: relative;
}

.modern-login-form {
	display: flex;
	flex-direction: column;
	gap: 18px;
}

.form-field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.field-label {
	color: var(--login-text);
	font-size: 13px;
	font-weight: 600;
	letter-spacing: 0.3px;
}

.field-label i {
	margin-right: 6px;
	color: var(--login-primary);
}

.modern-input {
	background: rgba(255,255,255,0.92);
	border: 1px solid var(--login-border);
	border-radius: 14px;
	padding: 13px 15px;
	color: var(--login-text);
	font-size: 15px;
	transition: all 0.2s ease;
	outline: none;
}

.modern-input::placeholder {
	color: #adb5bd;
}

.modern-input:focus {
	border-color: var(--login-primary);
	box-shadow: 0 0 0 4px rgba(15,118,110,0.12);
}

.password-input-wrapper {
	position: relative;
	display: flex;
	align-items: center;
}

.password-toggle-btn {
	position: absolute;
	right: 12px;
	background: transparent;
	border: none;
	color: var(--login-muted);
	font-size: 16px;
	cursor: pointer;
	padding: 6px;
	transition: color 0.2s;
}

.password-toggle-btn:hover {
	color: var(--login-primary);
}

.form-actions {
	margin-top: 8px;
}

.btn-login-primary {
	width: 100%;
	background: linear-gradient(135deg, var(--login-primary), #14b8a6);
	color: #ffffff;
	border: none;
	border-radius: 14px;
	padding: 15px;
	font-size: 15px;
	font-weight: 700;
	cursor: pointer;
	box-shadow: 0 14px 28px rgba(15,118,110,0.25);
	transition: all 0.2s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
}

.btn-login-primary:hover {
	transform: translateY(-1px);
	box-shadow: 0 18px 32px rgba(15,118,110,0.3);
}

.btn-login-primary:active {
	transform: translateY(0);
}

.login-links {
	display: flex;
	justify-content: space-between;
	margin-top: 20px;
	gap: 16px;
	flex-wrap: wrap;
}

.login-links a {
	color: var(--login-primary);
	text-decoration: none;
	font-size: 13px;
	font-weight: 500;
	transition: all 0.2s;
	display: flex;
	align-items: center;
	gap: 5px;
}

.login-links a:hover {
	color: var(--login-primary-hover);
	text-decoration: underline;
}

.link-register i,
.login-metrics i {
	color: var(--login-accent);
}

/* Responsive */
@media (max-width: 576px) {
	.login-glass-container {
		padding: 24px 20px 26px;
	}
	
	.login-title {
		font-size: 24px;
	}
	
	.login-links {
		flex-direction: column;
		align-items: center;
		gap: 10px;
	}
}
</style>

<?= $this->endSection() ?>
