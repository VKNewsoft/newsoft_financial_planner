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
		$this->model = new PersonalCashFlowModel();
		$this->data['site_title'] = 'Personal Cash Flow';
		$this->addStyle($this->config->baseURL . 'public/themes/modern/css/result-page.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/personal-cash-flow.js');
	}

	public function index()
	{
		$this->hasPermissionPrefix('read');

		$period = $this->model->getSelectedPeriod();
		$type = $this->model->getSelectedType();
		$keyword = $this->model->getSelectedKeyword();

		$this->data['title'] = 'Personal Cash Flow';
		$this->data['period'] = $period;
		$this->data['type'] = $type;
		$this->data['keyword'] = $keyword;
		$this->data['overallSummary'] = $this->model->getOverallSummary();
		$this->data['summary'] = $this->model->getSummary($period);
		$this->data['chart'] = $this->model->getMonthlyChartData($period, 6);
		$this->data['expenseCategoryChart'] = $this->model->getExpenseCategoryChart($period);
		$this->data['transactions'] = $this->model->getTransactions($period, $type, $keyword);
		$this->data['categoriesGrouped'] = $this->model->getCategoriesGrouped();
		$this->data['msg'] = $this->session->getFlashdata('msg') ?: [];

		$this->view('index.php', $this->data);
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
}
