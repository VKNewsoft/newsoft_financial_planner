<form method="post" action="" class="form-horizontal p-2 p-md-3" id="wallet-modal-form">
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Nama Wallet</label>
		<div class="col-sm-8">
			<input type="text" name="wallet_name" class="form-control" maxlength="100" value="<?= esc($wallet['wallet_name'] ?? '') ?>" required>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Tipe Wallet</label>
		<div class="col-sm-8">
			<select name="wallet_type" class="form-select" required>
				<?php $walletTypes = ['cash' => 'Cash', 'bank' => 'Bank', 'digital' => 'Digital', 'savings' => 'Savings', 'other' => 'Other']; ?>
				<?php foreach ($walletTypes as $value => $label): ?>
					<option value="<?= $value ?>" <?= ($wallet['wallet_type'] ?? 'cash') === $value ? 'selected' : '' ?>><?= $label ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="row mb-3">
		<label class="col-sm-4 col-form-label">Saldo Awal</label>
		<div class="col-sm-8">
			<input type="text" inputmode="numeric" name="initial_balance" class="form-control format-ribuan numeric-only" value="<?= isset($wallet['initial_balance']) && $wallet['initial_balance'] !== '' ? esc(number_format((float) $wallet['initial_balance'], 0, ',', '.')) : '' ?>" placeholder="Masukkan saldo awal" required>
		</div>
	</div>
	<div class="row mb-2">
		<label class="col-sm-4 col-form-label">Deskripsi</label>
		<div class="col-sm-8">
			<input type="text" name="description" class="form-control" maxlength="255" value="<?= esc($wallet['description'] ?? '') ?>">
		</div>
	</div>
	<input type="hidden" name="id" value="<?= (int) ($wallet['id_wallet'] ?? 0) ?>">
</form>
