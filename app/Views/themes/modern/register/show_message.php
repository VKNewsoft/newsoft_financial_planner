<?php
$this->extend('themes/modern/register/layout');
$this->section('content');

$type = $message['status'] == 'error' ? 'danger' : 'success';
$title = $message['status'] == 'error' ? 'Terjadi Kendala' : 'Registrasi Berhasil';
?>
<div class="register-card register-message-card">
	<div class="register-header">
		<div class="logo-wrapper">
			<img src="<?php echo $config->baseURL . 'public/images/' . $setting_aplikasi['logo_login'] ?>?r=<?=time()?>" alt="Logo" class="register-logo">
		</div>
		<h1 class="register-title"><?=$title?></h1>
	</div>

	<div class="alert alert-<?=$type?> modern-message-alert">
		<?=$message['message']?>
	</div>

	<div class="register-links register-links-centered">
		<a href="<?= $config->baseURL ?>login">
			<i class="fa fa-arrow-left"></i> Kembali ke Login
		</a>
	</div>
</div>

<style>
.register-card {
	background: #ffffff;
	border: 1px solid #e3e6f0;
	border-radius: 16px;
	padding: 28px 22px;
	box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
}

.register-header {
	text-align: center;
	margin-bottom: 20px;
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
	color: #212529;
}

.modern-message-alert {
	border-radius: 12px;
	padding: 16px 18px;
	font-size: 0.95rem;
	line-height: 1.65;
	margin-bottom: 18px;
}

.register-links {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10px;
}

.register-links a {
	color: #4e73df;
	text-decoration: none;
	font-size: 0.9rem;
	font-weight: 500;
}

.register-links a:hover {
	text-decoration: underline;
}

@media (min-width: 576px) {
	.register-card {
		padding: 34px 32px;
	}

	.register-links-centered {
		flex-direction: row;
		justify-content: center;
		gap: 22px;
	}
}
</style>
<?= $this->endSection() ?>
