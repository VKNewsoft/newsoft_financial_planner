<?php helper('html'); ?>
<style>
.pcfr-shell .pcfr-mobile-list{display:none}.pcfr-shell .pcfr-mobile-item{border:1px solid #e8ece8;border-radius:1rem;padding:1rem;background:#fff;box-shadow:0 10px 22px rgba(39,60,48,.05)}.pcfr-shell .pcfr-mobile-loadmore{display:none}.pcfr-shell .pcfr-filter-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.85rem}.pcfr-shell .pcfr-nav{display:flex;gap:.75rem;flex-wrap:wrap}.pcfr-shell .pcfr-nav .btn{border-radius:.7rem}.pcfr-shell .pcfr-table-wrap{width:100%;overflow:hidden}.pcfr-shell .pcfr-table-wrap .dataTables_wrapper{width:100%}.pcfr-shell .pcfr-table-wrap table{width:100%!important}.pcfr-shell .pcfr-table-wrap .dataTables_scrollHeadInner,.pcfr-shell .pcfr-table-wrap .dataTables_scrollHeadInner table,.pcfr-shell .pcfr-table-wrap .dataTables_scrollBody table{width:100%!important}@media(max-width:767.98px){.pcfr-shell .pcfr-table-desktop{display:none}.pcfr-shell .pcfr-mobile-list{display:grid;gap:.85rem}.pcfr-shell .pcfr-mobile-loadmore{display:inline-flex}.pcfr-shell .pcfr-filter-grid{grid-template-columns:1fr}}
</style>
<div class="result-page-shell pcfr-shell">
	<div class="result-page-hero">
		<div><div class="result-page-kicker">Finance / Report</div><h3 class="result-page-heading"><?= esc($title) ?></h3><p class="result-page-copy mb-0">Report transfer A ke B dengan filter wallet asal, wallet tujuan, dan deskripsi.</p></div>
		<div class="pcfr-nav">
			<a href="<?= base_url('personal-cash-flow-report/transactions') ?>" class="btn btn-outline-primary btn-sm">Daftar Transaksi</a>
			<a href="<?= base_url('personal-cash-flow-report/transfers') ?>" class="btn btn-primary btn-sm">Report Transfer</a>
			<a href="<?= base_url('personal-cash-flow-report/wallets') ?>" class="btn btn-outline-primary btn-sm">Summary per Wallet</a>
		</div>
	</div>
	<div class="card result-list-card mb-4"><div class="card-body"><form method="get"><div class="pcfr-filter-grid">
		<div><label class="form-label">Dari</label><input type="date" name="date_from" class="form-control" value="<?= esc($filter['date_from']) ?>"></div>
		<div><label class="form-label">Sampai</label><input type="date" name="date_to" class="form-control" value="<?= esc($filter['date_to']) ?>"></div>
		<div><label class="form-label">Wallet Asal</label><select name="source_wallet_id" class="form-select"><option value="">Semua</option><?php foreach($wallets as $wallet): ?><option value="<?= (int)$wallet['id_wallet'] ?>" <?= (int)$filter['source_wallet_id']===(int)$wallet['id_wallet']?'selected':'' ?>><?= esc($wallet['wallet_name']) ?></option><?php endforeach; ?></select></div>
		<div><label class="form-label">Wallet Tujuan</label><select name="target_wallet_id" class="form-select"><option value="">Semua</option><?php foreach($wallets as $wallet): ?><option value="<?= (int)$wallet['id_wallet'] ?>" <?= (int)$filter['target_wallet_id']===(int)$wallet['id_wallet']?'selected':'' ?>><?= esc($wallet['wallet_name']) ?></option><?php endforeach; ?></select></div>
		<div><label class="form-label">Deskripsi</label><input type="text" name="description" class="form-control" value="<?= esc($filter['description']) ?>" placeholder="Cari transfer"></div>
	</div><div class="mt-3 d-flex gap-2"><button type="submit" class="btn btn-primary btn-sm">Terapkan</button><a href="<?= base_url('personal-cash-flow-report/transfers') ?>" class="btn btn-outline-secondary btn-sm">Reset</a></div></form></div></div>
	<div class="card result-list-card"><div class="card-body p-0">
		<div class="result-list-toolbar"><div><h5 class="mb-1">Data Transfer</h5><p class="mb-0 text-muted">Report transfer dipisahkan dari income dan expense reguler.</p></div></div>
		<div class="p-3"><input type="text" id="pcfr-search" class="form-control" placeholder="Search realtime"></div>
		<div class="pcfr-table-wrap pcfr-table-desktop"><table class="table table-striped align-middle mb-0 w-100" id="pcfr-table"><thead><tr><th>Tanggal</th><th>Wallet Asal</th><th>Wallet Tujuan</th><th>Deskripsi</th><th class="text-end">Nominal</th></tr></thead></table></div>
		<div id="pcfr-mobile-list" class="pcfr-mobile-list p-3 pt-0"></div>
		<div class="text-center p-3"><button type="button" id="pcfr-mobile-loadmore" class="btn btn-outline-primary btn-sm pcfr-mobile-loadmore">Load More</button></div>
	</div></div>
</div>
<div id="pcfr-data-url" class="d-none"><?= base_url('personal-cash-flow-report/getTransfersDataDT?' . http_build_query($filter)) ?></div>
<div id="pcfr-mobile-url" class="d-none"><?= base_url('personal-cash-flow-report/ajaxGetTransfersMobileList?' . http_build_query($filter)) ?></div>
<div id="pcfr-mode" class="d-none">transfers</div>
<script type="application/json" id="pcfr-columns"><?= json_encode([['data'=>'transaction_date'],['data'=>'source_wallet_name'],['data'=>'target_wallet_name'],['data'=>'description'],['data'=>'nominal','className'=>'text-end']], JSON_UNESCAPED_SLASHES) ?></script>
