<?php
helper('html');

$summary = $summary ?? ['total_income' => 0, 'total_expense' => 0, 'balance' => 0, 'total_transaction' => 0];
$overallSummary = $overallSummary ?? ['total_income' => 0, 'total_expense' => 0, 'balance' => 0, 'total_transaction' => 0];
$expenseCategoryChart = $expenseCategoryChart ?? ['labels' => [], 'totals' => [], 'colors' => []];
$transactionCategories = $transactionCategories ?? [];
$walletSummary = $walletSummary ?? [];
$transferSummary = $transferSummary ?? ['total_transfer' => 0, 'total_nominal' => 0];
$transferReport = $transferReport ?? [];
$typeLabels = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
$typeBadges = ['income' => 'success', 'expense' => 'danger'];
?>
<style>
.pcf-shell .pcf-hero {
	padding: 1.5rem 1.75rem;
	border-radius: 1rem;
	background: linear-gradient(135deg, #f7f2e8 0%, #ffffff 50%, #eef6f1 100%);
	border: 1px solid #e8dfcf;
	box-shadow: 0 16px 36px rgba(95, 78, 52, 0.08);
}
.pcf-shell .pcf-hero-actions {
	display: flex;
	gap: .75rem;
	flex-wrap: wrap;
}
.pcf-shell .pcf-hero-actions .btn {
	display: flex;
	justify-content: center;
	align-items: center;
}
.pcf-shell .pcf-filter-card,
.pcf-shell .pcf-chart-card,
.pcf-shell .pcf-table-card,
.pcf-shell .pcf-summary-card {
	border: 1px solid #e8ece8;
	border-radius: 1rem;
	box-shadow: 0 12px 32px rgba(39, 60, 48, 0.08);
}
.pcf-shell .pcf-summary-card .card-body {
	padding: 1.1rem 1.15rem;
}
.pcf-shell .pcf-summary-label {
	color: #667085;
	font-size: .86rem;
	margin-bottom: .5rem;
}
.pcf-shell .pcf-summary-value {
	font-size: 1.6rem;
	font-weight: 700;
	line-height: 1.1;
}
.pcf-shell .pcf-chart-wrap {
	position: relative;
	min-height: 300px;
}
.pcf-shell .pcf-chart-wrap.is-compact {
	min-height: 260px;
}
.pcf-shell .pcf-category-chip {
	display: inline-flex;
	align-items: center;
	gap: .45rem;
	padding: .3rem .65rem;
	border-radius: 999px;
	border: 1px solid #e2e8f0;
	background: #f8fafc;
}
.pcf-shell .pcf-category-dot {
	width: .7rem;
	height: .7rem;
	border-radius: 50%;
	display: inline-block;
}
.pcf-shell .btn-soft {
	border-radius: .7rem;
	padding: .55rem .95rem;
	font-weight: 600;
}
.pcf-shell .table > :not(caption) > * > * {
	padding-top: .9rem;
	padding-bottom: .9rem;
}
.pcf-shell .pcf-bulk-toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 1rem;
	padding: 1rem 1.25rem;
	margin-bottom: 1rem;
	border: 1px solid #e8ece8;
	border-radius: 1rem;
	background: linear-gradient(180deg, rgba(247, 250, 248, .98) 0%, rgba(255, 255, 255, .98) 100%);
}
.pcf-shell .pcf-bulk-actions {
	display: flex;
	gap: .75rem;
	flex-wrap: wrap;
	align-items: center;
}
.pcf-shell .pcf-list-filter-grid {
	display: grid;
	grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) minmax(0, 1fr);
	gap: .9rem;
}
.pcf-shell .pcf-table-tools {
	display: flex;
	flex-direction: column;
	gap: 1rem;
	margin-bottom: 1rem;
}
.pcf-shell .pcf-check-col {
	width: 42px;
}
.pcf-shell .pcf-section-note {
	font-size: .86rem;
	color: #667085;
}
.pcf-shell .form-check-input.bulk-selector {
	width: 1.1rem;
	height: 1.1rem;
	margin-top: 0;
}
.pcf-shell .pcf-table-card .dataTables_wrapper .dataTables_filter {
	display: none;
}
.pcf-shell .pcf-table-card .dataTables_wrapper,
.pcf-shell .pcf-table-card .dataTables_scroll,
.pcf-shell .pcf-table-card .dataTables_scrollHead,
.pcf-shell .pcf-table-card .dataTables_scrollBody,
.pcf-shell .pcf-table-card .dataTables_scrollHeadInner,
.pcf-shell .pcf-table-card .dataTables_scrollHeadInner table,
.pcf-shell .pcf-table-card .dataTables_scrollBody table {
	width: 100% !important;
}
.pcf-shell .pcf-table-card .dataTables_wrapper .dataTables_length {
	margin-bottom: .75rem;
}
.pcf-shell .pcf-table-card .dataTables_wrapper .dataTables_info,
.pcf-shell .pcf-table-card .dataTables_wrapper .dataTables_paginate {
	margin-top: .9rem;
}
.pcf-shell #table-data {
	width: 100% !important;
}
.pcf-shell #table-data thead th,
.pcf-shell #table-data tbody td {
	vertical-align: middle;
	white-space: normal;
}
.pcf-shell #table-data td.text-end,
.pcf-shell #table-data th.text-end {
	white-space: nowrap;
}
.pcf-shell .pcf-loading-note {
	font-size: .82rem;
	color: #667085;
}
.pcf-shell .pcf-transaction-desc small {
	display: block;
	margin-top: .2rem;
	line-height: 1.5;
}
.pcf-shell .btn-group.btn-group-sm > form {
	margin: 0;
}
.pcf-shell .pcf-mobile-list {
	display: none;
}
.pcf-shell .pcf-mobile-item {
	border: 1px solid #e8ece8;
	border-radius: 1rem;
	padding: 1rem;
	background: #fff;
	box-shadow: 0 10px 22px rgba(39, 60, 48, 0.05);
}
.pcf-shell .pcf-mobile-item-head {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: .75rem;
	margin-bottom: .8rem;
}
.pcf-shell .pcf-mobile-item-left {
	display: flex;
	gap: .75rem;
	min-width: 0;
}
.pcf-shell .pcf-mobile-checkbox {
	padding-top: .1rem;
}
.pcf-shell .pcf-mobile-title {
	font-weight: 600;
	line-height: 1.4;
	margin-bottom: .25rem;
}
.pcf-shell .pcf-mobile-date {
	font-size: .82rem;
	color: #667085;
}
.pcf-shell .pcf-mobile-amount {
	font-weight: 700;
	text-align: right;
	white-space: nowrap;
}
.pcf-shell .pcf-mobile-meta {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: .75rem;
	margin-bottom: .85rem;
}
.pcf-shell .pcf-mobile-meta-label {
	display: block;
	font-size: .75rem;
	color: #667085;
	margin-bottom: .2rem;
}
.pcf-shell .pcf-mobile-actions {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: .6rem;
}
.pcf-shell .pcf-mobile-empty {
	display: none;
}
.pcf-shell .pcf-mobile-loadmore {
	display: none;
}
.pcf-shell .pcf-wallet-summary-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	gap: 1rem;
}
.pcf-shell .pcf-wallet-card {
	border: 1px solid #e8ece8;
	border-radius: 1rem;
	background: #fff;
	box-shadow: 0 12px 28px rgba(39, 60, 48, 0.06);
	padding: 1rem 1.1rem;
}
.pcf-shell .pcf-wallet-card-title {
	font-weight: 700;
	margin-bottom: .35rem;
}
.pcf-shell .pcf-wallet-card-type {
	font-size: .8rem;
	color: #667085;
	margin-bottom: .7rem;
}
.pcf-shell .pcf-wallet-card-balance {
	font-size: 1.35rem;
	font-weight: 700;
	margin-bottom: .8rem;
}
.pcf-shell .pcf-wallet-card-meta {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: .65rem;
	font-size: .83rem;
}
.pcf-shell .pcf-wallet-card-meta span {
	display: block;
	color: #667085;
	margin-bottom: .15rem;
}
@media (max-width: 991.98px) {
	.pcf-shell .pcf-hero {
		padding: 1.2rem;
	}
}
@media (max-width: 767.98px) {
	.pcf-shell .pcf-table-desktop {
		display: none;
	}
	.pcf-shell .pcf-mobile-list {
		display: grid;
		gap: .85rem;
	}
	.pcf-shell .pcf-chart-wrap {
		min-height: 240px;
	}
	.pcf-shell .pcf-summary-value {
		font-size: 1.35rem;
	}
	.pcf-shell .pcf-hero-actions {
		width: 100%;
	}
	.pcf-shell .pcf-hero-actions .btn {
		flex: 1 1 100%;
	}
	.pcf-shell .pcf-bulk-toolbar {
		flex-direction: column;
		align-items: stretch;
	}
	.pcf-shell .pcf-bulk-actions > * {
		width: 100%;
	}
	.pcf-shell .pcf-list-filter-grid {
		grid-template-columns: 1fr;
	}
	.pcf-shell .btn-group.btn-group-sm {
		display: flex;
		flex-direction: column;
		gap: .35rem;
	}
	.pcf-shell .btn-group.btn-group-sm > .btn,
	.pcf-shell .btn-group.btn-group-sm > form .btn {
		width: 100%;
		border-radius: .55rem !important;
	}
	.pcf-shell .pcf-mobile-loadmore {
		display: inline-flex;
	}
	.pcf-shell .pcf-mobile-empty.is-visible {
		display: block;
	}
}
</style>

<div class="result-page-shell pcf-shell">
	<div class="pcf-hero mb-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
		<div>
			<div class="result-page-kicker">Finance / Personal</div>
			<h3 class="result-page-heading mb-2"><?= esc($current_module['judul_module']) ?></h3>
			<p class="result-page-copy mb-0">Pencatatan cash flow pribadi yang ringkas, responsif, dan tetap nyaman dipakai dari desktop maupun mobile.</p>
		</div>
		<div class="pcf-hero-actions">
			<button type="button" class="btn btn-success btn-sm btn-soft btn-add-transaction"><i class="fa fa-plus me-1"></i>Tambah Transaksi</button>
			<button type="button" class="btn btn-outline-success btn-sm btn-soft btn-add-wallet"><i class="fa fa-wallet me-1"></i>Tambah Wallet</button>
			<a href="<?= base_url('personal-cash-flow/categories') ?>" class="btn btn-outline-primary btn-sm btn-soft"><i class="fa fa-tags me-1"></i>Kelola Kategori</a>
			<a href="<?= base_url('personal-cash-flow/wallets') ?>" class="btn btn-outline-primary btn-sm btn-soft"><i class="fa fa-university me-1"></i>Kelola Wallet</a>
		</div>
	</div>

	<?php if (!empty($msg)): ?>
		<div class="mb-3"><?php show_alert($msg); ?></div>
	<?php endif; ?>

	<div class="card pcf-filter-card mb-4">
		<div class="card-body">
			<form method="get">
				<div class="row g-3 align-items-end">
					<div class="col-md-4">
						<label class="form-label">Periode</label>
						<input type="month" name="period" class="form-control" value="<?= esc($period) ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label">Jenis</label>
						<select name="type" class="form-select">
							<option value="">Semua Jenis</option>
							<option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Pemasukan</option>
							<option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
						</select>
					</div>
					<div class="col-md-4 d-grid">
						<button type="submit" class="btn btn-primary btn-soft">Terapkan</button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-lg-2 col-sm-6">
			<div class="card pcf-summary-card h-100">
				<div class="card-body">
					<div class="pcf-summary-label">Sisa Kas Overall</div>
					<div class="pcf-summary-value <?= $overallSummary['balance'] >= 0 ? 'text-primary' : 'text-warning' ?>">Rp <?= number_format((float) $overallSummary['balance'], 0, ',', '.') ?></div>
				</div>
			</div>
		</div>
		<div class="col-lg-2 col-sm-6">
			<div class="card pcf-summary-card h-100">
				<div class="card-body">
					<div class="pcf-summary-label">Sisa Kas Periode</div>
					<div class="pcf-summary-value <?= $summary['balance'] >= 0 ? 'text-primary' : 'text-warning' ?>">Rp <?= number_format((float) $summary['balance'], 0, ',', '.') ?></div>
				</div>
			</div>
		</div>
		<div class="col-lg-2 col-sm-6">
			<div class="card pcf-summary-card h-100">
				<div class="card-body">
					<div class="pcf-summary-label">Total Pemasukan</div>
					<div class="pcf-summary-value text-success">Rp <?= number_format((float) $summary['total_income'], 0, ',', '.') ?></div>
				</div>
			</div>
		</div>
		<div class="col-lg-2 col-sm-6">
			<div class="card pcf-summary-card h-100">
				<div class="card-body">
					<div class="pcf-summary-label">Total Pengeluaran</div>
					<div class="pcf-summary-value text-danger">Rp <?= number_format((float) $summary['total_expense'], 0, ',', '.') ?></div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6">
			<div class="card pcf-summary-card h-100">
				<div class="card-body">
					<div class="pcf-summary-label">Total Pemasukan Overall</div>
					<div class="pcf-summary-value text-success">Rp <?= number_format((float) $overallSummary['total_income'], 0, ',', '.') ?></div>
				</div>
			</div>
		</div>
		<div class="col-lg-1 col-sm-6">
			<div class="card pcf-summary-card h-100">
				<div class="card-body">
					<div class="pcf-summary-label">Jumlah Transaksi</div>
					<div class="pcf-summary-value text-dark"><?= number_format((int) $summary['total_transaction'], 0, ',', '.') ?></div>
				</div>
			</div>
		</div>
	</div>

	<div class="card pcf-chart-card mb-4">
		<div class="card-header bg-white border-0 pt-4 px-4">
			<h5 class="mb-1">Summary per Wallet</h5>
			<p class="mb-0 pcf-section-note">Saldo wallet dihitung dari saldo awal ditambah pemasukan lalu dikurangi pengeluaran, sehingga transfer antar wallet tidak menambah total saldo secara ganda.</p>
		</div>
		<div class="card-body px-4 pb-4">
			<?php if (!$walletSummary): ?>
				<div class="alert alert-light border mb-0">Belum ada wallet aktif. Tambahkan wallet terlebih dahulu sebelum mencatat transaksi.</div>
			<?php else: ?>
				<div class="pcf-wallet-summary-grid">
					<?php foreach ($walletSummary as $wallet): ?>
						<div class="pcf-wallet-card">
							<div class="pcf-wallet-card-title"><?= esc($wallet['wallet_name']) ?></div>
							<div class="pcf-wallet-card-type"><?= esc(ucfirst($wallet['wallet_type'])) ?></div>
							<div class="pcf-wallet-card-balance <?= $wallet['balance'] >= 0 ? 'text-primary' : 'text-warning' ?>">Rp <?= number_format((float) $wallet['balance'], 0, ',', '.') ?></div>
							<div class="pcf-wallet-card-meta">
								<div>
									<span>Saldo Awal</span>
									<div>Rp <?= number_format((float) $wallet['initial_balance'], 0, ',', '.') ?></div>
								</div>
								<div>
									<span>Pemasukan</span>
									<div class="text-success">Rp <?= number_format((float) $wallet['total_income'], 0, ',', '.') ?></div>
								</div>
								<div>
									<span>Pengeluaran</span>
									<div class="text-danger">Rp <?= number_format((float) $wallet['total_expense'], 0, ',', '.') ?></div>
								</div>
								<div>
									<span>Deskripsi</span>
									<div><?= esc($wallet['description'] ?: '-') ?></div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="card pcf-chart-card mb-4">
		<div class="card-header bg-white border-0 pt-4 px-4">
			<h5 class="mb-1">Report Transfer</h5>
			<p class="mb-0 pcf-section-note">Transfer dipisahkan dari income dan expense utama, tetapi tetap tercermin pada saldo wallet asal dan tujuan.</p>
		</div>
		<div class="card-body px-4 pb-4">
			<div class="row g-3 mb-3">
				<div class="col-md-4">
					<div class="pcf-wallet-card">
						<div class="pcf-wallet-card-title">Total Transfer Periode</div>
						<div class="pcf-wallet-card-balance text-primary"><?= number_format((int) $transferSummary['total_transfer'], 0, ',', '.') ?></div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="pcf-wallet-card">
						<div class="pcf-wallet-card-title">Nilai Transfer Periode</div>
						<div class="pcf-wallet-card-balance text-primary">Rp <?= number_format((float) $transferSummary['total_nominal'], 0, ',', '.') ?></div>
					</div>
				</div>
			</div>
			<?php if (!$transferReport): ?>
				<div class="alert alert-light border mb-0">Belum ada transfer pada periode ini.</div>
			<?php else: ?>
				<div class="table-responsive">
					<table class="table table-striped align-middle mb-0">
						<thead>
							<tr>
								<th>Tanggal</th>
								<th>Transfer</th>
								<th>Deskripsi</th>
								<th class="text-end">Nominal</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($transferReport as $transfer): ?>
								<tr>
									<td><?= esc($transfer['transaction_date']) ?></td>
									<td><span class="pcf-category-chip"><?= esc($transfer['source_wallet_name']) ?> -> <?= esc($transfer['target_wallet_name']) ?></span></td>
									<td>
										<div class="fw-semibold"><?= esc($transfer['description']) ?></div>
										<?php if (!empty($transfer['notes'])): ?><small class="text-muted"><?= esc($transfer['notes']) ?></small><?php endif; ?>
									</td>
									<td class="text-end fw-semibold text-primary">Rp <?= number_format((float) $transfer['nominal'], 0, ',', '.') ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="row g-3 mb-4">
		<div class="col-lg-7">
			<div class="card pcf-chart-card h-100">
				<div class="card-header bg-white border-0 pt-4 px-4">
					<h5 class="mb-1">Arus Kas 6 Bulan Terakhir</h5>
					<p class="mb-0 pcf-section-note">Desktop memakai chart perbandingan bulanan, sedangkan mobile otomatis berganti ke donut agar lebih mudah dibaca.</p>
				</div>
				<div class="card-body px-4 pb-4">
					<div class="pcf-chart-wrap">
						<canvas id="pcfChart"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-5">
			<div class="card pcf-chart-card h-100">
				<div class="card-header bg-white border-0 pt-4 px-4">
					<h5 class="mb-1">Pengeluaran per Kategori</h5>
					<p class="mb-0 pcf-section-note">Breakdown pengeluaran untuk periode filter aktif, diprioritaskan dalam format donut supaya tetap jelas di mobile.</p>
				</div>
				<div class="card-body px-4 pb-4">
					<?php if (!empty($expenseCategoryChart['labels'])): ?>
						<div class="pcf-chart-wrap is-compact">
							<canvas id="expenseCategoryChart"></canvas>
						</div>
					<?php else: ?>
						<div class="alert alert-light border mb-0">Belum ada pengeluaran pada periode ini.</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="card pcf-table-card">
		<div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-md-row justify-content-between gap-2">
			<div>
				<h5 class="mb-1">Daftar Transaksi</h5>
				<p class="mb-0 text-muted">Daftar transaksi dimuat per halaman dengan server-side DataTable supaya tetap ringan saat data bertambah besar.</p>
			</div>
			<div class="text-muted small align-self-md-center"><?= number_format((int) $transactionCount, 0, ',', '.') ?> transaksi pada periode ini</div>
		</div>
		<div class="card-body px-4 pb-4">
			<div class="pcf-table-tools">
				<div class="pcf-bulk-toolbar">
					<div>
						<div class="fw-semibold">Bulk Update Kategori</div>
						<div class="small text-muted">Bekerja untuk baris yang dipilih di halaman aktif DataTable.</div>
					</div>
					<div class="pcf-bulk-actions">
						<select id="bulk-category-select" class="form-select" disabled>
							<option value="">Pilih transaksi dulu</option>
						</select>
						<button type="button" class="btn btn-primary btn-soft btn-bulk-update" disabled>Update Kategori</button>
					</div>
				</div>

				<div class="pcf-filter-card border-0 shadow-none mb-0">
					<div class="card-body p-0">
						<div class="pcf-list-filter-grid">
							<div>
								<label class="form-label">Search realtime</label>
								<input type="text" id="transaction-search" class="form-control" placeholder="Cari kategori, deskripsi, atau catatan">
							</div>
							<div>
								<label class="form-label">Filter kategori</label>
								<select id="transaction-category-filter" class="form-select">
									<option value="">Semua kategori</option>
									<?php foreach ($transactionCategories as $category): ?>
										<option value="<?= (int) $category['id_category'] ?>"><?= esc($category['category_name']) ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div>
								<label class="form-label">Filter deskripsi</label>
								<input type="text" id="transaction-description-filter" class="form-control" placeholder="Contoh: makan, listrik, transfer">
							</div>
						</div>
						<div class="pcf-loading-note mt-2">Filter daftar transaksi ini independen dan tidak mengubah summary atau chart dashboard.</div>
					</div>
				</div>
			</div>

			<div class="table-responsive pcf-table-desktop">
				<table class="table table-striped align-middle mb-0" id="table-data">
					<thead>
						<tr>
							<th class="pcf-check-col text-center"><input type="checkbox" class="form-check-input bulk-selector" id="select-all-transactions"></th>
							<th>Tanggal</th>
							<th>Jenis</th>
							<th>Kategori</th>
							<th>Deskripsi</th>
							<th class="text-end">Nominal</th>
							<th class="text-end">Aksi</th>
						</tr>
					</thead>
				</table>
			</div>

			<div class="pcf-mobile-list-wrapper">
				<div id="pcf-mobile-list" class="pcf-mobile-list"></div>
				<div id="pcf-mobile-empty" class="alert alert-light border mb-0 pcf-mobile-empty">Belum ada transaksi pada filter yang dipilih.</div>
				<div class="text-center mt-3">
					<button type="button" id="pcf-mobile-loadmore" class="btn btn-outline-primary btn-soft pcf-mobile-loadmore">Load More</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="dataTables-url" class="d-none"><?= base_url('personal-cash-flow/getDataDT?period=' . urlencode($period) . '&type=' . urlencode($type)) ?></div>
<div id="pcf-mobile-data-url" class="d-none"><?= base_url('personal-cash-flow/ajaxGetTransactionMobileList?period=' . urlencode($period) . '&type=' . urlencode($type)) ?></div>
<script type="application/json" id="dataTables-column"><?= json_encode([
	['data' => 'ignore_select', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
	['data' => 'transaction_date'],
	['data' => 'transaction_type'],
	['data' => 'category_name'],
	['data' => 'description'],
	['data' => 'nominal', 'className' => 'text-end'],
	['data' => 'ignore_search_action', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
], JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/json" id="dataTables-setting"><?= json_encode([
	'scrollX' => true,
	'pageLength' => 10,
	'lengthMenu' => [[10, 25, 50], [10, 25, 50]],
	'order' => [[1, 'desc']],
], JSON_UNESCAPED_SLASHES) ?></script>
<div id="dataTables-scrolls" class="d-none">420</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.personalCashFlowCategories = <?= json_encode($categoriesGrouped ?? ['income' => [], 'expense' => []]) ?>;
const isMobileCashFlow = window.matchMedia('(max-width: 767.98px)').matches;
const periodSummary = <?= json_encode([
	'income' => (float) ($summary['total_income'] ?? 0),
	'expense' => (float) ($summary['total_expense'] ?? 0),
	'balance' => max(0, (float) ($summary['balance'] ?? 0)),
]) ?>;

new Chart(document.getElementById('pcfChart'), isMobileCashFlow ? {
	type: 'doughnut',
	data: {
		labels: ['Pemasukan Periode', 'Pengeluaran Periode', 'Sisa Kas Periode'],
		datasets: [{
			data: [periodSummary.income, periodSummary.expense, periodSummary.balance],
			backgroundColor: ['rgba(34, 197, 94, 0.72)', 'rgba(239, 68, 68, 0.72)', 'rgba(37, 99, 235, 0.72)'],
			borderColor: ['rgb(34, 197, 94)', 'rgb(239, 68, 68)', 'rgb(37, 99, 235)'],
			borderWidth: 1
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				position: 'bottom'
			}
		}
	}
} : {
	type: 'bar',
	data: {
		labels: <?= json_encode($chart['labels'] ?? []) ?>,
		datasets: [
			{
				label: 'Pemasukan',
				data: <?= json_encode($chart['income'] ?? []) ?>,
				backgroundColor: 'rgba(34, 197, 94, 0.65)',
				borderColor: 'rgb(34, 197, 94)',
				borderWidth: 1,
				borderRadius: 8
			},
			{
				label: 'Pengeluaran',
				data: <?= json_encode($chart['expense'] ?? []) ?>,
				backgroundColor: 'rgba(239, 68, 68, 0.65)',
				borderColor: 'rgb(239, 68, 68)',
				borderWidth: 1,
				borderRadius: 8
			}
		]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				position: 'top'
			}
		},
		scales: {
			y: {
				beginAtZero: true
			}
		}
	}
});

<?php if (!empty($expenseCategoryChart['labels'])): ?>
new Chart(document.getElementById('expenseCategoryChart'), {
	type: 'doughnut',
	data: {
		labels: <?= json_encode($expenseCategoryChart['labels'] ?? []) ?>,
		datasets: [{
			data: <?= json_encode($expenseCategoryChart['totals'] ?? []) ?>,
			backgroundColor: <?= json_encode($expenseCategoryChart['colors'] ?? []) ?>,
			borderWidth: 1
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		plugins: {
			legend: {
				position: isMobileCashFlow ? 'bottom' : 'right'
			}
		}
	}
});
<?php endif; ?>
</script>
