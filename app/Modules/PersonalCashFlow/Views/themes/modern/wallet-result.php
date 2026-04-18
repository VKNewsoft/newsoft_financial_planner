<?php
helper('html');
$walletTypeLabels = ['cash' => 'Cash', 'bank' => 'Bank', 'digital' => 'Digital', 'savings' => 'Savings', 'other' => 'Other'];
?>
<style>
.pcf-wallet-shell .pcf-mobile-list {
	display: none;
}
.pcf-wallet-shell .pcf-mobile-item {
	border: 1px solid #edf1ee;
	border-radius: 1rem;
	padding: 1rem;
	background: #fff;
	box-shadow: 0 8px 20px rgba(39, 60, 48, 0.05);
}
.pcf-wallet-shell .pcf-mobile-actions {
	display: flex;
	gap: .5rem;
	flex-wrap: wrap;
}
.pcf-wallet-shell .btn-soft {
	border-radius: .7rem;
	padding: .55rem .95rem;
	font-weight: 600;
}
@media (max-width: 767.98px) {
	.pcf-wallet-shell .pcf-desktop-table {
		display: none;
	}
	.pcf-wallet-shell .pcf-mobile-list {
		display: grid;
		gap: .85rem;
	}
	.pcf-wallet-shell .result-page-actions .btn {
		flex: 1 1 100%;
	}
}
</style>
<div class="result-page-shell pcf-wallet-shell">
	<div class="result-page-hero">
		<div>
			<div class="result-page-kicker">Finance / Wallet</div>
			<h3 class="result-page-heading"><?= esc($title) ?></h3>
			<p class="result-page-copy mb-0">Kelola cash account untuk transaksi personal cash flow dengan pola modal yang sama seperti kategori.</p>
		</div>
		<div class="result-page-actions">
			<button type="button" class="btn btn-success btn-sm btn-soft btn-add-wallet"><i class="fa fa-plus me-1"></i>Tambah Wallet</button>
			<a href="<?= base_url('personal-cash-flow') ?>" class="btn btn-outline-primary btn-sm btn-soft"><i class="fa fa-arrow-left me-1"></i>Kembali</a>
		</div>
	</div>

	<?php if (!empty($msg)): ?>
		<div class="mb-3"><?php show_alert($msg); ?></div>
	<?php endif; ?>

	<div class="card result-list-card">
		<div class="card-body p-0">
			<div class="result-list-toolbar">
				<div>
					<h5 class="mb-1">Daftar Wallet</h5>
					<p class="mb-0 text-muted">Wallet aktif digunakan sebagai cash account untuk setiap transaksi.</p>
				</div>
			</div>
			<div class="result-table-wrap">
				<div class="table-responsive pcf-desktop-table">
					<table class="table table-striped align-middle mb-0">
						<thead>
							<tr>
								<th>Nama Wallet</th>
								<th>Tipe</th>
								<th>Saldo Awal</th>
								<th>Deskripsi</th>
								<th class="text-end">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($wallets as $wallet): ?>
								<tr>
									<td class="fw-semibold"><?= esc($wallet['wallet_name']) ?></td>
									<td><span class="badge text-bg-info"><?= esc($walletTypeLabels[$wallet['wallet_type']] ?? $wallet['wallet_type']) ?></span></td>
									<td>Rp <?= number_format((float) $wallet['initial_balance'], 0, ',', '.') ?></td>
									<td><?= esc($wallet['description'] ?: '-') ?></td>
									<td class="text-end">
										<div class="btn-group btn-group-sm">
											<button type="button" class="btn btn-success btn-edit-wallet" data-id="<?= (int) $wallet['id_wallet'] ?>">Edit</button>
											<form method="post" action="<?= base_url('personal-cash-flow/deleteWallet') ?>" onsubmit="return confirm('Hapus wallet ini?');">
												<input type="hidden" name="id" value="<?= (int) $wallet['id_wallet'] ?>">
												<input type="hidden" name="delete" value="1">
												<button type="submit" class="btn btn-danger">Hapus</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="pcf-mobile-list">
					<?php foreach ($wallets as $wallet): ?>
						<div class="pcf-mobile-item">
							<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
								<div>
									<div class="fw-semibold"><?= esc($wallet['wallet_name']) ?></div>
									<div class="small text-muted mt-1"><?= esc($walletTypeLabels[$wallet['wallet_type']] ?? $wallet['wallet_type']) ?></div>
								</div>
								<div class="fw-semibold">Rp <?= number_format((float) $wallet['initial_balance'], 0, ',', '.') ?></div>
							</div>
							<div class="mb-3"><?= esc($wallet['description'] ?: '-') ?></div>
							<div class="pcf-mobile-actions">
								<button type="button" class="btn btn-success btn-sm btn-soft btn-edit-wallet flex-fill" data-id="<?= (int) $wallet['id_wallet'] ?>">Edit</button>
								<form method="post" action="<?= base_url('personal-cash-flow/deleteWallet') ?>" class="flex-fill" onsubmit="return confirm('Hapus wallet ini?');">
									<input type="hidden" name="id" value="<?= (int) $wallet['id_wallet'] ?>">
									<input type="hidden" name="delete" value="1">
									<button type="submit" class="btn btn-danger btn-sm btn-soft w-100">Hapus</button>
								</form>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
