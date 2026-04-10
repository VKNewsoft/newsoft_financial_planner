<?php
/**
 * @author VKNewsoft - Newsoft Developer
 * @year 2026
 */

namespace App\Modules\PersonalCashFlow\Models;

class PersonalCashFlowModel extends \App\Modules\Common\Models\BaseModel
{
	private function getCurrentUserId(): int
	{
		return (int) ($this->session->get('user')['id_user'] ?? 0);
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
			->select('a.*, c.category_name, c.color')
			->join('personal_cash_flow_category c', 'c.id_category = a.id_category', 'left')
			->where('a.id_transaction', $idTransaction)
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->get()
			->getRowArray();

		return $result ?: null;
	}

	public function getSummary(string $period): array
	{
		[$startDate, $endDate] = $this->getPeriodRange($period);
		$userId = $this->getCurrentUserId();

		$sql = 'SELECT
					COALESCE(SUM(CASE WHEN transaction_type = "income" THEN nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN transaction_type = "expense" THEN nominal ELSE 0 END), 0) AS total_expense,
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
					COALESCE(SUM(CASE WHEN transaction_type = "income" THEN nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN transaction_type = "expense" THEN nominal ELSE 0 END), 0) AS total_expense,
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
					COALESCE(SUM(CASE WHEN transaction_type = "income" THEN nominal ELSE 0 END), 0) AS total_income,
					COALESCE(SUM(CASE WHEN transaction_type = "expense" THEN nominal ELSE 0 END), 0) AS total_expense
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
		[$startDate, $endDate] = $this->getPeriodRange($period);

		$builder = $this->db->table('personal_cash_flow_transaction a')
			->select('a.*, c.category_name, c.color, c.is_default')
			->join('personal_cash_flow_category c', 'c.id_category = a.id_category', 'left')
			->where('a.id_user', $this->getCurrentUserId())
			->where('a.isDeleted', 0)
			->where('a.transaction_date >=', $startDate)
			->where('a.transaction_date <=', $endDate);

		if ($type) {
			$builder->where('a.transaction_type', $type);
		}

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
		$validation = \Config\Services::validation();
		$validation->setRule('transaction_type', 'Jenis Transaksi', 'required|in_list[income,expense]');
		$validation->setRule('transaction_date', 'Tanggal Transaksi', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
		$validation->setRule('id_category', 'Kategori', 'required|numeric');
		$validation->setRule('nominal', 'Nominal', 'required');
		$validation->setRule('description', 'Deskripsi', 'required|max_length[255]');
		$validation->setRule('notes', 'Catatan', 'permit_empty');
		$validation->withRequest($this->request)->run();

		$errors = $validation->getErrors();
		if ($errors) {
			return $errors;
		}

		$category = $this->getCategoryById((int) $this->request->getPost('id_category'));
		if (!$category) {
			return ['id_category' => 'Kategori tidak ditemukan atau tidak bisa diakses'];
		}

		if ($category['transaction_type'] !== $this->request->getPost('transaction_type')) {
			return ['transaction_type' => 'Jenis transaksi harus sesuai dengan kategori yang dipilih'];
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
		$data = [
			'id_user' => $this->getCurrentUserId(),
			'id_category' => (int) $this->request->getPost('id_category'),
			'transaction_type' => (string) $this->request->getPost('transaction_type'),
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

		if ($idTransaction > 0) {
			$this->db->table('personal_cash_flow_transaction')
				->where('id_transaction', $idTransaction)
				->where('id_user', $this->getCurrentUserId())
				->update($data);

			return [
				'status' => 'ok',
				'message' => 'Transaksi berhasil diperbarui',
			];
		}

		$data['id_user_input'] = $this->getCurrentUserId();
		$data['created_at'] = $now;

		$this->db->table('personal_cash_flow_transaction')->insert($data);

		return [
			'status' => 'ok',
			'message' => 'Transaksi berhasil ditambahkan',
		];
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

		$this->db->table('personal_cash_flow_transaction')
			->where('id_transaction', $idTransaction)
			->where('id_user', $this->getCurrentUserId())
			->update([
				'isDeleted' => 1,
				'id_user_update' => $this->getCurrentUserId(),
				'updated_at' => date('Y-m-d H:i:s'),
			]);

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
}
