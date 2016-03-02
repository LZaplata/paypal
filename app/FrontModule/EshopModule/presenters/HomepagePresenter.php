<?php
	namespace FrontEshopModule;

	class HomepagePresenter extends BasePresenter {
		public $products;
		public $paginator;
		public $cols;

		public function actionDefault () {
			$this->cols = array(1 => 12, 2 => 6, 3 => 4, 4 => 3, 6 => 2);

			$vp = new \VisualPaginator($this, 'paginator');
			$this->paginator = $vp->getPaginator();
			$this->paginator->page = $this->presenter->getParameter('page');

			if ($q = $this->getParameter('q')) {
				$this->products = $this->model->getProducts()->where('name LIKE ? OR description LIKE ? OR text LIKE ? OR code LIKE ?', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%', '%'.$q.'%')->where('visibility', 1)->where('trash', 0)->where('pid IS NULL');
			}
			else {
//				$this->products = $this->model->getProducts()->where('pid IS NULL')->where('highlight', 1)->where('visibility', 1)->where('trash', 0);
			}

			$this->paginator->itemsPerPage = $this->module->lmt;
			$this->paginator->itemCount = count($this->products);
//			$this->products->page($this->paginator->page, $this->paginator->itemsPerPage);
		}

		public function renderDefault () {
			$page = $this->model->getPagesModules()->select('pages.keywords'.$this->lang.' AS keywords, pages.title'.$this->lang.' AS title, pages.description'.$this->lang.' AS description')->where('modules_id', 3)->fetch();

			$this->template->keywords = $page->keywords;
			$this->template->title = $page->title;
			$this->template->title_addition = $this->vendorSettings->title_editors;
			$this->template->desc = $page->description;
//			$this->template->products = $this->products;
			$this->template->settings = $this->settings;
			$this->template->related = true;
			$this->template->currency = $this->currency == 'czk' ? $this->context->parameters['currency'] : $this->currency;
			$this->template->decimals = $this->currency == 'czk' ? 0 : 2;
			$this->template->homepage = false;
			$this->template->cols = $this->cols[$this->module->cols];
			$this->template->clearfix = $this->module->cols;
			$this->template->isSearch = isset($_GET["q"]);
			$this->template->categories = $this->model->getCategories()->where("pid", 10);
		}

		public function getCategoryProducts ($cid) {
			return $this->products = $this->model->getProductsCategories()->select('*, products.*')->where('categories_id', $cid)->where('products.pid IS NULL')->where('products.trash', 0)->where('visibility', 1)/*->order('position ASC')*/;
		}
	}