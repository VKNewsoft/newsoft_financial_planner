<?php

namespace App\Modules\PersonalCashFlowReport\Models;

class PersonalCashFlowReportModel extends \App\Modules\Common\Models\BaseModel
{
	private function getCurrentUserId(): int
	{
		return (int) ($this->session->get('user')['id_user'] ?? 0);
	}

	private function parseDate(string $value, string $fallback): string
	{
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
	}

	public function getDateFrom(): string
	{
		return $this->parseDate((string) ($this->request->getGet('date_from') ?? date('Y-m-01')), date('Y-m-01'));
	}

	public function getDateTo(): string
	{
		return $this->parseDate((string) ($this->request->getGet('date_to') ?? date('Y-m-d')), date('Y-m-d'));
	}

	public function getFilterWalletId(): int
	{
		$idWallet = (int) ($this->request->getGet('wallet_id') ?? 0);
		return $idWallet > 0 ? $idWallet : 0;
	}

	public function getFilterCategoryId(): int
	{
		$idCategory = (int) ($this->request->getGet('category_id') ?? 0);
		return $idCategory > 0 ? $idCategory : 0;
	}

	public function getFilterType(): string
	{
		$type = (string) ($this->request->getGet('type') ?? '');
		return in_array($type, ['income', 'expense'], true) ? $type : '';
	}

	public function getFilterDescription(): string
	{
		return trim((string) ($this->request->getGet('description') ?? ''));
	}

	public function getFilterSourceWalletId(): int
	{
		$idWallet = (int) ($this->request->getGet('source_wallet_id') ?? 0);
		return $idWallet > 0 ? $idWallet : 0;
	}

	public function getFilterTargetWalletId(): int
	{
		$idWallet = (int) ($this->request->getGet('target_wallet_id') ?? 0);
		return $idWallet > 0 ? $idWallet : 0;
	}

	public function getWalletOptions(): array
	{
		return $this->db->table('personal_cash_flow_wallet')
			->where('id_user', $this->getCurrentUserId())
			->where('aktif', 1)
			->orderBy('wallet_name', 'ASC')
			->get()
			->getResultArray();
	}

	public function getWalletById(int $idWallet): ?array
	{
		if ($idWallet <= 0) {
			return null;
		}

		$result = $this->db->table('personal_cash_flow_wallet')
			->where('id_user', $this->getCurrentUserId())
			->where('id_wallet', $idWallet)
			->where('aktif', 1)
			->get()
			->getRowArray();

		return $result ?: null;
	}

	public function getCategoryOptions(): array
	{
		return $this->db->table('personal_cash_flow_category')
			->where('aktif', 1)
			->where('LOWER(category_name) !=', 'transfer')
			->groupStart()
				->where('id_user', $this->getCurrentUserId())
				->orWhere('id_user IS NULL', null, false)
			->groupEnd()
			->orderBy('transaction_type', 'ASC')
			->orderBy('category_name', 'ASC')
			->get()
			->getResultArray();
	}

	private function getTransactionBaseBuilder(array $filters)
	{
		$builder = $this->db->table('personal_cash_flow_transaction a')
			->select('a.*, c.category_name, c.color, w.wallet_name, wt.wallet_name AS transfer_wallet_name')
			->join('personal_cash_flow_category c', 'c.id_category = a.id_category', 'left')
			->join('personal_cash_flow_wallet w', 'w.id_wallet = a.id_wallet', 'left')
			->join('personal_cash_flow_wallet wt', 'wt.id_wallet = a.id_wallet_transfer_target', 'left')
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->where('a.transaction_date >=', $filters['date_from'])
			->where('a.transaction_date <=', $filters['date_to']);

		if (!empty($filters['wallet_id'])) {
			$builder->where('a.id_wallet', (int) $filters['wallet_id']);
		}

		if (!empty($filters['category_id'])) {
			$builder->where('a.id_category', (int) $filters['category_id']);
		}

		if (!empty($filters['type'])) {
			$builder->where('a.transaction_type', $filters['type']);
		}

		if (!empty($filters['description'])) {
			$builder->groupStart()
				->like('a.description', $filters['description'])
				->orLike('a.notes', $filters['description'])
			->groupEnd();
		}

		return $builder;
	}

	private function getTransferBaseBuilder(array $filters)
	{
		$builder = $this->db->table('personal_cash_flow_transaction a')
			->select('a.id_transaction, a.transaction_date, a.nominal, a.description, a.notes, ws.wallet_name AS source_wallet_name, wt.wallet_name AS target_wallet_name')
			->join('personal_cash_flow_wallet ws', 'ws.id_wallet = a.id_wallet', 'left')
			->join('personal_cash_flow_wallet wt', 'wt.id_wallet = a.id_wallet_transfer_target', 'left')
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->where('a.transaction_type', 'expense')
			->where('a.id_wallet_transfer_target IS NOT NULL', null, false)
			->where('a.transaction_date >=', $filters['date_from'])
			->where('a.transaction_date <=', $filters['date_to']);

		if (!empty($filters['source_wallet_id'])) {
			$builder->where('a.id_wallet', (int) $filters['source_wallet_id']);
		}

		if (!empty($filters['target_wallet_id'])) {
			$builder->where('a.id_wallet_transfer_target', (int) $filters['target_wallet_id']);
		}

		if (!empty($filters['description'])) {
			$builder->groupStart()
				->like('a.description', $filters['description'])
				->orLike('a.notes', $filters['description'])
				->orLike('ws.wallet_name', $filters['description'])
				->orLike('wt.wallet_name', $filters['description'])
			->groupEnd();
		}

		return $builder;
	}

	private function applyDataTableSearch($builder, array $allowedColumns): void
	{
		$searchValue = trim((string) ($this->request->getPost('search')['value'] ?? ''));
		if ($searchValue === '') {
			return;
		}

		$columns = $this->request->getPost('columns');
		$searchColumns = [];
		if (is_array($columns)) {
			foreach ($columns as $column) {
				$columnName = (string) ($column['data'] ?? '');
				if (isset($allowedColumns[$columnName])) {
					$searchColumns[] = $allowedColumns[$columnName];
				}
			}
		}

		if (!$searchColumns) {
			$searchColumns = array_values($allowedColumns);
		}

		$builder->groupStart();
		foreach (array_values(array_unique($searchColumns)) as $index => $column) {
			if ($index === 0) {
				$builder->like($column, $searchValue);
			} else {
				$builder->orLike($column, $searchValue);
			}
		}
		$builder->groupEnd();
	}

	private function applyDataTableOrderLimit($builder, array $allowedColumns, string $defaultColumn): void
	{
		$columns = $this->request->getPost('columns');
		$orderData = $this->request->getPost('order');
		$orderColumn = $defaultColumn;
		$orderDirection = 'DESC';

		if (is_array($orderData) && !empty($orderData[0]) && is_array($columns)) {
			$columnIndex = (int) ($orderData[0]['column'] ?? 0);
			$columnName = (string) ($columns[$columnIndex]['data'] ?? '');
			if (isset($allowedColumns[$columnName])) {
				$orderColumn = $allowedColumns[$columnName];
			}
			if (strtoupper((string) ($orderData[0]['dir'] ?? 'DESC')) === 'ASC') {
				$orderDirection = 'ASC';
			}
		}

		$start = max(0, (int) ($this->request->getPost('start') ?? 0));
		$length = max(1, (int) ($this->request->getPost('length') ?? 10));
		$builder->orderBy($orderColumn, $orderDirection)->limit($length, $start);
	}

	public function getTransactionDataTable(array $filters): array
	{
		$allowedColumns = [
			'transaction_date' => 'a.transaction_date',
			'transaction_type' => 'a.transaction_type',
			'wallet_name' => 'w.wallet_name',
			'category_name' => 'c.category_name',
			'description' => 'a.description',
			'nominal' => 'a.nominal',
		];

		$countBuilder = $this->getTransactionBaseBuilder($filters);
		$this->applyDataTableSearch($countBuilder, $allowedColumns);
		$totalFiltered = $countBuilder->countAllResults();

		$dataBuilder = $this->getTransactionBaseBuilder($filters);
		$this->applyDataTableSearch($dataBuilder, $allowedColumns);
		$this->applyDataTableOrderLimit($dataBuilder, $allowedColumns, 'a.transaction_date');
		$data = $dataBuilder->orderBy('a.id_transaction', 'DESC')->get()->getResultArray();

		return ['data' => $data, 'total_filtered' => $totalFiltered];
	}

	public function countTransactionData(array $filters): int
	{
		return $this->getTransactionBaseBuilder($filters)->countAllResults();
	}

	public function getTransactionMobilePage(array $filters, int $page = 1, int $perPage = 8): array
	{
		$page = max(1, $page);
		$perPage = max(1, min(20, $perPage));
		$offset = ($page - 1) * $perPage;

		$builder = $this->getTransactionBaseBuilder($filters);
		if ($filters['keyword'] !== '') {
			$builder->groupStart()
				->like('a.description', $filters['keyword'])
				->orLike('a.notes', $filters['keyword'])
				->orLike('w.wallet_name', $filters['keyword'])
				->orLike('c.category_name', $filters['keyword'])
			->groupEnd();
		}

		$totalFiltered = $builder->countAllResults(false);
		$data = $builder->orderBy('a.transaction_date', 'DESC')->orderBy('a.id_transaction', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

		return [
			'data' => $data,
			'page' => $page,
			'has_more' => ($offset + count($data)) < $totalFiltered,
			'total_filtered' => $totalFiltered,
		];
	}

	public function getTransferDataTable(array $filters): array
	{
		$allowedColumns = [
			'transaction_date' => 'a.transaction_date',
			'source_wallet_name' => 'ws.wallet_name',
			'target_wallet_name' => 'wt.wallet_name',
			'description' => 'a.description',
			'nominal' => 'a.nominal',
		];

		$countBuilder = $this->getTransferBaseBuilder($filters);
		$this->applyDataTableSearch($countBuilder, $allowedColumns);
		$totalFiltered = $countBuilder->countAllResults();

		$dataBuilder = $this->getTransferBaseBuilder($filters);
		$this->applyDataTableSearch($dataBuilder, $allowedColumns);
		$this->applyDataTableOrderLimit($dataBuilder, $allowedColumns, 'a.transaction_date');
		$data = $dataBuilder->orderBy('a.id_transaction', 'DESC')->get()->getResultArray();

		return ['data' => $data, 'total_filtered' => $totalFiltered];
	}

	public function countTransferData(array $filters): int
	{
		return $this->getTransferBaseBuilder($filters)->countAllResults();
	}

	public function getTransferMobilePage(array $filters, int $page = 1, int $perPage = 8): array
	{
		$page = max(1, $page);
		$perPage = max(1, min(20, $perPage));
		$offset = ($page - 1) * $perPage;

		$builder = $this->getTransferBaseBuilder($filters);
		if ($filters['keyword'] !== '') {
			$builder->groupStart()
				->like('a.description', $filters['keyword'])
				->orLike('a.notes', $filters['keyword'])
				->orLike('ws.wallet_name', $filters['keyword'])
				->orLike('wt.wallet_name', $filters['keyword'])
			->groupEnd();
		}

		$totalFiltered = $builder->countAllResults(false);
		$data = $builder->orderBy('a.transaction_date', 'DESC')->orderBy('a.id_transaction', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

		return [
			'data' => $data,
			'page' => $page,
			'has_more' => ($offset + count($data)) < $totalFiltered,
			'total_filtered' => $totalFiltered,
		];
	}

	public function getWalletSummaryReport(array $filters): array
	{
		$sql = 'SELECT
					w.id_wallet,
					w.wallet_name,
					w.wallet_type,
					w.description,
					w.initial_balance,
					COALESCE(SUM(CASE WHEN t.transaction_type = "income" AND t.id_wallet_transfer_target IS NULL THEN t.nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN t.transaction_type = "expense" AND t.id_wallet_transfer_target IS NULL THEN t.nominal ELSE 0 END), 0) AS total_expense,
					COALESCE(SUM(CASE WHEN t.transaction_type = "income" THEN t.nominal ELSE 0 END), 0) AS ledger_income,
					COALESCE(SUM(CASE WHEN t.transaction_type = "expense" THEN t.nominal ELSE 0 END), 0) AS ledger_expense,
					COUNT(t.id_transaction) AS total_transaction
				FROM personal_cash_flow_wallet w
				LEFT JOIN personal_cash_flow_transaction t
					ON t.id_wallet = w.id_wallet
					AND t.id_user = w.id_user
					AND t.isDeleted = 0
					AND t.transaction_date >= ?
					AND t.transaction_date <= ?
				WHERE w.id_user = ?
					AND w.aktif = 1';

		$params = [$filters['date_from'], $filters['date_to'], $this->getCurrentUserId()];
		if (!empty($filters['wallet_id'])) {
			$sql .= ' AND w.id_wallet = ?';
			$params[] = (int) $filters['wallet_id'];
		}

		$sql .= ' GROUP BY w.id_wallet, w.wallet_name, w.wallet_type, w.description, w.initial_balance ORDER BY w.wallet_name ASC';
		$rows = $this->db->query($sql, $params)->getResultArray();
		foreach ($rows as &$row) {
			$row['initial_balance'] = (float) $row['initial_balance'];
			$row['total_income'] = (float) $row['total_income'];
			$row['total_expense'] = (float) $row['total_expense'];
			$row['ledger_income'] = (float) $row['ledger_income'];
			$row['ledger_expense'] = (float) $row['ledger_expense'];
			$row['balance'] = $row['initial_balance'] + $row['ledger_income'] - $row['ledger_expense'];
			$row['total_transaction'] = (int) $row['total_transaction'];
		}

		return $rows;
	}
}
