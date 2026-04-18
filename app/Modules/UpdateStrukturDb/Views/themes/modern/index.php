<?php helper('html'); ?>
<style>
.usd-shell .usd-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.usd-shell .usd-card{border:1px solid #e7ece7;border-radius:1rem;padding:1rem;background:#fff}.usd-shell .usd-card h4{margin:0 0 .35rem;font-size:1.4rem}.usd-shell .usd-actions{display:flex;gap:.75rem;flex-wrap:wrap}.usd-shell .usd-section{display:grid;gap:1rem}.usd-shell .usd-diff{border:1px solid #e7ece7;border-radius:1rem;overflow:hidden;background:#fff}.usd-shell .usd-diff-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.1rem;border-bottom:1px solid #eef2ee;background:#fbfcfb}.usd-shell .usd-badge{font-size:.72rem;font-weight:700;padding:.3rem .55rem;border-radius:999px;text-transform:uppercase;letter-spacing:.04em}.usd-shell .usd-badge-add{background:#dcfce7;color:#166534}.usd-shell .usd-badge-modify{background:#fef3c7;color:#92400e}.usd-shell .usd-badge-review{background:#e0f2fe;color:#075985}.usd-shell .usd-body{display:grid;grid-template-columns:1fr 1fr}.usd-shell .usd-pane{padding:1rem 1.1rem}.usd-shell .usd-pane-old{border-right:1px solid #eef2ee;background:#fcfcfc}.usd-shell .usd-pane-new{background:#f8fff9}.usd-shell .usd-pane-label{font-size:.78rem;color:#667085;margin-bottom:.45rem;text-transform:uppercase;letter-spacing:.04em}.usd-shell pre{margin:0;white-space:pre-wrap;word-break:break-word;font-size:.82rem;line-height:1.45;font-family:Consolas,monospace}.usd-shell .usd-empty{color:#98a2b3;font-style:italic}.usd-shell .usd-summary{font-size:.88rem;color:#667085}.usd-shell .usd-list-empty{border:1px dashed #d8e1d8;border-radius:1rem;padding:1rem;background:#fbfcfb}.usd-shell .usd-columns{display:grid;gap:1rem}@media(max-width:991.98px){.usd-shell .usd-body{grid-template-columns:1fr}.usd-shell .usd-pane-old{border-right:0;border-bottom:1px solid #eef2ee}}@media(max-width:767.98px){.usd-shell .usd-grid{grid-template-columns:1fr}}
</style>
<?php
$schemaItems = $preview['schema_items'] ?? [];
$coreItems = $preview['core_items'] ?? [];
$badgeClass = [
	'add' => 'usd-badge-add',
	'modify' => 'usd-badge-modify',
	'update' => 'usd-badge-modify',
	'review' => 'usd-badge-review',
	'missing_target' => 'usd-badge-review',
];
?>
<div class="result-page-shell usd-shell">
	<div class="result-page-hero">
		<div>
			<div class="result-page-kicker">Maintenance / Database</div>
			<h3 class="result-page-heading"><?= $current_module['judul_module'] ?></h3>
			<p class="result-page-copy mb-0">Bandingkan struktur lama dan target schema seperti diff, lalu jalankan sinkronisasi aman untuk schema dan data core.</p>
		</div>
		<div class="usd-actions">
			<form method="post">
				<button type="submit" name="execute_sync" value="1" class="btn btn-primary btn-sm">Eksekusi Update</button>
			</form>
		</div>
	</div>

	<div class="usd-grid mb-4">
		<div class="usd-card"><div class="text-muted small mb-2">Schema Action</div><h4><?= number_format((int) $preview['total_schema'], 0, ',', '.') ?></h4><div class="usd-summary">Perubahan struktur yang bisa dijalankan tanpa drop data.</div></div>
		<div class="usd-card"><div class="text-muted small mb-2">Core Data Action</div><h4><?= number_format((int) $preview['total_core'], 0, ',', '.') ?></h4><div class="usd-summary">Sinkronisasi insert/update untuk data core berdasarkan key unik.</div></div>
		<div class="usd-card"><div class="text-muted small mb-2">Total Preview</div><h4><?= number_format((int) $preview['total_all'], 0, ',', '.') ?></h4><div class="usd-summary">Aman dijalankan berulang dan tidak memaksa id harus sama.</div></div>
	</div>

	<div class="card result-list-card mb-4">
		<div class="card-body">
			<h5 class="mb-3">Hasil Eksekusi</h5>
			<?php if (!empty($msg)): ?>
				<?php if (($msg['status'] ?? '') === 'ok'): ?>
					<div class="alert alert-success"><?= is_array($msg['message'] ?? null) ? '<ul class="mb-0"><li>' . implode('</li><li>', array_map('esc', $msg['message'])) . '</li></ul>' : esc($msg['message'] ?? 'Berhasil') ?></div>
				<?php else: ?>
					<div class="alert alert-danger"><?= is_array($msg['message'] ?? null) ? '<ul class="mb-0"><li>' . implode('</li><li>', array_map('esc', $msg['message'])) . '</li></ul>' : esc($msg['message'] ?? 'Gagal') ?></div>
					<?php if (!empty($msg['detail']) && is_array($msg['detail'])): ?><div class="alert alert-light border mb-0"><ul class="mb-0"><li><?= implode('</li><li>', array_map('esc', $msg['detail'])) ?></li></ul></div><?php endif; ?>
				<?php endif; ?>
			<?php else: ?>
				<div class="alert alert-light border mb-0">Belum ada eksekusi. Review diff terlebih dahulu sebelum menjalankan update.</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="card result-list-card mb-4">
		<div class="card-body">
			<h5 class="mb-3">Diff Struktur DB</h5>
			<div class="usd-section">
				<?php if (!$schemaItems): ?>
					<div class="usd-list-empty">Struktur database sudah sinkron dengan target schema.</div>
				<?php else: ?>
					<?php foreach ($schemaItems as $item): ?>
						<div class="usd-diff">
							<div class="usd-diff-head">
								<div>
									<div class="fw-semibold"><?= esc($item['table']) ?> / <?= esc($item['group']) ?> / <?= esc($item['name']) ?></div>
									<div class="usd-summary"><?= $item['sql'] ? esc($item['sql']) : 'Perubahan ini ditandai untuk review manual agar tidak memaksa operasi yang berisiko.' ?></div>
								</div>
								<span class="usd-badge <?= esc($badgeClass[$item['action']] ?? 'usd-badge-review') ?>"><?= esc($item['action']) ?></span>
							</div>
							<div class="usd-body">
								<div class="usd-pane usd-pane-old">
									<div class="usd-pane-label">Current DB</div>
									<?php if ($item['left'] !== null && $item['left'] !== ''): ?><pre><?= esc($item['left']) ?></pre><?php else: ?><div class="usd-empty">Tidak ada di current DB</div><?php endif; ?>
								</div>
								<div class="usd-pane usd-pane-new">
									<div class="usd-pane-label">Target Schema</div>
									<?php if ($item['right'] !== null && $item['right'] !== ''): ?><pre><?= esc($item['right']) ?></pre><?php else: ?><div class="usd-empty">Tidak ada di target schema</div><?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="card result-list-card">
		<div class="card-body">
			<h5 class="mb-3">Diff Data Core</h5>
			<div class="usd-section">
				<?php if (!$coreItems): ?>
					<div class="usd-list-empty">Data core sudah sinkron.</div>
				<?php else: ?>
					<?php foreach ($coreItems as $item): ?>
						<div class="usd-diff">
							<div class="usd-diff-head">
								<div>
									<div class="fw-semibold"><?= esc($item['group']) ?> / <?= esc($item['name']) ?></div>
									<div class="usd-summary"><?= $item['action'] === 'update' ? 'Data existing akan di-update berdasarkan key unik.' : 'Data baru akan ditambahkan jika belum ada.' ?></div>
								</div>
								<span class="usd-badge <?= esc($badgeClass[$item['action']] ?? 'usd-badge-review') ?>"><?= esc($item['action']) ?></span>
							</div>
							<div class="usd-body">
								<div class="usd-pane usd-pane-old">
									<div class="usd-pane-label">Current DB</div>
									<?php if ($item['left'] !== null && $item['left'] !== ''): ?><pre><?= esc($item['left']) ?></pre><?php else: ?><div class="usd-empty">Tidak ada di current DB</div><?php endif; ?>
								</div>
								<div class="usd-pane usd-pane-new">
									<div class="usd-pane-label">Target Schema</div>
									<?php if ($item['right'] !== null && $item['right'] !== ''): ?><pre><?= esc($item['right']) ?></pre><?php else: ?><div class="usd-empty">Tidak ada di target schema</div><?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
