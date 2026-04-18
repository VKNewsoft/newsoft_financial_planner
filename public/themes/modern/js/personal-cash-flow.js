jQuery(document).ready(function () {
	const cashFlowBaseUrl = typeof module_url !== 'undefined' ? module_url : current_url;
	const categoryGroups = window.personalCashFlowCategories || { income: [], expense: [] };
	let transactionTable = null;
	let searchDebounceTimer = null;
	const isMobileTransactionView = window.matchMedia('(max-width: 767.98px)').matches;
	let mobileTransactionPage = 1;
	let mobileHasMore = false;
	let mobileLoading = false;

	function showToast(message) {
		const Toast = Swal.mixin({
			toast: true,
			position: 'top-end',
			showConfirmButton: false,
			timer: 2500,
			timerProgressBar: true,
			iconColor: 'white',
			customClass: {
				popup: 'bg-success text-light toast p-2'
			}
		});

		Toast.fire({
			html: '<div class="toast-content"><i class="far fa-check-circle me-2"></i>' + message + '</div>'
		});
	}

	function renderErrorAlert(data) {
		let messages = [];
		if (data.form_errors) {
			$.each(data.form_errors, function (_, value) {
				messages.push(value);
			});
		} else if (data.message) {
			messages.push(data.message);
		} else {
			messages.push('Data belum valid');
		}

		return '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
			(messages.length > 1 ? '<ul class="mb-0"><li>' + messages.join('</li><li>') + '</li></ul>' : messages[0]) +
			'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
			'</div>';
	}

	function formatRibuan(value) {
		value = String(value || '').replace(/\D/g, '');
		if (!value) {
			return '';
		}

		return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	}

	function getSelectedTransactionCheckboxes() {
		return $('.transaction-selector:checked');
	}

	function getVisibleTransactionCheckboxes() {
		return $('.transaction-selector');
	}

	function syncBulkToolbar() {
		const $selected = getSelectedTransactionCheckboxes();
		const $bulkSelect = $('#bulk-category-select');
		const $bulkButton = $('.btn-bulk-update');
		const selectedTypes = [...new Set($selected.map(function () {
			return $(this).data('type');
		}).get())];

		if (!$selected.length) {
			$bulkSelect.html('<option value="">Pilih transaksi dulu</option>').prop('disabled', true);
			$bulkButton.prop('disabled', true);
			return;
		}

		if (selectedTypes.length !== 1) {
			$bulkSelect.html('<option value="">Jenis transaksi campuran tidak bisa dibulk</option>').prop('disabled', true);
			$bulkButton.prop('disabled', true);
			return;
		}

		const type = selectedTypes[0];
		const categories = categoryGroups[type] || [];
		let options = '<option value="">Pilih kategori tujuan</option>';
		$.each(categories, function (_, category) {
			options += '<option value="' + category.id_category + '">' + category.category_name + '</option>';
		});

		$bulkSelect.html(options).prop('disabled', false);
		$bulkButton.prop('disabled', false);
	}

	function resetBulkSelection() {
		$('#select-all-transactions').prop('checked', false);
		getVisibleTransactionCheckboxes().prop('checked', false);
		syncBulkToolbar();
	}

	function reloadTransactionTable(resetPaging = false) {
		if (isMobileTransactionView) {
			loadMobileTransactions(true);
			return;
		}

		if (transactionTable) {
			transactionTable.ajax.reload(function () {
				resetBulkSelection();
			}, resetPaging);
		}
	}

	function renderMobileTransactionCard(item) {
		let walletMeta = $('<div>').text(item.wallet_name || '-').html();
		if (item.transfer_wallet_name) {
			walletMeta += ' -> ' + $('<div>').text(item.transfer_wallet_name).html();
		}

		return '' +
			'<div class="pcf-mobile-item">' +
				'<div class="pcf-mobile-item-head">' +
					'<div class="pcf-mobile-item-left">' +
						'<div class="pcf-mobile-checkbox">' +
							'<input type="checkbox" class="form-check-input bulk-selector transaction-selector" value="' + item.id + '" data-type="' + item.transaction_type + '">' +
						'</div>' +
						'<div>' +
							'<div class="pcf-mobile-title">' + $('<div>').text(item.description).html() + '</div>' +
							'<div class="pcf-mobile-date">' + $('<div>').text(item.transaction_date).html() + '</div>' +
						'</div>' +
					'</div>' +
					'<div class="text-end">' +
						'<div class="badge text-bg-' + item.transaction_badge + '">' + $('<div>').text(item.transaction_type_label).html() + '</div>' +
						'<div class="pcf-mobile-amount ' + item.nominal_class + '">' + $('<div>').text(item.nominal).html() + '</div>' +
					'</div>' +
				'</div>' +
				'<div class="pcf-mobile-meta">' +
					'<div>' +
						'<span class="pcf-mobile-meta-label">Kategori</span>' +
						'<span class="pcf-category-chip"><span class="pcf-category-dot" style="background:' + item.color + '"></span>' + $('<div>').text(item.category_name).html() + '</span>' +
					'</div>' +
					'<div>' +
						'<span class="pcf-mobile-meta-label">Wallet</span>' +
						'<div>' + walletMeta + '</div>' +
					'</div>' +
					'<div>' +
						'<span class="pcf-mobile-meta-label">Catatan</span>' +
						'<div>' + $('<div>').text(item.notes).html() + '</div>' +
					'</div>' +
				'</div>' +
				'<div class="pcf-mobile-actions">' +
					'<button type="button" class="btn btn-success btn-sm btn-soft btn-edit-transaction" data-id="' + item.id + '">Edit</button>' +
					'<form method="post" action="' + item.delete_url + '" onsubmit="return confirm(\'Hapus transaksi ini?\');">' +
						'<input type="hidden" name="id" value="' + item.id + '">' +
						'<input type="hidden" name="delete" value="1">' +
						'<button type="submit" class="btn btn-danger btn-sm btn-soft w-100">Hapus</button>' +
					'</form>' +
				'</div>' +
			'</div>';
	}

	function updateMobileListState(items, appendMode) {
		const $list = $('#pcf-mobile-list');
		const $empty = $('#pcf-mobile-empty');
		const $loadMore = $('#pcf-mobile-loadmore');

		if (!appendMode) {
			$list.empty();
		}

		if (items.length) {
			$.each(items, function (_, item) {
				$list.append(renderMobileTransactionCard(item));
			});
			$empty.removeClass('is-visible');
		} else if (!appendMode) {
			$empty.addClass('is-visible');
		}

		$loadMore.toggle(mobileHasMore);
		resetBulkSelection();
	}

	function loadMobileTransactions(resetList) {
		if (!isMobileTransactionView || mobileLoading) {
			return;
		}

		const $loadMore = $('#pcf-mobile-loadmore');
		const baseUrl = $('#pcf-mobile-data-url').text();
		if (!baseUrl) {
			return;
		}

		if (resetList) {
			mobileTransactionPage = 1;
		}

		mobileLoading = true;
		$loadMore.prop('disabled', true).text('Loading...');

		$.ajax({
			url: baseUrl,
			type: 'GET',
			dataType: 'json',
			data: {
				page: mobileTransactionPage,
				per_page: 8,
				keyword: $('#transaction-search').val(),
				description_filter: $('#transaction-description-filter').val(),
				category_filter: $('#transaction-category-filter').val()
			},
			success: function (response) {
				mobileHasMore = !!response.has_more;
				updateMobileListState(response.items || [], !resetList && mobileTransactionPage > 1);

				if (mobileHasMore) {
					mobileTransactionPage += 1;
				}
			},
			error: function (xhr) {
				show_alert('Error !!!', xhr.responseText || 'Gagal memuat transaksi', 'error');
			},
			complete: function () {
				mobileLoading = false;
				$loadMore.prop('disabled', false).text('Load More');
			}
		});
	}

	function initTransactionTable() {
		if (isMobileTransactionView || !$('#table-data').length || typeof $.fn.DataTable === 'undefined') {
			return;
		}

		const column = $.parseJSON($('#dataTables-column').html());
		const url = $('#dataTables-url').text();
		const settings = {
			processing: true,
			serverSide: true,
			scrollX: true,
			scrollY: window.WDIResultTable ? WDIResultTable.getScrollY('#table-data') : ($('#dataTables-scrolls').text() || '420'),
			ajax: {
				url: url,
				type: 'POST',
				data: function (d) {
					d.description_filter = $('#transaction-description-filter').val();
					d.category_filter = $('#transaction-category-filter').val();
				}
			},
			columns: column,
			drawCallback: function () {
				resetBulkSelection();
			}
		};

		const $addSetting = $('#dataTables-setting');
		if ($addSetting.length) {
			const addSetting = $.parseJSON($addSetting.html());
			$.each(addSetting, function (key, value) {
				settings[key] = value;
			});
		}

		transactionTable = $('#table-data').DataTable(settings);
		if (window.WDIResultTable) {
			WDIResultTable.applyScrollBodyHeight(transactionTable, '#table-data');
			WDIResultTable.bindResize(transactionTable, '#table-data', 'personal-cash-flow-table');
		}
	}

	function openBootboxForm(options) {
		const dialog = bootbox.dialog({
			title: options.title,
			message: '<div class="text-center text-secondary py-4"><div class="spinner-border"></div></div>',
			centerVertical: true,
			buttons: {
				cancel: {
					label: 'Cancel'
				},
				success: {
					label: options.submitLabel || 'Submit',
					className: 'btn-success submit'
				}
			}
		});

		const $buttons = dialog.find('button').prop('disabled', true);
		const $submit = dialog.find('button.submit');

		$.get(options.formUrl, function (html) {
			$buttons.prop('disabled', false);
			dialog.find('.modal-body').empty().append(html);
			dialog.addClass('personal-cash-flow-modal');
		}).fail(function (xhr) {
			dialog.modal('hide');
			show_alert('Error !!!', xhr.responseText || 'Gagal memuat form', 'error');
		});

		$submit.on('click', function (e) {
			e.preventDefault();
			dialog.find('.alert').remove();

			const form = dialog.find('form')[0];
			if (!form) {
				return false;
			}

			$submit.prepend('<i class="fas fa-circle-notch fa-spin me-2"></i>');
			$buttons.prop('disabled', true);

			$.ajax({
				type: 'POST',
				url: options.submitUrl,
				data: new FormData(form),
				processData: false,
				contentType: false,
				dataType: 'json',
				success: function (data) {
					$submit.find('i').remove();
					$buttons.prop('disabled', false);

					if (data.status === 'ok') {
						showToast(data.message || 'Data berhasil disimpan');
						dialog.modal('hide');
						window.location.reload();
						return;
					}

					dialog.find('.modal-body').prepend(renderErrorAlert(data));
				},
				error: function (xhr) {
					$submit.find('i').remove();
					$buttons.prop('disabled', false);
					show_alert('Error !!!', xhr.responseText || 'Terjadi kesalahan', 'error');
				}
			});

			return false;
		});
	}

	$('body').on('click', '.btn-add-transaction', function (e) {
		e.preventDefault();
		openBootboxForm({
			title: 'Tambah Transaksi',
			formUrl: cashFlowBaseUrl + '/ajaxGetTransactionForm',
			submitUrl: cashFlowBaseUrl + '/ajaxSaveTransaction',
			submitLabel: 'Simpan'
		});
	});

	$('body').on('click', '.btn-edit-transaction', function (e) {
		e.preventDefault();
		openBootboxForm({
			title: 'Edit Transaksi',
			formUrl: cashFlowBaseUrl + '/ajaxGetTransactionForm?id=' + $(this).data('id'),
			submitUrl: cashFlowBaseUrl + '/ajaxSaveTransaction',
			submitLabel: 'Update'
		});
	});

	$('body').on('click', '.btn-add-category', function (e) {
		e.preventDefault();
		openBootboxForm({
			title: 'Tambah Kategori',
			formUrl: cashFlowBaseUrl + '/ajaxGetCategoryForm',
			submitUrl: cashFlowBaseUrl + '/ajaxSaveCategory',
			submitLabel: 'Simpan'
		});
	});

	$('body').on('click', '.btn-edit-category', function (e) {
		e.preventDefault();
		openBootboxForm({
			title: 'Edit Kategori',
			formUrl: cashFlowBaseUrl + '/ajaxGetCategoryForm?id=' + $(this).data('id'),
			submitUrl: cashFlowBaseUrl + '/ajaxSaveCategory',
			submitLabel: 'Update'
		});
	});

	$('body').on('click', '.btn-add-wallet', function (e) {
		e.preventDefault();
		openBootboxForm({
			title: 'Tambah Wallet',
			formUrl: cashFlowBaseUrl + '/ajaxGetWalletForm',
			submitUrl: cashFlowBaseUrl + '/ajaxSaveWallet',
			submitLabel: 'Simpan'
		});
	});

	$('body').on('click', '.btn-edit-wallet', function (e) {
		e.preventDefault();
		openBootboxForm({
			title: 'Edit Wallet',
			formUrl: cashFlowBaseUrl + '/ajaxGetWalletForm?id=' + $(this).data('id'),
			submitUrl: cashFlowBaseUrl + '/ajaxSaveWallet',
			submitLabel: 'Update'
		});
	});

	$('body').on('input', '#transaction-modal-form .numeric-only, #wallet-modal-form .numeric-only', function () {
		this.value = formatRibuan(this.value);
	});

	$('body').on('change', '#select-all-transactions', function () {
		getVisibleTransactionCheckboxes().prop('checked', $(this).is(':checked'));
		syncBulkToolbar();
	});

	$('body').on('change', '.transaction-selector', function () {
		const allCount = getVisibleTransactionCheckboxes().length;
		const checkedCount = getSelectedTransactionCheckboxes().length;
		$('#select-all-transactions').prop('checked', allCount > 0 && allCount === checkedCount);
		syncBulkToolbar();
	});

	$('body').on('click', '.btn-bulk-update', function (e) {
		e.preventDefault();

		const selectedIds = getSelectedTransactionCheckboxes().map(function () {
			return $(this).val();
		}).get();
		const idCategory = $('#bulk-category-select').val();

		if (!selectedIds.length || !idCategory) {
			show_alert('Error !!!', 'Pilih transaksi dan kategori tujuan terlebih dahulu', 'error');
			return;
		}

		const $button = $(this);
		$button.prop('disabled', true).prepend('<i class="fas fa-circle-notch fa-spin me-2"></i>');

		$.ajax({
			type: 'POST',
			url: cashFlowBaseUrl + '/ajaxBulkUpdateCategory',
			dataType: 'json',
			data: {
				selected_ids: selectedIds,
				id_category: idCategory
			},
			success: function (data) {
				$button.prop('disabled', false).find('i').remove();

				if (data.status === 'ok') {
					showToast(data.message || 'Kategori berhasil diperbarui');
					reloadTransactionTable(false);
					return;
				}

				show_alert('Error !!!', data.message || 'Bulk update gagal', 'error');
			},
			error: function (xhr) {
				$button.prop('disabled', false).find('i').remove();
				show_alert('Error !!!', xhr.responseText || 'Terjadi kesalahan', 'error');
			}
		});
	});

	$('#transaction-search').on('input', function () {
		const value = $(this).val();
		clearTimeout(searchDebounceTimer);
		searchDebounceTimer = setTimeout(function () {
			if (isMobileTransactionView) {
				loadMobileTransactions(true);
			} else if (transactionTable) {
				transactionTable.search(value).draw();
			}
		}, 250);
	});

	$('#transaction-category-filter, #transaction-description-filter').on('input change', function () {
		clearTimeout(searchDebounceTimer);
		searchDebounceTimer = setTimeout(function () {
			reloadTransactionTable(true);
		}, 250);
	});

	$('#pcf-mobile-loadmore').on('click', function () {
		loadMobileTransactions(false);
	});

	initTransactionTable();
	if (isMobileTransactionView) {
		loadMobileTransactions(true);
	}
	syncBulkToolbar();
});
