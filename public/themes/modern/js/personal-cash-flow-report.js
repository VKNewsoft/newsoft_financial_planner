jQuery(function ($) {
	const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
	const mode = ($('#pcfr-mode').text() || 'transactions').trim();
	const dataUrl = ($('#pcfr-data-url').text() || '').trim();
	const mobileUrl = ($('#pcfr-mobile-url').text() || '').trim();
	const columnsNode = $('#pcfr-columns');
	let table = null;
	let searchTimer = null;
	let mobilePage = 1;
	let mobileHasMore = false;
	let mobileLoading = false;

	function escapeHtml(value) {
		return $('<div>').text(value == null ? '' : value).html();
	}

	function formatCurrency(value) {
		return 'Rp ' + parseFloat(value || 0).toLocaleString('id-ID');
	}

	function renderTransactionCard(item) {
		let walletText = escapeHtml(item.wallet_name || '-');
		if (item.transfer_wallet_name) {
			walletText += ' -> ' + escapeHtml(item.transfer_wallet_name);
		}

		return '<div class="pcfr-mobile-item">' +
			'<div class="d-flex align-items-start justify-content-between gap-3">' +
				'<div><div class="fw-semibold">' + escapeHtml(item.description || '-') + '</div><div class="text-muted small">' + escapeHtml(item.transaction_date || '-') + '</div></div>' +
				'<div class="text-end"><div class="badge text-bg-' + (item.transaction_type === 'income' ? 'success' : 'danger') + '">' + escapeHtml(item.transaction_type || '-') + '</div><div class="fw-semibold mt-2 ' + (item.transaction_type === 'income' ? 'text-success' : 'text-danger') + '">' + formatCurrency(item.nominal) + '</div></div>' +
			'</div>' +
			'<div class="mt-3 small text-muted">Wallet</div><div>' + walletText + '</div>' +
			'<div class="mt-2 small text-muted">Kategori</div><div>' + escapeHtml(item.category_name || '-') + '</div>' +
			'<div class="mt-2 small text-muted">Catatan</div><div>' + escapeHtml(item.notes || '-') + '</div>' +
		'</div>';
	}

	function renderTransferCard(item) {
		return '<div class="pcfr-mobile-item">' +
			'<div class="d-flex align-items-start justify-content-between gap-3">' +
				'<div><div class="fw-semibold">' + escapeHtml(item.description || '-') + '</div><div class="text-muted small">' + escapeHtml(item.transaction_date || '-') + '</div></div>' +
				'<div class="text-end"><div class="fw-semibold text-primary">' + formatCurrency(item.nominal) + '</div></div>' +
			'</div>' +
			'<div class="mt-3 small text-muted">Transfer</div><div>' + escapeHtml(item.source_wallet_name || '-') + ' -> ' + escapeHtml(item.target_wallet_name || '-') + '</div>' +
			'<div class="mt-2 small text-muted">Catatan</div><div>' + escapeHtml(item.notes || '-') + '</div>' +
		'</div>';
	}

	function updateMobileList(items, appendMode) {
		const $list = $('#pcfr-mobile-list');
		if (!appendMode) {
			$list.empty();
		}
		$.each(items || [], function (_, item) {
			$list.append(mode === 'transfers' ? renderTransferCard(item) : renderTransactionCard(item));
		});
		$('#pcfr-mobile-loadmore').toggle(mobileHasMore);
	}

	function loadMobile(resetList) {
		if (!isMobile || !mobileUrl || mobileLoading) {
			return;
		}
		if (resetList) {
			mobilePage = 1;
		}
		mobileLoading = true;
		$('#pcfr-mobile-loadmore').prop('disabled', true).text('Loading...');
		$.getJSON(mobileUrl, { page: mobilePage, keyword: $('#pcfr-search').val() || '' })
			.done(function (response) {
				mobileHasMore = !!response.has_more;
				updateMobileList(response.data || [], !resetList && mobilePage > 1);
				if (mobileHasMore) {
					mobilePage += 1;
				}
			})
			.always(function () {
				mobileLoading = false;
				$('#pcfr-mobile-loadmore').prop('disabled', false).text('Load More');
			});
	}

	function initTable() {
		if (isMobile || !dataUrl || !columnsNode.length || typeof $.fn.DataTable === 'undefined') {
			return;
		}

		table = $('#pcfr-table').DataTable({
			processing: true,
			serverSide: true,
			scrollX: true,
			scrollY: window.WDIResultTable ? WDIResultTable.getScrollY('#pcfr-table') : '420px',
			ajax: {
				url: dataUrl,
				type: 'POST'
			},
			columns: JSON.parse(columnsNode.text()),
			order: [[0, 'desc']],
			autoWidth: false
		});

		if (window.WDIResultTable) {
			WDIResultTable.applyScrollBodyHeight(table, '#pcfr-table');
			WDIResultTable.bindResize(table, '#pcfr-table', 'pcfr-table');
		}

		table.on('draw', function () {
			table.columns.adjust();
		});
	}

	$('#pcfr-search').on('input', function () {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function () {
			if (isMobile) {
				loadMobile(true);
				return;
			}
			if (table) {
				table.search($('#pcfr-search').val()).draw();
			}
		}, 250);
	});

	$('#pcfr-mobile-loadmore').on('click', function () {
		loadMobile(false);
	});

	initTable();
	loadMobile(true);
});
