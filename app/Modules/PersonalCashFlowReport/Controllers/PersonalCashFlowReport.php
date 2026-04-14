<?php

namespace App\Modules\PersonalCashFlowReport\Controllers;

use App\Modules\PersonalCashFlowReport\Models\PersonalCashFlowReportModel;

class PersonalCashFlowReport extends \App\Modules\Common\Controllers\BaseController
{
	protected $model;

	public function __construct()
	{
		parent::__construct();
		$resultTableVersion = '?v=' . @filemtime(ROOTPATH . 'public/themes/modern/js/result-table.js');
		$this->model = new PersonalCashFlowReportModel();
		$this->data['site_title'] = 'Personal Cash Flow Report';
		$this->addStyle($this->config->baseURL . 'public/themes/modern/css/result-page.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/result-table.js' . $resultTableVersion);
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/personal-cash-flow-report.js');
	}

	private function getBaseFilters(): array
	{
		return [
			'date_from' => $this->model->getDateFrom(),
			'date_to' => $this->model->getDateTo(),
			'wallet_id' => $this->model->getFilterWalletId(),
			'category_id' => $this->model->getFilterCategoryId(),
			'type' => $this->model->getFilterType(),
			'description' => $this->model->getFilterDescription(),
			'keyword' => trim((string) ($this->request->getGet('keyword') ?? '')),
			'source_wallet_id' => $this->model->getFilterSourceWalletId(),
			'target_wallet_id' => $this->model->getFilterTargetWalletId(),
		];
	}

	public function index()
	{
		return $this->transactions();
	}

	public function transactions()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$this->data = array_merge($this->data, [
			'title' => 'Daftar Transaksi',
			'filter' => $filters,
			'wallets' => $this->model->getWalletOptions(),
			'categories' => $this->model->getCategoryOptions(),
			'currentSection' => 'transactions',
		]);
		$this->view('transactions.php', $this->data);
	}

	public function transfers()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$this->data = array_merge($this->data, [
			'title' => 'Report Transfer',
			'filter' => $filters,
			'wallets' => $this->model->getWalletOptions(),
			'currentSection' => 'transfers',
		]);
		$this->view('transfers.php', $this->data);
	}

	public function wallets()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$selectedWallet = $this->model->getWalletById((int) $filters['wallet_id']);
		$this->data = array_merge($this->data, [
			'title' => $selectedWallet ? 'Detail Wallet' : 'Summary per Wallet',
			'filter' => $filters,
			'wallets' => $this->model->getWalletOptions(),
			'walletSummary' => $this->model->getWalletSummaryReport($filters),
			'categories' => $this->model->getCategoryOptions(),
			'selectedWallet' => $selectedWallet,
			'currentSection' => 'wallets',
		]);
		$this->view('wallets.php', $this->data);
	}

	public function getTransactionsDataDT()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$query = $this->model->getTransactionDataTable($filters);
		$total = $this->model->countTransactionData($filters);
		$result = [
			'draw' => $this->request->getPost('draw') ?: 1,
			'recordsTotal' => $total,
			'recordsFiltered' => $query['total_filtered'],
			'data' => [],
		];

		foreach ($query['data'] as $row) {
			$result['data'][] = [
				'transaction_date' => esc($row['transaction_date']),
				'transaction_type' => '<span class="badge text-bg-' . ($row['transaction_type'] === 'income' ? 'success' : 'danger') . '">' . esc($row['transaction_type']) . '</span>',
				'wallet_name' => esc($row['wallet_name'] ?: '-'),
				'category_name' => esc($row['category_name'] ?: '-'),
				'description' => '<div class="fw-semibold">' . esc($row['description']) . '</div><small class="text-muted">' . esc($row['notes'] ?: '-') . '</small>',
				'nominal' => '<div class="text-end fw-semibold ' . ($row['transaction_type'] === 'income' ? 'text-success' : 'text-danger') . '">Rp ' . number_format((float) $row['nominal'], 0, ',', '.') . '</div>',
			];
		}

		return $this->response->setJSON($result);
	}

	public function getTransfersDataDT()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$query = $this->model->getTransferDataTable($filters);
		$total = $this->model->countTransferData($filters);
		$result = [
			'draw' => $this->request->getPost('draw') ?: 1,
			'recordsTotal' => $total,
			'recordsFiltered' => $query['total_filtered'],
			'data' => [],
		];

		foreach ($query['data'] as $row) {
			$result['data'][] = [
				'transaction_date' => esc($row['transaction_date']),
				'source_wallet_name' => esc($row['source_wallet_name'] ?: '-'),
				'target_wallet_name' => esc($row['target_wallet_name'] ?: '-'),
				'description' => '<div class="fw-semibold">' . esc($row['description']) . '</div><small class="text-muted">' . esc($row['notes'] ?: '-') . '</small>',
				'nominal' => '<div class="text-end fw-semibold text-primary">Rp ' . number_format((float) $row['nominal'], 0, ',', '.') . '</div>',
			];
		}

		return $this->response->setJSON($result);
	}

	public function ajaxGetTransactionsMobileList()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$page = max(1, (int) ($this->request->getGet('page') ?? 1));
		$data = $this->model->getTransactionMobilePage($filters, $page, 8);
		return $this->response->setJSON(['status' => 'ok'] + $data);
	}

	public function ajaxGetTransfersMobileList()
	{
		$this->hasPermissionPrefix('read');
		$filters = $this->getBaseFilters();
		$page = max(1, (int) ($this->request->getGet('page') ?? 1));
		$data = $this->model->getTransferMobilePage($filters, $page, 8);
		return $this->response->setJSON(['status' => 'ok'] + $data);
	}
}
