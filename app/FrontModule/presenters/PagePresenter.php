<?php
	namespace FrontModule;

	use Nette\Utils\Finder;
	use Nette\Utils\Json;
	use Nette\Utils\Strings;
	use WebLoader\Filter\LessFilter;

	use WebLoader\Nette\JavaScriptLoader;

	use WebLoader\Nette\CssLoader;

	use WebLoader\Filter\VariablesFilter;

	use WebLoader\Compiler;

	use WebLoader\FileCollection;

	use FrontEshopModule\Top;

	use FrontEshopModule\Cart;

	use FrontEshopModule\CategoriesPresenter;

	use Nette\Utils\Html;

	use NetteTranslator\Gettext;

	use AdminModule\SignPresenter;

	use EshopModule\ProductsPresenter;
use Nette\Application\UI\Multiplier;
use FrontEshopModule\AddToCart;
use FrontEshopModule\Userbar;
			
	class PagePresenter extends SignPresenter {
		/** @persistent */
		public $lang;
		/** @persistent */
		public $url;
		public $pages;
		public $page;
		public $actualUrl = true;
		/** @persistent */
		public $article;
		/** @persistent */
		public $aid;
		/** @persistent */
		public $path;
		/** @persistent */
		public $category;
		/** @persistent */
		public $cid;
		public $tag;
		public $parameters;
		public $pid;
		/** @var \WebLoader\Nette\LoaderFactory @inject */
		public $webLoader;
		
		public function startup() {
			parent::startup();
			$this->parameters = $this->params;
			
			$this->lang = $this->request->parameters['lang'];
			
// 			$this->article = isset($this->params['aid']) ? $this->params['aid'] : null;
// 			$this->category = isset($this->params['cid']) ? $this->params['cid'] : null;
			$this->tag = isset($this->params['tid']) ? $this->params['tid'] : null;
// 			$this->product = isset($this->params['pid']) ? $this->params['pid'] : null;

			foreach (Finder::find("*.json")->in(WWW_DIR) as $file) {
				$json = Json::decode(file_get_contents($file), Json::FORCE_ARRAY);

				foreach ($json as $item) {
					$article = array();
					$article["name"] = $article["title"] = $article["keywords"] = $article["meta_description"] = $item["Title"];
					$article["url"] = Strings::webalize($article["name"]);
					$article["text"] = $item["Content"];
					$article["lng"] = $item["lng"];
					$article["sections_id"] = 8;
					$article["lat"] = $item["lat"];
//					$article["pid"] = $art->id;
					$article["galleries_id"] = $this->model->getGalleries()->insert(array());
					$article["filestores_id"] = $this->model->getFilestores()->insert(array());
					$article["date"] = $article["created"] = date("Y-m-d H:i:s");

					$this->model->getArticles()->insert($article);
				}
			}
		}
		
		public function actionDefault() {
			$this->getPagesArray();
			$this->getPathError();
		}
		
		public function renderDefault() {
			$this->getPageLayout();
			
			if ($this->page) {
				$article = $this->aid ? $this->getComponent('articles')->getArticle() : null; 
				$category = $this->cid ? $this->getComponent('articles')->getCategory() : null; 
				
				$this->template->title = $this->aid ? $article->title : ($category ? $category->title : $this->page->title);
				$this->template->title_addition = $this->aid ? $this->vendorSettings->title_articles : ($category ? $this->vendorSettings->title_articles_categories : $this->vendorSettings->title_editors);
				$this->template->keywords = $this->aid ? $article->keywords : ($category ? $category->keywords : $this->page->keywords);
				$this->template->desc = $this->aid ? $article->meta_description : ($category ? $category->description : $this->page->description);
				$this->template->homepage = $this->page->highlight ? true : false;
				$this->template->current = $this->actualUrl;
				$this->template->lang = $this->lang;
				$this->template->singlepagePages = $this->model->getPages()->where('visibility', 1)->order('position ASC');
				
				$this->template->setTranslator($this->translator);
			}
		}
		
		public function getPagesArray() {
			if (!empty($this->url)) {
				$this->pages = explode('/', $this->url);
				$this->actualUrl = $this->pages[count($this->pages)-1];
			}
		}
		
		public function getPathError () {
			if (!empty($this->url)) {
				foreach (array_reverse($this->pages) as $url) {
					if (!$this->model->getPages()->where('url = ? OR url'.$this->lang.' = ?', $url, $url)->fetch()) {
						if ($url == $this->actualUrl) {
							$this->error('', 404);
						}
						else {
							$this->error('', 301);
						}
					}
				}
			}
			
			return false;
		}
		
		public function getPageLayout() {
			$this->pid = count($this->pages) > 1 ? $this->model->getPages()->where('url'.$this->lang.' = ? OR url = ?', $this->pages[count($this->pages)-2], $this->pages[count($this->pages)-2])->fetch()->id : 0;
			
			if (!$this->page = $this->model->getPages()->select('*, title'.$this->lang.' AS title, description'.$this->lang.' AS description, keywords'.$this->lang.' AS keywords')->where('url'.$this->lang.' = ? OR url = ?', $this->actualUrl, $this->actualUrl)->where('pid', $this->pid)->order('pid ASC')->fetch()) {
				$this->page = $this->model->getPages()->select('*, title'.$this->lang.' AS title, description'.$this->lang.' AS description, keywords'.$this->lang.' AS keywords')->where('highlight', 1)->fetch();
			}
			
// 			if (!$this->page->highlight) {
// 				$this->page->visibility ?: $this->error('', 408);
// 			}
			
			$this->setView('layout'.$this->page->layout);
		}
		
		public function createComponentMenu ($name) {
			return new Menu($this, $name);
		}
		
		public function createComponentTags ($name) {
			return new Tags($this, $name);
		}
		
		public function createComponentCategories ($name) {
			return new Categories($this, $name);
		}
		
		public function createComponentBlock1 ($name) {
			return $this->switchModule(1, $this, $name);
		}
		
		public function createComponentBlock2 ($name) {
			return $this->switchModule(2, $this, $name);
		}
		
		public function createComponentBlock3 ($name) {
			return $this->switchModule(3, $this, $name);
		}
		
		public function createComponentBlock4 ($name) {
			return $this->switchModule(4, $this, $name);
		}
		
		public function switchModule($block, $parent, $name) {
			$blockModule = $this->model->getPagesModules()->where(array('pages_id' => $this->page->id, 'position' => $block))->fetch();
			switch ($blockModule->modules_id) {
				case 1:
					return new EditorPresenter($parent, $name, $blockModule);
					break;
				case 2:
					return new ArticlesPresenter($parent, $name, $blockModule);
					break;
				case 3:
					return new Top($parent, $name);
					break;
			}
		}
		
		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}
		
		public function createComponentArticles ($name) {			
			return new ArticlesPresenter($this, $name);
		}
		
		public function createComponentEditor ($name) {
			return new EditorPresenter($this, $name);
		}
		
		public function createComponentSlider ($name) {
			return new Slider($this, $name);
		}
		
		public function createComponentCurrencies ($name) {
			return new Currencies($this, $name);
		}
		
		public function createComponentContactForm ($name) {
			return new ContactForm($this, $name);
		}
		
		public function createComponentBreadcrumb ($name) {
			return new Breadcrumb($this, $name);
		}
		
		public function createComponentNewsletter ($name) {
			return new Newsletter($this, $name);
		}
		
		public function createComponentCart ($name) {
			return new Cart($this, $name);
		}
		
		public function createComponentSearch ($name) {
			return new Search($this, $name);
		}

		/** @return CssLoader */
		protected function createComponentCss () {
			return $this->webLoader->createCssLoader('front');
		}
		
		/** @return JavaScriptLoader */
	    protected function createComponentJs () {
	        return $this->webLoader->createJavaScriptLoader('front');
	    }
	    
	    public function createComponentAddToCart () {
	    	return new Multiplier(function ($id) {
	    		return new AddToCart($id);
	    	});
	    }
		
		public function createComponentUserbar ($name) {
			return new Userbar($this, $name);
		}
		
		public function getPageModule ($page, $position) {
			$module = $page->related("pages_modules")->where('position', $position)->fetch();
			
			return $module;			
		}
		
		public function createComponentFacebookLogin ($name) {
			return new FacebookLogin($this, $name);
		}
	}