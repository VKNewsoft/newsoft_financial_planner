<?php
/**
 * @author VKNewsoft - Newsoft Developer
 * @year 2026
 */

namespace App\Modules\PersonalCashFlow\Controllers;

use App\Modules\PersonalCashFlow\Models\PersonalCashFlowModel;

class PersonalCashFlow extends \App\Modules\Common\Controllers\BaseController
{
	protected $model;

	public function __construct()
	{
		parent::__construct();
		$resultTableVersion = '?v=' . @filemtime(ROOTPATH . 'public/themes/modern/js/result-table.js');
		$this->model = new PersonalCashFlowModel();
		$this->data['site_title'] = 'Personal Cash Flow';
		$this->addStyle($this->config->baseURL . 'public/themes/modern/css/result-page.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/result-table.js' . $resultTableVersion);
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/personal-cash-flow.js');
	}

	public function index()
	{
		$this->hasPermissionPrefix('read');

		$period = $this->model->getSelectedPeriod();
		$type = $this->model->getSelectedType();

		$this->data['title'] = 'Personal Cash Flow';
		$this->data['period'] = $period;
		$this->data['type'] = $type;
		$this->data['overallSummary'] = $this->model->getOverallSummary();
		$this->data['summary'] = $this->model->getSummary($period);
		$this->data['chart'] = $this->model->getMonthlyChartData($period, 6);
		$this->data['expenseCategoryChart'] = $this->model->getExpenseCategoryChart($period);
		$this->data['transactionCount'] = $this->model->countTransactions($period, $type);
		$this->data['categoriesGrouped'] = $this->model->getCategoriesGrouped();
		$this->data['transactionCategories'] = $this->model->getCategories();
		$this->data['wallets'] = $this->model->getWallets();
		$this->data['walletSummary'] = $this->model->getWalletSummary();
		$this->data['transferSummary'] = $this->model->getTransferSummary($period);
		$this->data['transferReport'] = $this->model->getTransferReport($period);
		$this->data['msg'] = $this->session->getFlashdata('msg') ?: [];

		$this->view('index.php', $this->data);
	}

	public function getDataDT()
	{
		$this->hasPermissionPrefix('read');

		$period = $this->model->getSelectedPeriod();
		$type = $this->model->getSelectedType();
		$query = $this->model->getTransactionListDataTable($period, $type);
		$total = $this->model->countTransactions($period, $type);

		$result = [
			'draw' => $this->request->getPost('draw') ?: 1,
			'recordsTotal' => $total,
			'recordsFiltered' => $query['total_filtered'],
			'data' => [],
		];

		$typeLabels = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
		$typeBadges = ['income' => 'success', 'expense' => 'danger'];

		foreach ($query['data'] as $transaction) {
			$badge = $typeBadges[$transaction['transaction_type']] ?? 'secondary';
			$typeLabel = $typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type'];
			$color = esc($transaction['color'] ?: '#6c757d');
			$description = esc($transaction['description']);
			$walletLabel = esc($transaction['wallet_name'] ?: '-');
			$walletMeta = 'Wallet: ' . $walletLabel;
			if (!empty($transaction['transfer_wallet_name'])) {
				$walletMeta .= ' | Transfer: ' . esc($transaction['transfer_wallet_name']);
			}
			$notes = esc($transaction['notes'] ?: '-');
			$amount = 'Rp ' . number_format((float) $transaction['nominal'], 0, ',', '.');
			$amountClass = $transaction['transaction_type'] === 'income' ? 'text-success' : 'text-danger';
			$id = (int) $transaction['id_transaction'];

			$result['data'][] = [
				'ignore_select' => '<div class="text-center"><input type="checkbox" class="form-check-input bulk-selector transaction-selector" value="' . $id . '" data-type="' . esc($transaction['transaction_type']) . '"></div>',
				'transaction_date' => esc($transaction['transaction_date']),
				'transaction_type' => '<span class="badge text-bg-' . $badge . '">' . esc($typeLabel) . '</span>',
				'category_name' => '<span class="pcf-category-chip"><span class="pcf-category-dot" style="background:' . $color . '"></span>' . esc($transaction['category_name']) . '</span>',
				'description' => '<div class="pcf-transaction-desc"><div class="fw-semibold">' . $description . '</div><small class="text-muted">' . $walletMeta . '</small><small class="text-muted">' . $notes . '</small></div>',
				'nominal' => '<div class="text-end fw-semibold ' . $amountClass . '">' . $amount . '</div>',
				'ignore_search_action' => '<div class="btn-group btn-group-sm">'
					. '<button type="button" class="btn btn-success btn-edit-transaction" data-id="' . $id . '">Edit</button>'
					. '<form method="post" action="' . base_url('personal-cash-flow/delete') . '" onsubmit="return confirm(\'Hapus transaksi ini?\');">'
					. '<input type="hidden" name="id" value="' . $id . '">'
					. '<input type="hidden" name="delete" value="1">'
					. '<button type="submit" class="btn btn-danger">Hapus</button>'
					. '</form></div>',
			];
		}

		return $this->response->setJSON($result);
	}

	public function ajaxGetTransactionMobileList()
	{
		$this->hasPermissionPrefix('read');

		$period = $this->model->getSelectedPeriod();
		$type = $this->model->getSelectedType();
		$page = max(1, (int) ($this->request->getGet('page') ?? 1));
		$perPage = max(1, (int) ($this->request->getGet('per_page') ?? 8));
		$keyword = trim((string) ($this->request->getGet('keyword') ?? ''));
		$descriptionFilter = trim((string) ($this->request->getGet('description_filter') ?? ''));
		$categoryFilter = trim((string) ($this->request->getGet('category_filter') ?? ''));

		$data = $this->model->getTransactionListPage($period, $type, $page, $perPage, $keyword, $descriptionFilter, $categoryFilter);

		$typeLabels = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
		$typeBadges = ['income' => 'success', 'expense' => 'danger'];
		$items = [];

		foreach ($data['data'] as $transaction) {
			$items[] = [
				'id' => (int) $transaction['id_transaction'],
				'transaction_type' => (string) $transaction['transaction_type'],
				'transaction_type_label' => (string) ($typeLabels[$transaction['transaction_type']] ?? $transaction['transaction_type']),
				'transaction_badge' => (string) ($typeBadges[$transaction['transaction_type']] ?? 'secondary'),
				'transaction_date' => (string) $transaction['transaction_date'],
				'category_name' => (string) $transaction['category_name'],
				'wallet_name' => (string) ($transaction['wallet_name'] ?: '-'),
				'transfer_wallet_name' => (string) ($transaction['transfer_wallet_name'] ?: ''),
				'color' => (string) ($transaction['color'] ?: '#6c757d'),
				'description' => (string) $transaction['description'],
				'notes' => (string) ($transaction['notes'] ?: '-'),
				'nominal' => 'Rp ' . number_format((float) $transaction['nominal'], 0, ',', '.'),
				'nominal_class' => $transaction['transaction_type'] === 'income' ? 'text-success' : 'text-danger',
				'delete_url' => base_url('personal-cash-flow/delete'),
			];
		}

		return $this->response->setJSON([
			'status' => 'ok',
			'items' => $items,
			'page' => $data['page'],
			'per_page' => $data['per_page'],
			'total_filtered' => $data['total_filtered'],
			'has_more' => $data['has_more'],
		]);
	}

	public function delete()
	{
		$this->hasPermissionPrefix('delete');

		if (!$this->request->getPost('delete')) {
			return redirect()->to(base_url('personal-cash-flow'));
		}

		$result = $this->model->deleteTransaction((int) $this->request->getPost('id'));
		$this->session->setFlashdata('msg', $result);
		return redirect()->to(base_url('personal-cash-flow'));
	}

	public function categories()
	{
		$this->hasPermissionPrefix('read');

		$this->data['title'] = 'Kategori Personal Cash Flow';
		$this->data['msg'] = $this->session->getFlashdata('msg') ?: [];
		$this->data['categories'] = $this->model->getCategories();
		$this->view('category-result.php', $this->data);
	}

	public function wallets()
	{
		$this->hasPermissionPrefix('read');

		$this->data['title'] = 'Wallet Personal Cash Flow';
		$this->data['msg'] = $this->session->getFlashdata('msg') ?: [];
		$this->data['wallets'] = $this->model->getWallets();
		$this->view('wallet-result.php', $this->data);
	}

	public function deleteCategory()
	{
		$this->hasPermissionPrefix('delete');

		if (!$this->request->getPost('delete')) {
			return redirect()->to(base_url('personal-cash-flow/categories'));
		}

		$result = $this->model->deleteCategory((int) $this->request->getPost('id'));
		$this->session->setFlashdata('msg', $result);
		return redirect()->to(base_url('personal-cash-flow/categories'));
	}

	public function deleteWallet()
	{
		$this->hasPermissionPrefix('delete');

		if (!$this->request->getPost('delete')) {
			return redirect()->to(base_url('personal-cash-flow/wallets'));
		}

		$result = $this->model->deleteWallet((int) $this->request->getPost('id'));
		$this->session->setFlashdata('msg', $result);
		return redirect()->to(base_url('personal-cash-flow/wallets'));
	}

	public function ajaxGetTransactionForm()
	{
		$this->hasPermissionPrefix('read');

		$idTransaction = (int) ($this->request->getGet('id') ?? 0);
		$transaction = [
			'transaction_type' => 'expense',
			'transaction_date' => date('Y-m-d'),
			'id_category' => '',
			'nominal' => '',
			'description' => '',
			'notes' => '',
		];

		if ($idTransaction > 0) {
			$this->hasPermissionPrefix('update');
			$transaction = $this->model->getTransactionById($idTransaction);
			if (!$transaction) {
				return $this->response->setStatusCode(404)->setBody('Data transaksi tidak ditemukan');
			}
		}

		$data = $this->data;
		$data['transaction'] = $transaction;
		$data['categories'] = $this->model->getCategoriesGrouped();
		$data['wallets'] = $this->model->getWallets();
		echo $this->fetchViewFile('themes/modern/transaction-modal-form.php', $data);
	}

	public function ajaxSaveTransaction()
	{
		$idTransaction = (int) ($this->request->getPost('id') ?? 0);
		if ($idTransaction > 0) {
			$this->hasPermissionPrefix('update');
		} else {
			$this->hasPermissionPrefix('create');
		}

		return $this->response->setJSON($this->model->saveTransaction($idTransaction));
	}

	public function ajaxBulkUpdateCategory()
	{
		$this->hasPermissionPrefix('update');
		return $this->response->setJSON($this->model->bulkUpdateCategory());
	}

	public function ajaxGetCategoryForm()
	{
		$this->hasPermissionPrefix('read');

		$idCategory = (int) ($this->request->getGet('id') ?? 0);
		$category = [
			'transaction_type' => 'expense',
			'category_name' => '',
			'description' => '',
			'color' => '#6c757d',
		];

		if ($idCategory > 0) {
			$this->hasPermissionPrefix('update');
			$category = $this->model->getCategoryById($idCategory, false);
			if (!$category || (int) $category['is_default'] === 1) {
				return $this->response->setStatusCode(404)->setBody('Data kategori tidak ditemukan');
			}
		}

		$data = $this->data;
		$data['category'] = $category;
		echo $this->fetchViewFile('themes/modern/category-modal-form.php', $data);
	}

	public function ajaxSaveCategory()
	{
		$idCategory = (int) ($this->request->getPost('id') ?? 0);
		if ($idCategory > 0) {
			$this->hasPermissionPrefix('update');
		} else {
			$this->hasPermissionPrefix('create');
		}

		return $this->response->setJSON($this->model->saveCategory($idCategory));
	}

	public function ajaxGetWalletForm()
	{
		$this->hasPermissionPrefix('read');

		$idWallet = (int) ($this->request->getGet('id') ?? 0);
		$wallet = [
			'wallet_name' => '',
			'wallet_type' => 'cash',
			'description' => '',
			'initial_balance' => '',
		];

		if ($idWallet > 0) {
			$this->hasPermissionPrefix('update');
			$wallet = $this->model->getWalletById($idWallet);
			if (!$wallet) {
				return $this->response->setStatusCode(404)->setBody('Data wallet tidak ditemukan');
			}
		}

		$data = $this->data;
		$data['wallet'] = $wallet;
		echo $this->fetchViewFile('themes/modern/wallet-modal-form.php', $data);
	}

	public function ajaxSaveWallet()
	{
		$idWallet = (int) ($this->request->getPost('id') ?? 0);
		if ($idWallet > 0) {
			$this->hasPermissionPrefix('update');
		} else {
			$this->hasPermissionPrefix('create');
		}

		return $this->response->setJSON($this->model->saveWallet($idWallet));
	}
}
