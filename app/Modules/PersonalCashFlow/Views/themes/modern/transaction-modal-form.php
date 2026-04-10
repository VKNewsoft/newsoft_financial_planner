<?php helper('html'); ?>
<form method="post" action="" class="form-horizontal p-2 p-md-3 personal-cash-flow-form" id="transaction-modal-form">
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Jenis Transaksi</label>
		<div class="col-sm-8">
			<select name="transaction_type" id="transaction_type" class="form-select" required>
				<option value="income" <?= ($transaction['transaction_type'] ?? '') === 'income' ? 'selected' : '' ?>>Pemasukan</option>
				<option value="expense" <?= ($transaction['transaction_type'] ?? '') === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
			</select>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Tanggal</label>
		<div class="col-sm-8">
			<input type="date" name="transaction_date" class="form-control" value="<?= esc($transaction['transaction_date'] ?? date('Y-m-d')) ?>" required>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Kategori</label>
		<div class="col-sm-8">
			<select name="id_category" id="id_category" class="form-select" required data-selected="<?= (int) ($transaction['id_category'] ?? 0) ?>"></select>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Nominal</label>
		<div class="col-sm-8">
			<input type="text" inputmode="numeric" name="nominal" class="form-control format-ribuan numeric-only" value="<?= !empty($transaction['nominal']) ? esc(number_format((float) $transaction['nominal'], 0, ',', '.')) : '' ?>" placeholder="Masukkan nominal" required>
			<div class="text-muted small mt-1">Input nominal hanya menerima angka dan otomatis diformat ribuan.</div>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Deskripsi</label>
		<div class="col-sm-8">
			<input type="text" name="description" class="form-control" value="<?= esc($transaction['description'] ?? '') ?>" maxlength="255" required>
		</div>
	</div>
	<div class="row mb-2">
		<label class="col-sm-4 col-form-label">Catatan</label>
		<div class="col-sm-8">
			<textarea name="notes" class="form-control" rows="4" placeholder="Opsional"><?= esc($transaction['notes'] ?? '') ?></textarea>
		</div>
	</div>
	<input type="hidden" name="id" value="<?= (int) ($transaction['id_transaction'] ?? 0) ?>">
</form>

<script>
(function() {
	const categoryOptions = <?= json_encode($categories ?? ['income' => [], 'expense' => []]) ?>;
	const typeSelect = document.getElementById('transaction_type');
	const categorySelect = document.getElementById('id_category');
	const nominalInput = document.querySelector('#transaction-modal-form .numeric-only');

	function renderCategories() {
		const type = typeSelect.value;
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

	if (typeSelect && categorySelect) {
		typeSelect.addEventListener('change', function() {
			categorySelect.dataset.selected = '';
			renderCategories();
		});
		renderCategories();
	}

	if (nominalInput) {
		nominalInput.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '');
			if (typeof jQuery !== 'undefined') {
				jQuery(this).trigger('keyup');
			}
		});
	}
})();
</script>
