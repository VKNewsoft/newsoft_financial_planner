<?php
/**
 * @author VKNewsoft - Newsoft Developer
 * @year 2026
 */

namespace App\Modules\PersonalCashFlow\Models;

class PersonalCashFlowModel extends \App\Modules\Common\Models\BaseModel
{
	private function getTransactionListAllowedColumns(): array
	{
		return array_keys($this->getTransactionListColumnMap());
	}

	private function getTransactionListColumnMap(): array
	{
		return [
			'transaction_date' => 'a.transaction_date',
			'transaction_type' => 'a.transaction_type',
			'category_name' => 'c.category_name',
			'description' => 'a.description',
			'notes' => 'a.notes',
			'nominal' => 'a.nominal',
			'id_transaction' => 'a.id_transaction',
		];
	}

	private function getTransactionListBuilder(string $period, string $type = '')
	{
		[$startDate, $endDate] = $this->getPeriodRange($period);

		$builder = $this->db->table('personal_cash_flow_transaction a')
			->select('a.*, c.category_name, c.color, c.is_default, w.wallet_name, wt.wallet_name AS transfer_wallet_name')
			->join('personal_cash_flow_category c', 'c.id_category = a.id_category', 'left')
			->join('personal_cash_flow_wallet w', 'w.id_wallet = a.id_wallet', 'left')
			->join('personal_cash_flow_wallet wt', 'wt.id_wallet = a.id_wallet_transfer_target', 'left')
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->where('a.transaction_date >=', $startDate)
			->where('a.transaction_date <=', $endDate);

		if ($type) {
			$builder->where('a.transaction_type', $type);
		}

		return $builder;
	}

	private function getDatatableRequestedColumns(): array
	{
		$requestedColumns = $this->request->getPost('columns');
		if (!is_array($requestedColumns)) {
			return [];
		}

		$allowedColumns = $this->getTransactionListAllowedColumns();
		$result = [];

		foreach ($requestedColumns as $column) {
			$columnName = (string) ($column['data'] ?? '');
			if ($columnName !== '' && in_array($columnName, $allowedColumns, true)) {
				$result[] = $columnName;
			}
		}

		return array_values(array_unique($result));
	}

	private function applyTransactionDatatableSearch($builder): void
	{
		$globalSearch = trim((string) ($this->request->getPost('search')['value'] ?? ''));
		$descriptionSearch = trim((string) ($this->request->getPost('description_filter') ?? ''));
		$categorySearch = trim((string) ($this->request->getPost('category_filter') ?? ''));

		if ($globalSearch !== '') {
			$searchColumns = $this->getDatatableRequestedColumns();
			if (!$searchColumns) {
				$searchColumns = ['category_name', 'description', 'notes'];
			}

			$columnMap = $this->getTransactionListColumnMap();

			$builder->groupStart();
			foreach ($searchColumns as $index => $columnAlias) {
				$column = $columnMap[$columnAlias] ?? null;
				if (!$column) {
					continue;
				}

				if ($index === 0) {
					$builder->like($column, $globalSearch);
				} else {
					$builder->orLike($column, $globalSearch);
				}
			}
			$builder->groupEnd();
		}

		if ($descriptionSearch !== '') {
			$builder->groupStart()
				->like('a.description', $descriptionSearch)
				->orLike('a.notes', $descriptionSearch)
			->groupEnd();
		}

		if ($categorySearch !== '') {
			if (ctype_digit($categorySearch)) {
				$builder->where('a.id_category', (int) $categorySearch);
			} else {
				$builder->like('c.category_name', $categorySearch);
			}
		}
	}

	private function applyTransactionDatatableOrderAndLimit($builder): void
	{
		$columns = $this->request->getPost('columns');
		$orderData = $this->request->getPost('order');
		$allowedColumns = $this->getTransactionListAllowedColumns();
		$columnMap = $this->getTransactionListColumnMap();
		$orderColumn = 'a.transaction_date';
		$orderDirection = 'DESC';

		if (is_array($orderData) && !empty($orderData[0]) && is_array($columns)) {
			$columnIndex = (int) ($orderData[0]['column'] ?? 0);
			$direction = strtoupper((string) ($orderData[0]['dir'] ?? 'DESC'));
			$requestedColumn = (string) ($columns[$columnIndex]['data'] ?? '');

			if (in_array($requestedColumn, $allowedColumns, true)) {
				$orderColumn = $columnMap[$requestedColumn] ?? $orderColumn;
			}

			if ($direction === 'ASC') {
				$orderDirection = 'ASC';
			}
		}

		$start = max(0, (int) ($this->request->getPost('start') ?? 0));
		$length = (int) ($this->request->getPost('length') ?? 10);
		if ($length < 1) {
			$length = 10;
		}

		$builder
			->orderBy($orderColumn, $orderDirection)
			->orderBy('a.id_transaction', 'DESC')
			->limit($length, $start);
	}

	private function applyTransactionListFilters($builder, string $descriptionFilter = '', string $categoryFilter = '', string $keyword = ''): void
	{
		$descriptionFilter = trim($descriptionFilter);
		$categoryFilter = trim($categoryFilter);
		$keyword = trim($keyword);

		if ($keyword !== '') {
			$builder->groupStart()
				->like('a.description', $keyword)
				->orLike('a.notes', $keyword)
				->orLike('c.category_name', $keyword)
			->groupEnd();
		}

		if ($descriptionFilter !== '') {
			$builder->groupStart()
				->like('a.description', $descriptionFilter)
				->orLike('a.notes', $descriptionFilter)
			->groupEnd();
		}

		if ($categoryFilter !== '') {
			if (ctype_digit($categoryFilter)) {
				$builder->where('a.id_category', (int) $categoryFilter);
			} else {
				$builder->like('c.category_name', $categoryFilter);
			}
		}
	}

	private function getCurrentUserId(): int
	{
		return (int) ($this->session->get('user')['id_user'] ?? 0);
	}

	private function getNonTransferAggregateSqlCondition(): string
	{
		return 'id_wallet_transfer_target IS NULL';
	}

	private function isTransferCategoryName(string $categoryName): bool
	{
		return strtolower(trim($categoryName)) === 'transfer';
	}

	private function getTransferCategoryId(string $transactionType): int
	{
		$result = $this->db->table('personal_cash_flow_category')
			->where('id_user', $this->getCurrentUserId())
			->where('transaction_type', $transactionType)
			->where('aktif', 1)
			->where('LOWER(category_name) =', 'transfer')
			->get()
			->getRowArray();

		if ($result) {
			return (int) $result['id_category'];
		}

		$this->db->table('personal_cash_flow_category')->insert([
			'id_user' => $this->getCurrentUserId(),
			'transaction_type' => $transactionType,
			'category_name' => 'Transfer',
			'description' => 'Kategori internal untuk transfer wallet',
			'color' => $transactionType === 'income' ? '#0ea5e9' : '#f97316',
			'is_default' => 1,
			'aktif' => 1,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		]);

		return (int) $this->db->insertID();
	}

	private function findTransferPair(array $transaction): ?array
	{
		if (empty($transaction['id_wallet_transfer_target'])) {
			return null;
		}

		$pairType = $transaction['transaction_type'] === 'expense' ? 'income' : 'expense';

		$result = $this->db->table('personal_cash_flow_transaction')
			->where('id_user', $this->getCurrentUserId())
			->where('isDeleted', 0)
			->where('transaction_type', $pairType)
			->where('transaction_date', $transaction['transaction_date'])
			->where('nominal', $transaction['nominal'])
			->where('description', $transaction['description'])
			->where('id_wallet', (int) $transaction['id_wallet_transfer_target'])
			->where('id_wallet_transfer_target', (int) $transaction['id_wallet'])
			->where('id_transaction !=', (int) $transaction['id_transaction'])
			->orderBy('id_transaction', 'DESC')
			->get()
			->getRowArray();

		return $result ?: null;
	}

	private function parseNominalInput($value): float
	{
		$value = trim((string) $value);
		if ($value === '') {
			return 0;
		}

		$value = preg_replace('/\s+/', '', $value);

		if (strpos($value, ',') !== false) {
			$value = str_replace('.', '', $value);
			$value = str_replace(',', '.', $value);
		} else {
			$parts = explode('.', $value);
			if (count($parts) > 2) {
				$value = str_replace('.', '', $value);
			} elseif (count($parts) === 2 && strlen($parts[1]) !== 2) {
				$value = str_replace('.', '', $value);
			}
		}

		$value = preg_replace('/[^0-9.]/', '', $value);
		return (float) $value;
	}

	private function normalizeSelectedTransactionIds(): array
	{
		$ids = $this->request->getPost('selected_ids');
		if (!is_array($ids)) {
			$ids = [];
		}

		$result = [];
		foreach ($ids as $id) {
			if (is_numeric($id)) {
				$result[] = (int) $id;
			}
		}

		return array_values(array_unique(array_filter($result)));
	}

	public function getSelectedPeriod(): string
	{
		$period = (string) ($this->request->getGet('period') ?? date('Y-m'));
		if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
			return date('Y-m');
		}

		return $period;
	}

	public function getSelectedType(): string
	{
		$type = (string) ($this->request->getGet('type') ?? '');
		return in_array($type, ['income', 'expense'], true) ? $type : '';
	}

	public function getSelectedKeyword(): string
	{
		return trim((string) ($this->request->getGet('keyword') ?? ''));
	}

	public function getSelectedDescriptionFilter(): string
	{
		return trim((string) ($this->request->getPost('description_filter') ?? $this->request->getGet('description_filter') ?? ''));
	}

	public function getSelectedCategoryFilter(): string
	{
		return trim((string) ($this->request->getPost('category_filter') ?? $this->request->getGet('category_filter') ?? ''));
	}

	private function getPeriodRange(string $period): array
	{
		$startDate = $period . '-01';
		$endDate = date('Y-m-t', strtotime($startDate));

		return [$startDate, $endDate];
	}

	public function getCategories(?string $type = null): array
	{
		$builder = $this->db->table('personal_cash_flow_category')
			->where('aktif', 1)
			->where('LOWER(category_name) !=', 'transfer')
			->groupStart()
				->where('id_user', $this->getCurrentUserId())
				->orWhere('id_user IS NULL', null, false)
			->groupEnd();

		if ($type) {
			$builder->where('transaction_type', $type);
		}

		return $builder
			->orderBy('transaction_type', 'ASC')
			->orderBy('is_default', 'DESC')
			->orderBy('category_name', 'ASC')
			->get()
			->getResultArray();
	}

	public function getWallets(): array
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
		$result = $this->db->table('personal_cash_flow_wallet')
			->where('id_wallet', $idWallet)
			->where('id_user', $this->getCurrentUserId())
			->where('aktif', 1)
			->get()
			->getRowArray();

		return $result ?: null;
	}

	public function getCategoriesGrouped(): array
	{
		$result = [
			'income' => [],
			'expense' => [],
		];

		foreach ($this->getCategories() as $category) {
			$result[$category['transaction_type']][] = $category;
		}

		return $result;
	}

	public function getCategoryById(int $idCategory, bool $allowDefault = true): ?array
	{
		$builder = $this->db->table('personal_cash_flow_category')
			->where('id_category', $idCategory)
			->where('aktif', 1)
			->groupStart()
				->where('id_user', $this->getCurrentUserId());

		if ($allowDefault) {
			$builder->orWhere('id_user IS NULL', null, false);
		}

		$builder->groupEnd();

		$result = $builder->get()->getRowArray();
		return $result ?: null;
	}

	public function getTransactionById(int $idTransaction): ?array
	{
		$result = $this->db->table('personal_cash_flow_transaction a')
			->select('a.*, c.category_name, c.color, w.wallet_name')
			->join('personal_cash_flow_category c', 'c.id_category = a.id_category', 'left')
			->join('personal_cash_flow_wallet w', 'w.id_wallet = a.id_wallet', 'left')
			->where('a.id_transaction', $idTransaction)
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->get()
			->getRowArray();

		if ($result && !empty($result['id_wallet_transfer_target'])) {
			$pair = $this->findTransferPair($result);
			$result['transaction_mode'] = 'transfer';
			$result['id_category_transfer_target'] = (int) ($pair['id_category'] ?? 0);
		} elseif ($result) {
			$result['transaction_mode'] = $result['transaction_type'];
			$result['id_category_transfer_target'] = 0;
		}

		return $result ?: null;
	}

	public function getWalletSummary(): array
	{
		$userId = $this->getCurrentUserId();
		$sql = 'SELECT
					w.id_wallet,
					w.wallet_name,
					w.wallet_type,
					w.description,
					w.initial_balance,
					COALESCE(SUM(CASE WHEN t.transaction_type = "income" AND t.id_wallet_transfer_target IS NULL THEN t.nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN t.transaction_type = "expense" AND t.id_wallet_transfer_target IS NULL THEN t.nominal ELSE 0 END), 0) AS total_expense,
					COALESCE(SUM(CASE WHEN t.transaction_type = "income" THEN t.nominal ELSE 0 END), 0) AS ledger_income,
					COALESCE(SUM(CASE WHEN t.transaction_type = "expense" THEN t.nominal ELSE 0 END), 0) AS ledger_expense
				FROM personal_cash_flow_wallet w
				LEFT JOIN personal_cash_flow_transaction t
					ON t.id_wallet = w.id_wallet
					AND t.id_user = w.id_user
					AND t.isDeleted = 0
				WHERE w.id_user = ?
					AND w.aktif = 1
				GROUP BY w.id_wallet, w.wallet_name, w.wallet_type, w.description, w.initial_balance
				ORDER BY w.wallet_name ASC';

		$rows = $this->db->query($sql, [$userId])->getResultArray();
		foreach ($rows as &$row) {
			$row['initial_balance'] = (float) $row['initial_balance'];
			$row['total_income'] = (float) $row['total_income'];
			$row['total_expense'] = (float) $row['total_expense'];
			$row['ledger_income'] = (float) $row['ledger_income'];
			$row['ledger_expense'] = (float) $row['ledger_expense'];
			$row['balance'] = $row['initial_balance'] + $row['ledger_income'] - $row['ledger_expense'];
		}

		return $rows;
	}

	public function getTransferSummary(string $period): array
	{
		[$startDate, $endDate] = $this->getPeriodRange($period);
		$sql = 'SELECT
					COUNT(*) AS total_transfer,
					COALESCE(SUM(a.nominal), 0) AS total_nominal
				FROM personal_cash_flow_transaction a
				WHERE a.id_user = ?
					AND a.isDeleted = 0
					AND a.transaction_type = "expense"
					AND a.id_wallet_transfer_target IS NOT NULL
					AND a.transaction_date >= ?
					AND a.transaction_date <= ?';

		$result = $this->db->query($sql, [$this->getCurrentUserId(), $startDate, $endDate])->getRowArray() ?: [];
		return [
			'total_transfer' => (int) ($result['total_transfer'] ?? 0),
			'total_nominal' => (float) ($result['total_nominal'] ?? 0),
		];
	}

	public function getTransferReport(string $period): array
	{
		[$startDate, $endDate] = $this->getPeriodRange($period);
		return $this->db->table('personal_cash_flow_transaction a')
			->select('a.id_transaction, a.transaction_date, a.nominal, a.description, a.notes, ws.wallet_name AS source_wallet_name, wt.wallet_name AS target_wallet_name')
			->join('personal_cash_flow_wallet ws', 'ws.id_wallet = a.id_wallet', 'left')
			->join('personal_cash_flow_wallet wt', 'wt.id_wallet = a.id_wallet_transfer_target', 'left')
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->where('a.transaction_type', 'expense')
			->where('a.id_wallet_transfer_target IS NOT NULL', null, false)
			->where('a.transaction_date >=', $startDate)
			->where('a.transaction_date <=', $endDate)
			->orderBy('a.transaction_date', 'DESC')
			->orderBy('a.id_transaction', 'DESC')
			->get()
			->getResultArray();
	}

	public function getSummary(string $period): array
	{
		[$startDate, $endDate] = $this->getPeriodRange($period);
		$userId = $this->getCurrentUserId();

		$sql = 'SELECT
					COALESCE(SUM(CASE WHEN transaction_type = "income" AND ' . $this->getNonTransferAggregateSqlCondition() . ' THEN nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN transaction_type = "expense" AND ' . $this->getNonTransferAggregateSqlCondition() . ' THEN nominal ELSE 0 END), 0) AS total_expense,
					COUNT(*) AS total_transaction
				FROM personal_cash_flow_transaction
				WHERE id_user = ?
					AND isDeleted = 0
					AND transaction_date >= ?
					AND transaction_date <= ?';

		$summary = $this->db->query($sql, [$userId, $startDate, $endDate])->getRowArray() ?: [];
		$summary['total_income'] = (float) ($summary['total_income'] ?? 0);
		$summary['total_expense'] = (float) ($summary['total_expense'] ?? 0);
		$summary['balance'] = $summary['total_income'] - $summary['total_expense'];
		$summary['total_transaction'] = (int) ($summary['total_transaction'] ?? 0);

		return $summary;
	}

	public function getOverallSummary(): array
	{
		$userId = $this->getCurrentUserId();

		$sql = 'SELECT
					COALESCE(SUM(CASE WHEN transaction_type = "income" AND ' . $this->getNonTransferAggregateSqlCondition() . ' THEN nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN transaction_type = "expense" AND ' . $this->getNonTransferAggregateSqlCondition() . ' THEN nominal ELSE 0 END), 0) AS total_expense,
					COUNT(*) AS total_transaction
				FROM personal_cash_flow_transaction
				WHERE id_user = ?
					AND isDeleted = 0';

		$summary = $this->db->query($sql, [$userId])->getRowArray() ?: [];
		$summary['total_income'] = (float) ($summary['total_income'] ?? 0);
		$summary['total_expense'] = (float) ($summary['total_expense'] ?? 0);
		$summary['balance'] = $summary['total_income'] - $summary['total_expense'];
		$summary['total_transaction'] = (int) ($summary['total_transaction'] ?? 0);

		return $summary;
	}

	public function getMonthlyChartData(string $period, int $monthCount = 6): array
	{
		$monthCount = max(1, $monthCount);
		$endMonth = date('Y-m-01', strtotime($period . '-01'));
		$startMonth = date('Y-m-01', strtotime('-' . ($monthCount - 1) . ' months', strtotime($endMonth)));
		$userId = $this->getCurrentUserId();

		$sql = 'SELECT
					DATE_FORMAT(transaction_date, "%Y-%m") AS month_key,
					COALESCE(SUM(CASE WHEN transaction_type = "income" AND ' . $this->getNonTransferAggregateSqlCondition() . ' THEN nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN transaction_type = "expense" AND ' . $this->getNonTransferAggregateSqlCondition() . ' THEN nominal ELSE 0 END), 0) AS total_expense
				FROM personal_cash_flow_transaction
				WHERE id_user = ?
					AND isDeleted = 0
					AND transaction_date >= ?
					AND transaction_date <= ?
				GROUP BY DATE_FORMAT(transaction_date, "%Y-%m")
				ORDER BY month_key ASC';

		$rows = $this->db->query($sql, [$userId, $startMonth, date('Y-m-t', strtotime($endMonth))])->getResultArray();
		$indexed = [];
		foreach ($rows as $row) {
			$indexed[$row['month_key']] = $row;
		}

		$labels = [];
		$income = [];
		$expense = [];
		for ($i = 0; $i < $monthCount; $i++) {
			$currentMonth = date('Y-m', strtotime('+' . $i . ' months', strtotime($startMonth)));
			$labels[] = date('M Y', strtotime($currentMonth . '-01'));
			$income[] = (float) ($indexed[$currentMonth]['total_income'] ?? 0);
			$expense[] = (float) ($indexed[$currentMonth]['total_expense'] ?? 0);
		}

		return [
			'labels' => $labels,
			'income' => $income,
			'expense' => $expense,
		];
	}

	public function getExpenseCategoryChart(string $period): array
	{
		[$startDate, $endDate] = $this->getPeriodRange($period);
		$userId = $this->getCurrentUserId();

		$sql = 'SELECT
					c.category_name,
					COALESCE(c.color, "#6c757d") AS color,
					COALESCE(SUM(a.nominal), 0) AS total_nominal
				FROM personal_cash_flow_transaction a
				LEFT JOIN personal_cash_flow_category c ON c.id_category = a.id_category
				WHERE a.id_user = ?
					AND a.isDeleted = 0
					AND a.transaction_type = "expense"
					AND a.transaction_date >= ?
					AND a.transaction_date <= ?
				GROUP BY a.id_category, c.category_name, c.color
				ORDER BY total_nominal DESC, c.category_name ASC';

		$rows = $this->db->query($sql, [$userId, $startDate, $endDate])->getResultArray();
		$labels = [];
		$totals = [];
		$colors = [];

		foreach ($rows as $row) {
			if ($this->isTransferCategoryName((string) ($row['category_name'] ?? ''))) {
				continue;
			}
			$labels[] = (string) $row['category_name'];
			$totals[] = (float) $row['total_nominal'];
			$colors[] = (string) ($row['color'] ?: '#6c757d');
		}

		return [
			'labels' => $labels,
			'totals' => $totals,
			'colors' => $colors,
		];
	}

	public function getTransactions(string $period, string $type = '', string $keyword = ''): array
	{
		$builder = $this->getTransactionListBuilder($period, $type);

		if ($keyword !== '') {
			$builder->groupStart()
				->like('a.description', $keyword)
				->orLike('a.notes', $keyword)
				->orLike('c.category_name', $keyword)
			->groupEnd();
		}

		return $builder
			->orderBy('a.transaction_date', 'DESC')
			->orderBy('a.id_transaction', 'DESC')
			->get()
			->getResultArray();
	}

	public function countTransactions(string $period, string $type = ''): int
	{
		return $this->getTransactionListBuilder($period, $type)->countAllResults();
	}

	public function getTransactionListDataTable(string $period, string $type = ''): array
	{
		$countBuilder = $this->getTransactionListBuilder($period, $type);
		$this->applyTransactionDatatableSearch($countBuilder);
		$totalFiltered = $countBuilder->countAllResults();

		$dataBuilder = $this->getTransactionListBuilder($period, $type);
		$this->applyTransactionDatatableSearch($dataBuilder);
		$this->applyTransactionDatatableOrderAndLimit($dataBuilder);
		$data = $dataBuilder->get()->getResultArray();

		return [
			'data' => $data,
			'total_filtered' => $totalFiltered,
		];
	}

	public function getTransactionListPage(string $period, string $type = '', int $page = 1, int $perPage = 8, string $keyword = '', string $descriptionFilter = '', string $categoryFilter = ''): array
	{
		$page = max(1, $page);
		$perPage = max(1, min(20, $perPage));
		$offset = ($page - 1) * $perPage;

		$countBuilder = $this->getTransactionListBuilder($period, $type);
		$this->applyTransactionListFilters($countBuilder, $descriptionFilter, $categoryFilter, $keyword);
		$totalFiltered = $countBuilder->countAllResults();

		$dataBuilder = $this->getTransactionListBuilder($period, $type);
		$this->applyTransactionListFilters($dataBuilder, $descriptionFilter, $categoryFilter, $keyword);
		$data = $dataBuilder
			->orderBy('a.transaction_date', 'DESC')
			->orderBy('a.id_transaction', 'DESC')
			->limit($perPage, $offset)
			->get()
			->getResultArray();

		return [
			'data' => $data,
			'page' => $page,
			'per_page' => $perPage,
			'total_filtered' => $totalFiltered,
			'has_more' => ($offset + count($data)) < $totalFiltered,
		];
	}

	public function getTransactionsByIds(array $ids): array
	{
		$ids = $this->normalizeIntegerList($ids);
		if (!$ids) {
			return [];
		}

		return $this->db->table('personal_cash_flow_transaction a')
			->select('a.*, c.category_name')
			->join('personal_cash_flow_category c', 'c.id_category = a.id_category', 'left')
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->whereIn('a.id_transaction', $ids)
			->get()
			->getResultArray();
	}

	private function validateTransactionPayload(int $idTransaction = 0): array
	{
		$transactionMode = (string) ($this->request->getPost('transaction_type') ?? '');
		$baseTransactionType = $transactionMode === 'transfer' ? 'expense' : $transactionMode;

		$validation = \Config\Services::validation();
		$validation->setRule('transaction_type', 'Jenis Transaksi', 'required|in_list[income,expense,transfer]');
		$validation->setRule('transaction_date', 'Tanggal Transaksi', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
		$validation->setRule('id_wallet', 'Wallet', 'required|numeric');
		$validation->setRule('nominal', 'Nominal', 'required');
		$validation->setRule('description', 'Deskripsi', 'required|max_length[255]');
		$validation->setRule('notes', 'Catatan', 'permit_empty');
		if ($transactionMode !== 'transfer') {
			$validation->setRule('id_category', 'Kategori', 'required|numeric');
		}
		$validation->withRequest($this->request)->run();

		$errors = $validation->getErrors();
		if ($errors) {
			return $errors;
		}

		if ($transactionMode !== 'transfer') {
			$category = $this->getCategoryById((int) $this->request->getPost('id_category'));
			if (!$category) {
				return ['id_category' => 'Kategori tidak ditemukan atau tidak bisa diakses'];
			}

			if ($category['transaction_type'] !== $baseTransactionType) {
				return ['transaction_type' => 'Jenis transaksi harus sesuai dengan kategori yang dipilih'];
			}
		}

		$wallet = $this->getWalletById((int) $this->request->getPost('id_wallet'));
		if (!$wallet) {
			return ['id_wallet' => 'Wallet tidak ditemukan atau tidak bisa diakses'];
		}

		if ($transactionMode === 'transfer') {
			$targetWallet = $this->getWalletById((int) $this->request->getPost('id_wallet_transfer_target'));
			if (!$targetWallet) {
				return ['id_wallet_transfer_target' => 'Wallet tujuan transfer tidak ditemukan'];
			}

			if ((int) $wallet['id_wallet'] === (int) $targetWallet['id_wallet']) {
				return ['id_wallet_transfer_target' => 'Wallet tujuan transfer harus berbeda'];
			}
		}

		if ($idTransaction > 0 && !$this->getTransactionById($idTransaction)) {
			return ['id' => 'Transaksi tidak ditemukan'];
		}

		return [];
	}

	public function saveTransaction(int $idTransaction = 0): array
	{
		$formErrors = $this->validateTransactionPayload($idTransaction);
		if ($formErrors) {
			return [
				'status' => 'error',
				'message' => 'Data transaksi belum valid',
				'form_errors' => $formErrors,
			];
		}

		$now = date('Y-m-d H:i:s');
		$transactionMode = (string) $this->request->getPost('transaction_type');
		$data = [
			'id_user' => $this->getCurrentUserId(),
			'id_wallet' => (int) $this->request->getPost('id_wallet'),
			'transaction_date' => (string) $this->request->getPost('transaction_date'),
			'nominal' => $this->parseNominalInput($this->request->getPost('nominal')),
			'description' => trim((string) $this->request->getPost('description')),
			'notes' => trim((string) $this->request->getPost('notes')),
			'id_user_update' => $this->getCurrentUserId(),
			'updated_at' => $now,
		];

		if ($data['nominal'] <= 0) {
			return [
				'status' => 'error',
				'message' => 'Data transaksi belum valid',
				'form_errors' => ['nominal' => 'Nominal harus berupa angka dan lebih besar dari 0'],
			];
		}

		$this->db->transBegin();

		if ($transactionMode === 'transfer') {
			$sourceData = array_merge($data, [
				'id_category' => $this->getTransferCategoryId('expense'),
				'transaction_type' => 'expense',
				'id_wallet_transfer_target' => (int) $this->request->getPost('id_wallet_transfer_target'),
			]);
			$targetData = array_merge($data, [
				'id_category' => $this->getTransferCategoryId('income'),
				'id_wallet' => (int) $this->request->getPost('id_wallet_transfer_target'),
				'transaction_type' => 'income',
				'id_wallet_transfer_target' => (int) $this->request->getPost('id_wallet'),
			]);

			if ($idTransaction > 0) {
				$current = $this->getTransactionById($idTransaction);
				if (!$current) {
					$this->db->transRollback();
					return ['status' => 'error', 'message' => 'Transaksi tidak ditemukan'];
				}

				$sourceTransaction = $current['transaction_mode'] === 'transfer' ? $current : null;
				if (!$sourceTransaction || $sourceTransaction['transaction_type'] !== 'expense') {
					$sourceTransaction = $this->findTransferPair($current);
				}
				$targetTransaction = $sourceTransaction ? $this->findTransferPair($sourceTransaction) : null;

				if (!$sourceTransaction || !$targetTransaction) {
					$this->db->transRollback();
					return ['status' => 'error', 'message' => 'Pasangan transfer tidak ditemukan'];
				}

				$this->db->table('personal_cash_flow_transaction')
					->where('id_transaction', $sourceTransaction['id_transaction'])
					->where('id_user', $this->getCurrentUserId())
					->update($sourceData);

				$this->db->table('personal_cash_flow_transaction')
					->where('id_transaction', $targetTransaction['id_transaction'])
					->where('id_user', $this->getCurrentUserId())
					->update($targetData);
			} else {
				$sourceData['id_user_input'] = $this->getCurrentUserId();
				$sourceData['created_at'] = $now;
				$targetData['id_user_input'] = $this->getCurrentUserId();
				$targetData['created_at'] = $now;

				$this->db->table('personal_cash_flow_transaction')->insert($sourceData);
				$this->db->table('personal_cash_flow_transaction')->insert($targetData);
			}

			if (!$this->db->transStatus()) {
				$this->db->transRollback();
				return ['status' => 'error', 'message' => 'Transfer gagal disimpan'];
			}

			$this->db->transCommit();
			return [
				'status' => 'ok',
				'message' => $idTransaction > 0 ? 'Transfer berhasil diperbarui' : 'Transfer berhasil ditambahkan',
			];
		}

		$data['id_category'] = (int) $this->request->getPost('id_category');
		$data['transaction_type'] = $transactionMode;
		$data['id_wallet_transfer_target'] = null;
		if ($idTransaction > 0) {
			$current = $this->getTransactionById($idTransaction);
			if ($current && !empty($current['id_wallet_transfer_target'])) {
				$pair = $this->findTransferPair($current);
				if ($pair) {
					$this->db->table('personal_cash_flow_transaction')
						->where('id_transaction', (int) $pair['id_transaction'])
						->where('id_user', $this->getCurrentUserId())
						->update([
							'isDeleted' => 1,
							'id_user_update' => $this->getCurrentUserId(),
							'updated_at' => $now,
						]);
				}
			}
		}

		if ($idTransaction > 0) {
			$this->db->table('personal_cash_flow_transaction')
				->where('id_transaction', $idTransaction)
				->where('id_user', $this->getCurrentUserId())
				->update($data);

			if (!$this->db->transStatus()) {
				$this->db->transRollback();
				return ['status' => 'error', 'message' => 'Transaksi gagal diperbarui'];
			}
			$this->db->transCommit();
			return ['status' => 'ok', 'message' => 'Transaksi berhasil diperbarui'];
		}

		$data['id_user_input'] = $this->getCurrentUserId();
		$data['created_at'] = $now;

		$this->db->table('personal_cash_flow_transaction')->insert($data);
		if (!$this->db->transStatus()) {
			$this->db->transRollback();
			return ['status' => 'error', 'message' => 'Transaksi gagal ditambahkan'];
		}
		$this->db->transCommit();
		return ['status' => 'ok', 'message' => 'Transaksi berhasil ditambahkan'];
	}

	public function deleteTransaction(int $idTransaction): array
	{
		$transaction = $this->getTransactionById($idTransaction);
		if (!$transaction) {
			return [
				'status' => 'error',
				'message' => 'Transaksi tidak ditemukan',
			];
		}

		$this->db->transBegin();
		$updateData = [
			'isDeleted' => 1,
			'id_user_update' => $this->getCurrentUserId(),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$this->db->table('personal_cash_flow_transaction')
			->where('id_transaction', $idTransaction)
			->where('id_user', $this->getCurrentUserId())
			->update($updateData);

		if (!empty($transaction['id_wallet_transfer_target'])) {
			$pair = $this->findTransferPair($transaction);
			if ($pair) {
				$this->db->table('personal_cash_flow_transaction')
					->where('id_transaction', (int) $pair['id_transaction'])
					->where('id_user', $this->getCurrentUserId())
					->update($updateData);
			}
		}

		if (!$this->db->transStatus()) {
			$this->db->transRollback();
			return ['status' => 'error', 'message' => 'Transaksi gagal dihapus'];
		}
		$this->db->transCommit();

		return [
			'status' => 'ok',
			'message' => 'Transaksi berhasil dihapus',
		];
	}

	public function bulkUpdateCategory(): array
	{
		$selectedIds = $this->normalizeSelectedTransactionIds();
		if (!$selectedIds) {
			return [
				'status' => 'error',
				'message' => 'Pilih minimal satu transaksi',
			];
		}

		$idCategory = (int) ($this->request->getPost('id_category') ?? 0);
		$category = $this->getCategoryById($idCategory);
		if (!$category) {
			return [
				'status' => 'error',
				'message' => 'Kategori tujuan tidak ditemukan',
			];
		}

		$transactions = $this->getTransactionsByIds($selectedIds);
		if (count($transactions) !== count($selectedIds)) {
			return [
				'status' => 'error',
				'message' => 'Ada transaksi yang tidak ditemukan atau tidak bisa diakses',
			];
		}

		$transactionTypes = array_values(array_unique(array_column($transactions, 'transaction_type')));
		if (count($transactionTypes) > 1) {
			return [
				'status' => 'error',
				'message' => 'Bulk update kategori hanya bisa untuk transaksi dengan jenis yang sama',
			];
		}

		if (($transactionTypes[0] ?? '') !== $category['transaction_type']) {
			return [
				'status' => 'error',
				'message' => 'Jenis kategori tujuan harus sesuai dengan jenis transaksi terpilih',
			];
		}

		$this->db->table('personal_cash_flow_transaction')
			->where('id_user', $this->getCurrentUserId())
			->where('isDeleted', 0)
			->whereIn('id_transaction', $selectedIds)
			->update([
				'id_category' => $idCategory,
				'id_user_update' => $this->getCurrentUserId(),
				'updated_at' => date('Y-m-d H:i:s'),
			]);

		return [
			'status' => 'ok',
			'message' => 'Kategori transaksi berhasil diperbarui',
		];
	}

	private function validateCategoryPayload(int $idCategory = 0): array
	{
		$validation = \Config\Services::validation();
		$validation->setRule('transaction_type', 'Jenis Kategori', 'required|in_list[income,expense]');
		$validation->setRule('category_name', 'Nama Kategori', 'required|max_length[100]');
		$validation->setRule('description', 'Deskripsi', 'permit_empty|max_length[255]');
		$validation->setRule('color', 'Warna', 'permit_empty|regex_match[/^#[A-Fa-f0-9]{6}$/]');
		$validation->withRequest($this->request)->run();

		$errors = $validation->getErrors();
		if ($errors) {
			return $errors;
		}

		$builder = $this->db->table('personal_cash_flow_category')
			->where('transaction_type', (string) $this->request->getPost('transaction_type'))
			->where('category_name', trim((string) $this->request->getPost('category_name')))
			->where('aktif', 1)
			->groupStart()
				->where('id_user', $this->getCurrentUserId())
				->orWhere('id_user IS NULL', null, false)
			->groupEnd();

		if ($idCategory > 0) {
			$builder->where('id_category !=', $idCategory);
		}

		if ($builder->countAllResults() > 0) {
			return ['category_name' => 'Nama kategori sudah digunakan'];
		}

		if ($this->isTransferCategoryName((string) $this->request->getPost('category_name'))) {
			return ['category_name' => 'Nama kategori Transfer digunakan khusus untuk sistem'];
		}

		return [];
	}

	public function saveCategory(int $idCategory = 0): array
	{
		$formErrors = $this->validateCategoryPayload($idCategory);
		if ($formErrors) {
			return [
				'status' => 'error',
				'message' => 'Data kategori belum valid',
				'form_errors' => $formErrors,
			];
		}

		$data = [
			'id_user' => $this->getCurrentUserId(),
			'transaction_type' => (string) $this->request->getPost('transaction_type'),
			'category_name' => trim((string) $this->request->getPost('category_name')),
			'description' => trim((string) $this->request->getPost('description')),
			'color' => (string) ($this->request->getPost('color') ?: '#6c757d'),
			'updated_at' => date('Y-m-d H:i:s'),
		];

		if ($idCategory > 0) {
			$category = $this->getCategoryById($idCategory, false);
			if (!$category || (int) $category['is_default'] === 1) {
				return [
					'status' => 'error',
					'message' => 'Kategori tidak ditemukan',
				];
			}

			$this->db->table('personal_cash_flow_category')
				->where('id_category', $idCategory)
				->where('id_user', $this->getCurrentUserId())
				->update($data);

			return [
				'status' => 'ok',
				'message' => 'Kategori berhasil diperbarui',
			];
		}

		$data['created_at'] = date('Y-m-d H:i:s');

		$this->db->table('personal_cash_flow_category')->insert($data);

		return [
			'status' => 'ok',
			'message' => 'Kategori berhasil ditambahkan',
		];
	}

	public function deleteCategory(int $idCategory): array
	{
		$category = $this->getCategoryById($idCategory, false);
		if (!$category || (int) $category['is_default'] === 1) {
			return [
				'status' => 'error',
				'message' => 'Kategori tidak ditemukan',
			];
		}

		$transactionCount = $this->db->table('personal_cash_flow_transaction')
			->where('id_category', $idCategory)
			->where('id_user', $this->getCurrentUserId())
			->where('isDeleted', 0)
			->countAllResults();

		if ($transactionCount > 0) {
			return [
				'status' => 'error',
				'message' => 'Kategori tidak bisa dihapus karena masih dipakai transaksi',
			];
		}

		$this->db->table('personal_cash_flow_category')
			->where('id_category', $idCategory)
			->where('id_user', $this->getCurrentUserId())
			->delete();

		return [
			'status' => 'ok',
			'message' => 'Kategori berhasil dihapus',
		];
	}

	private function validateWalletPayload(int $idWallet = 0): array
	{
		$validation = \Config\Services::validation();
		$validation->setRule('wallet_name', 'Nama Wallet', 'required|max_length[100]');
		$validation->setRule('wallet_type', 'Tipe Wallet', 'required|in_list[cash,bank,digital,savings,other]');
		$validation->setRule('description', 'Deskripsi', 'permit_empty|max_length[255]');
		$validation->setRule('initial_balance', 'Saldo Awal', 'required');
		$validation->withRequest($this->request)->run();

		$errors = $validation->getErrors();
		if ($errors) {
			return $errors;
		}

		$builder = $this->db->table('personal_cash_flow_wallet')
			->where('id_user', $this->getCurrentUserId())
			->where('aktif', 1)
			->where('wallet_name', trim((string) $this->request->getPost('wallet_name')));

		if ($idWallet > 0) {
			$builder->where('id_wallet !=', $idWallet);
		}

		if ($builder->countAllResults() > 0) {
			return ['wallet_name' => 'Nama wallet sudah digunakan'];
		}

		return [];
	}

	public function saveWallet(int $idWallet = 0): array
	{
		$formErrors = $this->validateWalletPayload($idWallet);
		if ($formErrors) {
			return [
				'status' => 'error',
				'message' => 'Data wallet belum valid',
				'form_errors' => $formErrors,
			];
		}

		$data = [
			'id_user' => $this->getCurrentUserId(),
			'wallet_name' => trim((string) $this->request->getPost('wallet_name')),
			'wallet_type' => (string) $this->request->getPost('wallet_type'),
			'description' => trim((string) $this->request->getPost('description')),
			'initial_balance' => $this->parseNominalInput($this->request->getPost('initial_balance')),
			'aktif' => 1,
			'updated_at' => date('Y-m-d H:i:s'),
		];

		if ($idWallet > 0) {
			$wallet = $this->getWalletById($idWallet);
			if (!$wallet) {
				return ['status' => 'error', 'message' => 'Wallet tidak ditemukan'];
			}

			$this->db->table('personal_cash_flow_wallet')
				->where('id_wallet', $idWallet)
				->where('id_user', $this->getCurrentUserId())
				->update($data);

			return ['status' => 'ok', 'message' => 'Wallet berhasil diperbarui'];
		}

		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->table('personal_cash_flow_wallet')->insert($data);
		return ['status' => 'ok', 'message' => 'Wallet berhasil ditambahkan'];
	}

	public function deleteWallet(int $idWallet): array
	{
		$wallet = $this->getWalletById($idWallet);
		if (!$wallet) {
			return ['status' => 'error', 'message' => 'Wallet tidak ditemukan'];
		}

		$transactionCount = $this->db->table('personal_cash_flow_transaction')
			->where('id_user', $this->getCurrentUserId())
			->where('isDeleted', 0)
			->groupStart()
				->where('id_wallet', $idWallet)
				->orWhere('id_wallet_transfer_target', $idWallet)
			->groupEnd()
			->countAllResults();

		if ($transactionCount > 0) {
			return ['status' => 'error', 'message' => 'Wallet tidak bisa dihapus karena masih dipakai transaksi'];
		}

		$this->db->table('personal_cash_flow_wallet')
			->where('id_wallet', $idWallet)
			->where('id_user', $this->getCurrentUserId())
			->update([
				'aktif' => 0,
				'updated_at' => date('Y-m-d H:i:s'),
			]);

		return ['status' => 'ok', 'message' => 'Wallet berhasil dihapus'];
	}
}
