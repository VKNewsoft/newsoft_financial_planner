<?php
helper('html');

$summary = $summary ?? ['total_income' => 0, 'total_expense' => 0, 'balance' => 0, 'total_transaction' => 0];
$overallSummary = $overallSummary ?? ['total_income' => 0, 'total_expense' => 0, 'balance' => 0, 'total_transaction' => 0];
$expenseCategoryChart = $expenseCategoryChart ?? ['labels' => [], 'totals' => [], 'colors' => []];
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
.pcf-shell .pcf-mobile-list {
	display: none;
}
.pcf-shell .pcf-mobile-item {
	border: 1px solid #edf1ee;
	border-radius: 1rem;
	padding: 1rem;
	background: #fff;
	box-shadow: 0 8px 20px rgba(39, 60, 48, 0.05);
}
.pcf-shell .pcf-mobile-meta {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: .75rem;
}
.pcf-shell .pcf-mobile-meta span {
	display: block;
	font-size: .78rem;
	color: #667085;
	margin-bottom: .15rem;
}
.pcf-shell .pcf-mobile-actions {
	display: flex;
	gap: .5rem;
	flex-wrap: wrap;
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
@media (max-width: 991.98px) {
	.pcf-shell .pcf-hero {
		padding: 1.2rem;
	}
}
@media (max-width: 767.98px) {
	.pcf-shell .pcf-desktop-table {
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
			<a href="<?= base_url('personal-cash-flow/categories') ?>" class="btn btn-outline-primary btn-sm btn-soft"><i class="fa fa-tags me-1"></i>Kelola Kategori</a>
		</div>
	</div>

	<?php if (!empty($msg)): ?>
		<div class="mb-3"><?php show_alert($msg); ?></div>
	<?php endif; ?>

	<div class="card pcf-filter-card mb-4">
		<div class="card-body">
			<form method="get">
				<div class="row g-3 align-items-end">
					<div class="col-md-3">
						<label class="form-label">Periode</label>
						<input type="month" name="period" class="form-control" value="<?= esc($period) ?>">
					</div>
					<div class="col-md-3">
						<label class="form-label">Jenis</label>
						<select name="type" class="form-select">
							<option value="">Semua Jenis</option>
							<option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Pemasukan</option>
							<option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
						</select>
					</div>
					<div class="col-md-4">
						<label class="form-label">Cari</label>
						<input type="text" name="keyword" class="form-control" value="<?= esc($keyword) ?>" placeholder="Kategori, deskripsi, atau catatan">
					</div>
					<div class="col-md-2 d-grid">
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
				<p class="mb-0 text-muted">Desktop memakai tabel, mobile otomatis berubah menjadi kartu yang lebih mudah dipindai.</p>
			</div>
			<div class="text-muted small align-self-md-center"><?= count($transactions) ?> transaksi</div>
		</div>
		<div class="card-body px-4 pb-4">
			<?php if (!$transactions): ?>
				<div class="alert alert-light border mb-0">Belum ada transaksi pada filter yang dipilih.</div>
			<?php else: ?>
				<div class="pcf-bulk-toolbar">
					<div>
						<div class="fw-semibold">Bulk Update Kategori</div>
						<div class="small text-muted">Pilih beberapa transaksi dengan jenis yang sama, lalu ubah kategorinya sekaligus.</div>
					</div>
					<div class="pcf-bulk-actions">
						<select id="bulk-category-select" class="form-select" disabled>
							<option value="">Pilih transaksi dulu</option>
						</select>
						<button type="button" class="btn btn-primary btn-soft btn-bulk-update" disabled>Update Kategori</button>
					</div>
				</div>

				<div class="table-responsive pcf-desktop-table">
					<table class="table table-striped align-middle mb-0">
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
						<tbody>
							<?php foreach ($transactions as $transaction): ?>
								<tr>
									<td class="text-center">
										<input type="checkbox" class="form-check-input bulk-selector transaction-selector" value="<?= (int) $transaction['id_transaction'] ?>" data-type="<?= esc($transaction['transaction_type']) ?>">
									</td>
									<td><?= esc($transaction['transaction_date']) ?></td>
									<td><span class="badge text-bg-<?= $typeBadges[$transaction['transaction_type']] ?? 'secondary' ?>"><?= esc($typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type']) ?></span></td>
									<td>
										<span class="pcf-category-chip">
											<span class="pcf-category-dot" style="background: <?= esc($transaction['color'] ?: '#6c757d') ?>"></span>
											<?= esc($transaction['category_name']) ?>
										</span>
									</td>
									<td>
										<div class="fw-semibold"><?= esc($transaction['description']) ?></div>
										<?php if (!empty($transaction['notes'])): ?>
											<small class="text-muted"><?= esc($transaction['notes']) ?></small>
										<?php endif; ?>
									</td>
									<td class="text-end fw-semibold <?= $transaction['transaction_type'] === 'income' ? 'text-success' : 'text-danger' ?>">
										Rp <?= number_format((float) $transaction['nominal'], 0, ',', '.') ?>
									</td>
									<td class="text-end">
										<div class="btn-group btn-group-sm">
											<button type="button" class="btn btn-success btn-edit-transaction" data-id="<?= (int) $transaction['id_transaction'] ?>">Edit</button>
											<form method="post" action="<?= base_url('personal-cash-flow/delete') ?>" onsubmit="return confirm('Hapus transaksi ini?');">
												<input type="hidden" name="id" value="<?= (int) $transaction['id_transaction'] ?>">
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
					<?php foreach ($transactions as $transaction): ?>
						<div class="pcf-mobile-item">
							<div class="d-flex justify-content-between align-items-start gap-2 mb-3">
								<div>
									<div class="mb-2">
										<input type="checkbox" class="form-check-input bulk-selector transaction-selector" value="<?= (int) $transaction['id_transaction'] ?>" data-type="<?= esc($transaction['transaction_type']) ?>">
									</div>
									<div class="fw-semibold mb-1"><?= esc($transaction['description']) ?></div>
									<div class="small text-muted"><?= esc($transaction['transaction_date']) ?></div>
								</div>
								<div class="text-end">
									<div class="badge text-bg-<?= $typeBadges[$transaction['transaction_type']] ?? 'secondary' ?>"><?= esc($typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type']) ?></div>
									<div class="fw-semibold mt-2 <?= $transaction['transaction_type'] === 'income' ? 'text-success' : 'text-danger' ?>">Rp <?= number_format((float) $transaction['nominal'], 0, ',', '.') ?></div>
								</div>
							</div>
							<div class="pcf-mobile-meta mb-3">
								<div>
									<span>Kategori</span>
									<div class="pcf-category-chip">
										<span class="pcf-category-dot" style="background: <?= esc($transaction['color'] ?: '#6c757d') ?>"></span>
										<?= esc($transaction['category_name']) ?>
									</div>
								</div>
								<div>
									<span>Catatan</span>
									<div><?= esc($transaction['notes'] ?: '-') ?></div>
								</div>
							</div>
							<div class="pcf-mobile-actions">
								<button type="button" class="btn btn-success btn-sm btn-soft btn-edit-transaction flex-fill" data-id="<?= (int) $transaction['id_transaction'] ?>">Edit</button>
								<form method="post" action="<?= base_url('personal-cash-flow/delete') ?>" class="flex-fill" onsubmit="return confirm('Hapus transaksi ini?');">
									<input type="hidden" name="id" value="<?= (int) $transaction['id_transaction'] ?>">
									<input type="hidden" name="delete" value="1">
									<button type="submit" class="btn btn-danger btn-sm btn-soft w-100">Hapus</button>
								</form>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

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
