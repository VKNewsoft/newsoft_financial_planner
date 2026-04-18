<?php helper('html'); ?>
<form method="post" action="" class="form-horizontal p-2 p-md-3 personal-cash-flow-form" id="transaction-modal-form">
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Jenis Transaksi</label>
		<div class="col-sm-8">
			<select name="transaction_type" id="transaction_type" class="form-select" required>
				<?php $transactionMode = $transaction['transaction_mode'] ?? ($transaction['transaction_type'] ?? 'expense'); ?>
				<option value="income" <?= $transactionMode === 'income' ? 'selected' : '' ?>>Pemasukan</option>
				<option value="expense" <?= $transactionMode === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
				<option value="transfer" <?= $transactionMode === 'transfer' ? 'selected' : '' ?>>Transfer</option>
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
		<label class="col-sm-4 col-form-label">Wallet</label>
		<div class="col-sm-8">
			<select name="id_wallet" id="id_wallet" class="form-select" required data-selected="<?= (int) ($transaction['id_wallet'] ?? 0) ?>">
				<option value="">Pilih wallet</option>
				<?php foreach (($wallets ?? []) as $wallet): ?>
					<option value="<?= (int) $wallet['id_wallet'] ?>" <?= (int) ($transaction['id_wallet'] ?? 0) === (int) $wallet['id_wallet'] ? 'selected' : '' ?>><?= esc($wallet['wallet_name']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="row mb-3 transfer-only-field d-none">
		<label class="col-sm-4 col-form-label">Wallet Tujuan</label>
		<div class="col-sm-8">
			<select name="id_wallet_transfer_target" id="id_wallet_transfer_target" class="form-select" data-selected="<?= (int) ($transaction['id_wallet_transfer_target'] ?? 0) ?>">
				<option value="">Pilih wallet tujuan</option>
				<?php foreach (($wallets ?? []) as $wallet): ?>
					<option value="<?= (int) $wallet['id_wallet'] ?>" <?= (int) ($transaction['id_wallet_transfer_target'] ?? 0) === (int) $wallet['id_wallet'] ? 'selected' : '' ?>><?= esc($wallet['wallet_name']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label transaction-category-field">Kategori</label>
		<div class="col-sm-8 transaction-category-field">
			<select name="id_category" id="id_category" class="form-select" required data-selected="<?= (int) ($transaction['id_category'] ?? 0) ?>"></select>
		</div>
	</div>
	<div class="row mb-3 transfer-only-field d-none">
		<label class="col-sm-4 col-form-label">Info Transfer</label>
		<div class="col-sm-8">
			<div class="form-control-plaintext text-muted small">Transfer memakai kategori internal khusus dan tidak tampil di pilihan kategori umum.</div>
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
	const targetCategorySelect = document.getElementById('id_category_transfer_target');
	const walletSelect = document.getElementById('id_wallet');
	const targetWalletSelect = document.getElementById('id_wallet_transfer_target');
	const nominalInput = document.querySelector('#transaction-modal-form .numeric-only');
	const transferFields = document.querySelectorAll('#transaction-modal-form .transfer-only-field');
	const categoryFields = document.querySelectorAll('#transaction-modal-form .transaction-category-field');

	function renderCategories(selectElement, type, selectedValue) {
		const categories = categoryOptions[type] || [];
		if (!selectElement) {
			return;
		}

		selectElement.innerHTML = '';
		categories.forEach(function(category) {
			const option = document.createElement('option');
			option.value = category.id_category;
			option.textContent = category.category_name + (parseInt(category.is_default, 10) === 1 ? ' (Default)' : '');
			if (String(category.id_category) === String(selectedValue)) {
				option.selected = true;
			}
			selectElement.appendChild(option);
		});

		if (!selectElement.value && categories.length) {
			selectElement.value = categories[0].id_category;
		}
	}

	function syncMode() {
		const type = typeSelect.value;
		const isTransfer = type === 'transfer';
		const baseType = isTransfer ? 'expense' : type;
		const selectedValue = categorySelect.dataset.selected || '';
		renderCategories(categorySelect, baseType, selectedValue);

		transferFields.forEach(function(field) {
			field.classList.toggle('d-none', !isTransfer);
		});
		categoryFields.forEach(function(field) {
			field.classList.toggle('d-none', isTransfer);
		});

		if (targetWalletSelect) {
			targetWalletSelect.required = isTransfer;
		}

		if (categorySelect) {
			categorySelect.required = !isTransfer;
		}
	}

	if (typeSelect && categorySelect) {
		typeSelect.addEventListener('change', function() {
			categorySelect.dataset.selected = '';
			syncMode();
		});
		syncMode();
	}

	if (nominalInput) {
		nominalInput.addEventListener('input', function() {
			this.value = this.value.replace(/\D/g, '');
			if (typeof jQuery !== 'undefined') {
				jQuery(this).trigger('keyup');
			}
		});
	}

	if (walletSelect && targetWalletSelect) {
		targetWalletSelect.value = targetWalletSelect.dataset.selected || targetWalletSelect.value;
	}
})();
</script>
