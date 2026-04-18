<?php

namespace App\Modules\UpdateStrukturDb\Controllers;

use App\Modules\UpdateStrukturDb\Models\UpdateStrukturDbModel;

class UpdateStrukturDb extends \App\Modules\Common\Controllers\BaseController
{
	protected $model;

	public function __construct()
	{
		parent::__construct();
		$resultPageVersion = '?v=' . @filemtime(ROOTPATH . 'public/themes/modern/css/result-page.css');
		$this->model = new UpdateStrukturDbModel();
		$this->data['site_title'] = 'Update Struktur DB';
		$this->addStyle($this->config->baseURL . 'public/themes/modern/css/result-page.css' . $resultPageVersion);
	}

	public function index()
	{
		$this->hasPermission('read_all');
		$data = $this->data;
		$data['msg'] = $this->session->getFlashdata('msg') ?: [];

		if ($this->request->getPost('execute_sync')) {
			$this->hasPermission('update_all');
			$result = $this->model->executeSync();
			$this->session->setFlashdata('msg', $result);
			return redirect()->to(current_url());
		}

		$data['preview'] = $this->model->getPreview();
		$this->view('index.php', $data);
	}
}
