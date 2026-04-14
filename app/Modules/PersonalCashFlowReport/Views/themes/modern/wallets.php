<?php helper('html'); ?>
<style>
.pcfr-shell .pcfr-mobile-list{display:none}.pcfr-shell .pcfr-mobile-item{border:1px solid #e8ece8;border-radius:1rem;padding:1rem;background:#fff;box-shadow:0 10px 22px rgba(39,60,48,.05)}.pcfr-shell .pcfr-mobile-loadmore{display:none}.pcfr-shell .pcfr-filter-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.85rem}.pcfr-shell .pcfr-nav{display:flex;gap:.75rem;flex-wrap:wrap}.pcfr-shell .pcfr-nav .btn{border-radius:.7rem}.pcfr-shell .pcfr-wallet-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.pcfr-shell .pcfr-wallet-card{display:block;border:1px solid #e8ece8;border-radius:1rem;padding:1rem;background:#fbfcfb;color:inherit;text-decoration:none;transition:.18s ease}.pcfr-shell .pcfr-wallet-card:hover{border-color:#cfd8cf;box-shadow:0 12px 24px rgba(39,60,48,.08);transform:translateY(-1px)}.pcfr-shell .pcfr-wallet-card-title{font-size:.82rem;color:#667085;margin-bottom:.45rem}.pcfr-shell .pcfr-wallet-card-balance{font-size:1.2rem;font-weight:700;color:#1f2937}.pcfr-shell .pcfr-wallet-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-top:.85rem}.pcfr-shell .pcfr-wallet-meta-item span{display:block;font-size:.74rem;color:#667085;margin-bottom:.15rem}.pcfr-shell .pcfr-table-wrap{width:100%;overflow:hidden}.pcfr-shell .pcfr-table-wrap .dataTables_wrapper{width:100%}.pcfr-shell .pcfr-table-wrap table{width:100%!important}.pcfr-shell .pcfr-table-wrap .dataTables_scrollHeadInner,.pcfr-shell .pcfr-table-wrap .dataTables_scrollHeadInner table,.pcfr-shell .pcfr-table-wrap .dataTables_scrollBody table{width:100%!important}.pcfr-shell .pcfr-wallet-note{font-size:.88rem;color:#667085}@media(max-width:991.98px){.pcfr-shell .pcfr-wallet-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767.98px){.pcfr-shell .pcfr-table-desktop{display:none}.pcfr-shell .pcfr-mobile-list{display:grid;gap:.85rem}.pcfr-shell .pcfr-mobile-loadmore{display:inline-flex}.pcfr-shell .pcfr-filter-grid,.pcfr-shell .pcfr-wallet-grid,.pcfr-shell .pcfr-wallet-meta{grid-template-columns:1fr}}
</style>
<div class="result-page-shell pcfr-shell">
	<div class="result-page-hero">
		<div><div class="result-page-kicker">Finance / Report</div><h3 class="result-page-heading"><?= esc($title) ?></h3><p class="result-page-copy mb-0"><?= !empty($selectedWallet) ? 'Menampilkan seluruh transaksi dari wallet terpilih dengan filter yang tetap tersedia.' : 'Ringkasan saldo per wallet tetap menghitung transaksi internal, sementara income dan expense transfer tidak didobel pada summary.' ?></p></div>
		<div class="pcfr-nav">
			<a href="<?= base_url('personal-cash-flow-report/transactions') ?>" class="btn btn-outline-primary btn-sm">Daftar Transaksi</a>
			<a href="<?= base_url('personal-cash-flow-report/transfers') ?>" class="btn btn-outline-primary btn-sm">Report Transfer</a>
			<a href="<?= base_url('personal-cash-flow-report/wallets') ?>" class="btn btn-primary btn-sm">Summary per Wallet</a>
		</div>
	</div>
	<div class="card result-list-card mb-4"><div class="card-body"><form method="get"><div class="pcfr-filter-grid">
		<div><label class="form-label">Dari</label><input type="date" name="date_from" class="form-control" value="<?= esc($filter['date_from']) ?>"></div>
		<div><label class="form-label">Sampai</label><input type="date" name="date_to" class="form-control" value="<?= esc($filter['date_to']) ?>"></div>
		<div><label class="form-label">Wallet</label><select name="wallet_id" class="form-select"><option value="">Semua</option><?php foreach($wallets as $wallet): ?><option value="<?= (int)$wallet['id_wallet'] ?>" <?= (int)$filter['wallet_id']===(int)$wallet['id_wallet']?'selected':'' ?>><?= esc($wallet['wallet_name']) ?></option><?php endforeach; ?></select></div>
		<div><label class="form-label">Kategori</label><select name="category_id" class="form-select"><option value="">Semua</option><?php foreach($categories as $category): ?><option value="<?= (int)$category['id_category'] ?>" <?= (int)$filter['category_id']===(int)$category['id_category']?'selected':'' ?>><?= esc($category['category_name']) ?></option><?php endforeach; ?></select></div>
		<div><label class="form-label">Tipe</label><select name="type" class="form-select"><option value="">Semua</option><option value="income" <?= $filter['type']==='income'?'selected':'' ?>>Pemasukan</option><option value="expense" <?= $filter['type']==='expense'?'selected':'' ?>>Pengeluaran</option></select></div>
		<div><label class="form-label">Deskripsi</label><input type="text" name="description" class="form-control" value="<?= esc($filter['description']) ?>" placeholder="Cari deskripsi"></div>
	</div><div class="mt-3 d-flex gap-2"><button type="submit" class="btn btn-primary btn-sm">Terapkan</button><a href="<?= base_url('personal-cash-flow-report/wallets') ?>" class="btn btn-outline-secondary btn-sm">Reset</a></div></form></div></div>
	<div class="card result-list-card mb-4"><div class="card-body">
		<div class="result-list-toolbar px-0 pt-0"><div><h5 class="mb-1">Summary Wallet</h5><p class="mb-0 text-muted">Saldo akhir memakai seluruh transaksi wallet, termasuk transfer masuk dan keluar.</p></div></div>
		<div class="pcfr-wallet-grid">
			<?php if (!$walletSummary): ?>
				<div class="alert alert-light border mb-0">Belum ada wallet aktif.</div>
			<?php else: ?>
				<?php foreach($walletSummary as $wallet): ?>
					<a href="<?= base_url('personal-cash-flow-report/wallets?' . http_build_query(array_merge($filter, ['wallet_id' => (int) $wallet['id_wallet']]))) ?>" class="pcfr-wallet-card">
						<div class="pcfr-wallet-card-title"><?= esc($wallet['wallet_name']) ?></div>
						<div class="pcfr-wallet-card-balance">Rp <?= number_format((float) $wallet['balance'], 0, ',', '.') ?></div>
						<div class="pcfr-wallet-meta">
							<div class="pcfr-wallet-meta-item"><span>Income</span><strong class="text-success">Rp <?= number_format((float) $wallet['total_income'], 0, ',', '.') ?></strong></div>
							<div class="pcfr-wallet-meta-item"><span>Expense</span><strong class="text-danger">Rp <?= number_format((float) $wallet['total_expense'], 0, ',', '.') ?></strong></div>
							<div class="pcfr-wallet-meta-item"><span>Saldo Awal</span><strong>Rp <?= number_format((float) $wallet['initial_balance'], 0, ',', '.') ?></strong></div>
							<div class="pcfr-wallet-meta-item"><span>Jumlah Transaksi</span><strong><?= number_format((int) $wallet['total_transaction'], 0, ',', '.') ?></strong></div>
						</div>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div></div>
	<div class="card result-list-card"><div class="card-body p-0">
		<div class="result-list-toolbar"><div><h5 class="mb-1"><?= !empty($selectedWallet) ? 'Detail Wallet: ' . esc($selectedWallet['wallet_name']) : 'Transaksi per Wallet' ?></h5><p class="mb-0 text-muted"><?= !empty($selectedWallet) ? 'Filter tetap tersedia untuk mempersempit transaksi wallet terpilih.' : 'Daftar transaksi mengikuti filter wallet, kategori, tipe, dan deskripsi.' ?></p></div></div>
		<div class="p-3"><input type="text" id="pcfr-search" class="form-control" placeholder="Search realtime"></div>
		<?php if (!empty($selectedWallet)): ?><div class="px-3 pb-2 pcfr-wallet-note">Wallet aktif: <strong><?= esc($selectedWallet['wallet_name']) ?></strong></div><?php endif; ?>
		<div class="pcfr-table-wrap pcfr-table-desktop"><table class="table table-striped align-middle mb-0 w-100" id="pcfr-table"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Wallet</th><th>Kategori</th><th>Deskripsi</th><th class="text-end">Nominal</th></tr></thead></table></div>
		<div id="pcfr-mobile-list" class="pcfr-mobile-list p-3 pt-0"></div>
		<div class="text-center p-3"><button type="button" id="pcfr-mobile-loadmore" class="btn btn-outline-primary btn-sm pcfr-mobile-loadmore">Load More</button></div>
	</div></div>
</div>
<div id="pcfr-data-url" class="d-none"><?= base_url('personal-cash-flow-report/getTransactionsDataDT?' . http_build_query($filter)) ?></div>
<div id="pcfr-mobile-url" class="d-none"><?= base_url('personal-cash-flow-report/ajaxGetTransactionsMobileList?' . http_build_query($filter)) ?></div>
<div id="pcfr-mode" class="d-none">wallets</div>
<script type="application/json" id="pcfr-columns"><?= json_encode([['data'=>'transaction_date'],['data'=>'transaction_type'],['data'=>'wallet_name'],['data'=>'category_name'],['data'=>'description'],['data'=>'nominal','className'=>'text-end']], JSON_UNESCAPED_SLASHES) ?></script>
