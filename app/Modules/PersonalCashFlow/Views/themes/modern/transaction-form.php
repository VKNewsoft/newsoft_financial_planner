<?php helper('html'); ?>
<div class="card">
	<div class="card-header">
		<h5 class="card-title mb-0"><?= esc($title) ?></h5>
	</div>
	<div class="card-body">
		<div class="mb-3 d-flex gap-2 flex-wrap">
			<?= btn_link([
				'attr' => ['class' => 'btn btn-success btn-xs'],
				'url' => base_url('personal-cash-flow/add'),
				'icon' => 'fa fa-plus',
				'label' => 'Tambah Transaksi'
			]) ?>
			<?= btn_link([
				'attr' => ['class' => 'btn btn-light btn-xs'],
				'url' => base_url('personal-cash-flow'),
				'icon' => 'fa fa-arrow-circle-left',
				'label' => 'Kembali ke Daftar'
			]) ?>
		</div>

		<?php if (!empty($message)): ?>
			<div class="mb-3"><?php show_alert($message); ?></div>
		<?php endif; ?>

		<form method="post" action="">
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Jenis Transaksi</label>
				<div class="col-sm-6">
					<select name="transaction_type" id="transaction_type" class="form-select" required>
						<option value="income" <?= ($transaction['transaction_type'] ?? '') === 'income' ? 'selected' : '' ?>>Pemasukan</option>
						<option value="expense" <?= ($transaction['transaction_type'] ?? '') === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
					</select>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Tanggal</label>
				<div class="col-sm-6">
					<input type="date" name="transaction_date" class="form-control" value="<?= esc($transaction['transaction_date'] ?? date('Y-m-d')) ?>" required>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Kategori</label>
				<div class="col-sm-6">
					<select name="id_category" id="id_category" class="form-select" required data-selected="<?= (int) ($transaction['id_category'] ?? 0) ?>"></select>
					<small class="text-muted">Kategori default dan kategori buatan Anda akan muncul sesuai jenis transaksi.</small>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Nominal</label>
				<div class="col-sm-6">
					<input type="number" min="0" step="0.01" name="nominal" class="form-control" value="<?= esc((string) ($transaction['nominal'] ?? '')) ?>" placeholder="0" required>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Deskripsi</label>
				<div class="col-sm-6">
					<input type="text" name="description" class="form-control" value="<?= esc($transaction['description'] ?? '') ?>" maxlength="255" placeholder="Contoh: Gaji bulanan / Belanja dapur" required>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-lg-2 col-form-label">Catatan</label>
				<div class="col-sm-6">
					<textarea name="notes" class="form-control" rows="4" placeholder="Opsional"><?= esc($transaction['notes'] ?? '') ?></textarea>
				</div>
			</div>
			<button type="submit" name="submit" value="submit" class="btn btn-primary">Simpan</button>
		</form>
	</div>
</div>

<script>
const categoryOptions = <?= json_encode($categories ?? ['income' => [], 'expense' => []]) ?>;
const transactionTypeSelect = document.getElementById('transaction_type');
const categorySelect = document.getElementById('id_category');

function renderCategories() {
	const type = transactionTypeSelect.value;
	const selectedValue = categorySelect.dataset.selected || '';
	const categories = categoryOptions[type] || [];
	categorySelect.innerHTML = '';

	categories.forEach(function(category) {
		const option = document.createElement('option');
		option.value = category.id_category;
		option.textContent = category.category_name + (parseInt(category.is_default, 10) === 1 ? ' (Default)' : '');
		if (String(category.id_category) === String(selectedValue)) {
			option.selected = true;
		}
		categorySelect.appendChild(option);
	});

	if (!categorySelect.value && categories.length) {
		categorySelect.value = categories[0].id_category;
	}
}

transactionTypeSelect.addEventListener('change', function() {
	categorySelect.dataset.selected = '';
	renderCategories();
});

renderCategories();
</script>
