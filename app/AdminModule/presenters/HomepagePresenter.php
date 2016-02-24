<?php
	namespace AdminModule;

	class HomepagePresenter extends BasePresenter {
		public $id;
		public $urlID;
		public $orders;
		public $products;
		public $searchLog;
		public $loginLog;

		public function actionDefault () {
			$this->urlID = 0;
			$this->orders = $this->ordersModel->getAllFinished()->order("date DESC")->limit(5);
			$this->products = $this->productsModel->getAll()->order("date DESC")->limit(5);
			$this->searchLog = $this->logsModel->getSearchLogWithCounts();
			$this->loginLog = $this->logsModel->getLoginLog()->order("date DESC")->limit(5);
		}

		public function renderDefault () {
			$this->template->orders = $this->orders;
			$this->template->products = $this->products;
			$this->template->searchLog = $this->searchLog;
			$this->template->loginLog = $this->loginLog;
		}
	}
?>