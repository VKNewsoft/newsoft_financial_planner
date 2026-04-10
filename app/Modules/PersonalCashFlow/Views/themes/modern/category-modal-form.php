<form method="post" action="" class="form-horizontal p-2 p-md-3" id="category-modal-form">
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Jenis Kategori</label>
		<div class="col-sm-8">
			<select name="transaction_type" class="form-select" required>
				<option value="income" <?= ($category['transaction_type'] ?? '') === 'income' ? 'selected' : '' ?>>Pemasukan</option>
				<option value="expense" <?= ($category['transaction_type'] ?? '') === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
			</select>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Nama Kategori</label>
		<div class="col-sm-8">
			<input type="text" name="category_name" class="form-control" maxlength="100" value="<?= esc($category['category_name'] ?? '') ?>" required>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Deskripsi</label>
		<div class="col-sm-8">
			<input type="text" name="description" class="form-control" maxlength="255" value="<?= esc($category['description'] ?? '') ?>">
		</div>
	</div>
	<div class="row mb-2">
		<label class="col-sm-4 col-form-label">Warna</label>
		<div class="col-sm-8">
			<input type="color" name="color" class="form-control form-control-color" value="<?= esc($category['color'] ?? '#6c757d') ?>">
		</div>
	</div>
	<input type="hidden" name="id" value="<?= (int) ($category['id_category'] ?? 0) ?>">
</form>
