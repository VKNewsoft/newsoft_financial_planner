<?php
helper('html');
$typeLabels = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
?>
<style>
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
@media (max-width: 767.98px) {
	.pcf-shell .pcf-desktop-table {
		display: none;
	}
	.pcf-shell .pcf-mobile-list {
		display: grid;
		gap: .85rem;
	}
	.pcf-shell .result-page-actions .btn {
		flex: 1 1 100%;
	}
}
</style>
<div class="result-page-shell pcf-shell">
	<div class="result-page-hero">
		<div>
			<div class="result-page-kicker">Finance / Category</div>
			<h3 class="result-page-heading"><?= esc($title) ?></h3>
			<p class="result-page-copy mb-0">Kategori default tetap readonly, sementara kategori custom bisa Anda tambah dan edit lewat modal yang sama.</p>
		</div>
		<div class="result-page-actions">
			<button type="button" class="btn btn-success btn-sm btn-soft btn-add-category"><i class="fa fa-plus me-1"></i>Tambah Kategori</button>
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
					<h5 class="mb-1">Daftar Kategori</h5>
					<p class="mb-0 text-muted">Tampilan tabel tetap dipertahankan di desktop, sedangkan mobile memakai stack card untuk menjaga readability.</p>
				</div>
			</div>
			<div class="result-table-wrap">
				<div class="table-responsive pcf-desktop-table">
					<table class="table table-striped align-middle mb-0">
						<thead>
							<tr>
								<th>Jenis</th>
								<th>Nama Kategori</th>
								<th>Deskripsi</th>
								<th>Status</th>
								<th class="text-end">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($categories as $category): ?>
								<tr>
									<td>
										<span class="badge text-bg-<?= $category['transaction_type'] === 'income' ? 'success' : 'danger' ?>">
											<?= esc($typeLabels[$category['transaction_type']] ?? $category['transaction_type']) ?>
										</span>
									</td>
									<td>
										<span class="pcf-category-chip">
											<span class="pcf-category-dot" style="background: <?= esc($category['color'] ?: '#6c757d') ?>"></span>
											<?= esc($category['category_name']) ?>
										</span>
									</td>
									<td><?= esc($category['description'] ?: '-') ?></td>
									<td><span class="badge text-bg-<?= (int) $category['is_default'] === 1 ? 'secondary' : 'info' ?>"><?= (int) $category['is_default'] === 1 ? 'Default' : 'Custom' ?></span></td>
									<td class="text-end">
										<?php if ((int) $category['is_default'] === 1): ?>
											<span class="text-muted small">Readonly</span>
										<?php else: ?>
											<div class="btn-group btn-group-sm">
												<button type="button" class="btn btn-success btn-edit-category" data-id="<?= (int) $category['id_category'] ?>">Edit</button>
												<form method="post" action="<?= base_url('personal-cash-flow/deleteCategory') ?>" onsubmit="return confirm('Hapus kategori ini?');">
													<input type="hidden" name="id" value="<?= (int) $category['id_category'] ?>">
													<input type="hidden" name="delete" value="1">
													<button type="submit" class="btn btn-danger">Hapus</button>
												</form>
											</div>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="pcf-mobile-list">
					<?php foreach ($categories as $category): ?>
						<div class="pcf-mobile-item">
							<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
								<div>
									<div class="pcf-category-chip">
										<span class="pcf-category-dot" style="background: <?= esc($category['color'] ?: '#6c757d') ?>"></span>
										<?= esc($category['category_name']) ?>
									</div>
									<div class="small text-muted mt-2"><?= esc($typeLabels[$category['transaction_type']] ?? $category['transaction_type']) ?></div>
								</div>
								<span class="badge text-bg-<?= (int) $category['is_default'] === 1 ? 'secondary' : 'info' ?>"><?= (int) $category['is_default'] === 1 ? 'Default' : 'Custom' ?></span>
							</div>
							<div class="mb-3"><?= esc($category['description'] ?: '-') ?></div>
							<?php if ((int) $category['is_default'] === 1): ?>
								<div class="text-muted small">Kategori bawaan sistem tidak dapat diubah.</div>
							<?php else: ?>
								<div class="pcf-mobile-actions">
									<button type="button" class="btn btn-success btn-sm btn-soft btn-edit-category flex-fill" data-id="<?= (int) $category['id_category'] ?>">Edit</button>
									<form method="post" action="<?= base_url('personal-cash-flow/deleteCategory') ?>" class="flex-fill" onsubmit="return confirm('Hapus kategori ini?');">
										<input type="hidden" name="id" value="<?= (int) $category['id_category'] ?>">
										<input type="hidden" name="delete" value="1">
										<button type="submit" class="btn btn-danger btn-sm btn-soft w-100">Hapus</button>
									</form>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
