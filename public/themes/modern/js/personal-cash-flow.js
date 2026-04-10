jQuery(document).ready(function () {
	const cashFlowBaseUrl = typeof module_url !== 'undefined' ? module_url : current_url;
	const categoryGroups = window.personalCashFlowCategories || { income: [], expense: [] };

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
		return $('.transaction-selector:visible:checked');
	}

	function getVisibleTransactionCheckboxes() {
		return $('.transaction-selector:visible');
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

	$('body').on('input', '#transaction-modal-form .numeric-only', function () {
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
					window.location.reload();
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

	syncBulkToolbar();
});
