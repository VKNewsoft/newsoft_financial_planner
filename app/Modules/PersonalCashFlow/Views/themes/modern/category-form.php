<?php helper('html'); ?>
<div class="card">
	<div class="card-header">
		<h5 class="card-title mb-0"><?= esc($title) ?></h5>
	</div>
	<div class="card-body">
		<div class="mb-3 d-flex gap-2 flex-wrap">
			<?= btn_link([
				'attr' => ['class' => 'btn btn-success btn-xs'],
				'url' => base_url('personal-cash-flow/addCategory'),
				'icon' => 'fa fa-plus',
				'label' => 'Tambah Kategori'
			]) ?>
			<?= btn_link([
				'attr' => ['class' => 'btn btn-light btn-xs'],
				'url' => base_url('personal-cash-flow/categories'),
				'icon' => 'fa fa-arrow-circle-left',
				'label' => 'Daftar Kategori'
			]) ?>
		</div>

		<?php if (!empty($message)): ?>
			<div class="mb-3"><?php show_alert($message); ?></div>
		<?php endif; ?>

		<form method="post" action="">
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Jenis Kategori</label>
				<div class="col-sm-6">
					<select name="transaction_type" class="form-select" required>
						<option value="income" <?= ($category['transaction_type'] ?? '') === 'income' ? 'selected' : '' ?>>Pemasukan</option>
						<option value="expense" <?= ($category['transaction_type'] ?? '') === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
					</select>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Nama Kategori</label>
				<div class="col-sm-6">
					<input type="text" name="category_name" class="form-control" maxlength="100" value="<?= esc($category['category_name'] ?? '') ?>" required>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Deskripsi</label>
				<div class="col-sm-6">
					<input type="text" name="description" class="form-control" maxlength="255" value="<?= esc($category['description'] ?? '') ?>">
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Warna</label>
				<div class="col-sm-6">
					<input type="color" name="color" class="form-control form-control-color" value="<?= esc($category['color'] ?? '#6c757d') ?>">
				</div>
			</div>
			<button type="submit" name="submit" value="submit" class="btn btn-primary">Simpan</button>
		</form>
	</div>
</div>
