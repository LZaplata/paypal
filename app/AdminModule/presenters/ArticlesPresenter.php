<?php
	namespace AdminModule;

	use Nette\Utils\Strings;

	use Nette\DI\IContainer;

	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;
use Nette\Application\UI\Multiplier;
		
	class ArticlesPresenter extends ContentPresenter {
		public $articles;
		public $article;	
		public $fields;
		public $versions;
		public $parent;
				
		public function actionView ($id) {			
			if (!isset($this->request->getParameters()['grid-order']) && !$this->isAjax()) {
				$this->redirect('this', array('grid-order' => 'position ASC'));
			}
			
			$this->actionDefault();
			
			$this->id = $id;
			
			$query = $this->model->getArticles()->select('articles.*, :articles_categories.categories_id')->where('sections_id = '.$id)/*->order('id DESC')*/;
			
			// nette 2.2
// 			$this->articles = $this->model->getArticlesInserted($query->getSql())->select('*')->group('galleries_id');
			// nette 2.3 - akorát se neřeší verze, případně ten dotaz vymyslet jinak
			$this->articles = $query;
			
			$this->section = $this->model->getSections()->wherePrimary($id)->fetch();
			
			$this->urlID = $this->id;
		}
		
		public function actionAdd ($id) {
			$this->actionView($id);
			
			$this->section = $this->model->getSections()->wherePrimary($id)->fetch();
			
			$this->fields = $this->model->getSectionsFields()->where('sections_id', $id)->where('visibility', 1)->order('position ASC');
		}
		
		public function actionEdit ($id, $sid, $version = null) {						
			$this->section = $this->model->getSections()->wherePrimary($sid)->fetch();
			
			if ($this->section->versions) {
				$row = $this->model->getArticles()->where('pid', $id)->order('created DESC')->fetch();
				if ($version == null && $row) {
					$this->redirect('this', array('version' => $row['id']));
				}
			}
			
			if ($version != null && $this->section->versions) {
				$this->article = $this->model->getArticles()->wherePrimary($version)->fetch();
				$this->parent = $this->model->getArticles()->wherePrimary($id)->fetch();
			}
			else {
				$this->article = $this->model->getArticles()->wherePrimary($id)->fetch();
			}
			
			if ($this->article->users_id != $this->user->id) {
				if (!$this->acl->isAllowed($this->user->identity->role, 'post', 'edit')) {
					$this->error();
				}
			}
			
			if ($this->article) {
				$this->versions = $this->model->getArticles()->select('users.*, articles.*, articles.created AS date')->where('galleries_id', $this->article->galleries_id)->order('created DESC');
			}
			
			$this->fields = $this->model->getSectionsFields()->where('sections_id', $sid)->where('visibility', 1)->order('position ASC');
			
			$this->actionView($this->article->sections_id);
			$this->setLastEdited('articles');
			
			$this->id = $id;
			$this->urlID = $sid;
			
			$this->lastEdited->rows[] = $id;
		}
		
		public function actionGallery ($id, $sid, $urlID) {			
			$this->actionDefault();
			$this->setLastEdited('articles');
			
			if (!isset($this->request->getParameters()['gallery-grid-order'])) {
				$this->redirect('this', array('gallery-grid-order' => 'position ASC'));
			}
			
			$this->section = $this->model->getSections()->wherePrimary($sid)->fetch();
			$this->article = $this->model->getArticles()->where('galleries_id', $id)->fetch();
			
			$this->id = $id;
			$this->sid = $sid;
			$this->urlID = $sid;
			
			$this->lastEdited->rows[] = $id;
		}
		
		public function actionFiles ($id, $sid, $urlID) {
			$this->actionDefault();
			$this->setLastEdited('articles');
			
			if (!isset($this->request->getParameters()['files-grid-order'])) {
				$this->redirect('this', array('files-grid-order' => 'position ASC'));
			}
			
			$this->section = $this->model->getSections()->wherePrimary($sid)->fetch();
			$this->article = $this->model->getArticles()->where('filestores_id', $id)->fetch();
			
			$this->id = $id;
			$this->sid = $sid;
			$this->urlID = $sid;
			
			$this->lastEdited->rows[] = $id;
		}
		
		public function actionPosition ($id) {
			$this->actionView($id);
		}
		
		public function renderView () {
			$this->renderDefault();
			$this->template->articles = $this->articles;
			$this->template->section = $this->section;
// 			$this->template->pagesModules = $this->pagesModules;
		}
		
		public function renderAdd () {
			$this->renderView();
		}
		
		public function renderEdit () {
			$this->renderView();
		}
		
		public function renderGallery () {
			$this->renderDefault();
		}
		
		public function renderFiles () {
			$this->renderDefault();
		}
		
		public function renderPosition () {
			$this->renderDefault();
			$this->template->articles = $this->articles->order('position ASC');
		}
		
// 		public function getArticles ($id) {
// 			if ($id == 0) {
// 				return $this->model->getArticlesCategories();	
// 			}
// 			else {
// 				return $this->model->getArticlesCategories()->where('categories_id', $id);
// 			}
// 		}
		
		public function createComponentAddForm() {
			return new Multiplier(function ($key) {
				$key = $key == 'cs' ? '' : '_'.$key;
				
				$form = new Form();
				
				$form->getElementPrototype()->addClass('form-horizontal');
				
				$form->addGroup('Základní informace');
				$form->addText('name'.$key, 'Jméno:')
					->setRequired('Vyplňte prosím název článku!')
					->setAttribute('class', 'input-name');
	// 				->setAttribute('onkeyup', 'createUrl()');
				
				$form->addText('url'.$key, 'URL:')
					->setRequired('Vyplňte prosím url stránky!')
					->setAttribute('class', 'input-url');
	
				if ($this->section->title) {
					$form->addText('title'.$key, 'Titulek:')
						->setRequired('Vyplňte prosím titulek stránky!');
				}
	
				if ($this->section->keywords) {
					$form->addText('keywords'.$key, 'Klíčová slova:')
						->setRequired('Vyplňte prosím klíčová slova!');
				}
				
				if ($this->section->meta_description) {
					$form->addText('meta_description'.$key, 'Meta popisek:')
						->setRequired('Vyplňte prosím meta popisek!');
				}
				
				if ($this->section->categories) {
					$form->addMultiSelect('categories', 'Kategorie:', $this->model->getCategories()->where('sections_id', $this->section->id)->fetchPairs('id', 'name'))
						->getControlPrototype()->class('chosen');
				}
				
				if ($this->section->slider) {
					$form->addMultiSelect('pages', 'Stránky:', $this->model->getPages()->fetchPairs('id', 'name'))
						->getControlPrototype()->class('chosen');
				}
				
				if ($this->section->tags) {
					$form->addMultiSelect('tags', 'Tagy:', $this->model->getSectionsTags()->where('id_section', $this->section->id)->fetch()->sections->related('articles')->fetchPairs('id', 'name'))
						->getControlPrototype()->class('chosen');
				}
				
				if ($this->section->date) {
					$form->addText('altDate', 'Datum:')
						->getControlPrototype()->class('date');
				}
				
				$form->addHidden('date', date('Y-m-d H:i'))
					->getControlPrototype()->addAttributes(array('id' => 'date'));
				
				if ($this->section->expirationDate) {
					$form->addGroup('Platnost');
					$form->addText('altExpirationDateFrom', 'Od:');
					
					$form->addHidden('expirationDateFrom');
					
					$form->addText('altExpirationDateTo', 'Do:');
					
					$form->addHidden('expirationDateTo');
				}
				
				if ($this->section->description) {
					$form->addGroup('Text');
					$form->addTextarea('description'.$key, 'Krátký popis:');
				}
				
				if ($this->section->text) {
					$form->addTextarea('text'.$key, 'Text:')
						->getControlPrototype()->class('tinymce');
				}
				
				$form->addGroup('Speciální položky');
				foreach ($this->fields as $field) {
					switch ($field->type) {
						case 1:
							$form->addText($field->title.$key, $field->name);
							break;
						case 2:
							$form->addTextarea($field->title.$key, $field->name);
							break;
						case 3:
							$form->addCheckbox($field->title.$key, $field->name);
							break;
						case 4:
							preg_match_all('~[,;]?([a-zA-Z0-9ěščřžýáíéúůňťď]+)[,;]?~', $field->values, $values);
							
							$form->addSelect($field->title.$key, $field->name, array_combine($values[1], $values[1]))
								->setPrompt('--Vyberte možnost--');
							break;
						case 5:
							$form->addTextarea($field->title.$key, $field->name)
								->getControlPrototype()->class('tinymce');
					}
				}
				
				$form->addGroup()
					->setOption('container', 'fieldset class="submit"');
				if ($this->section->versions && $this->article) {
					$form->addSubmit('addVersion', 'nová verze')
						->onClick[] = callback($this, 'addVersion');
				}
				$form->addSubmit('add', $this->article ? 'Upravit' : 'Vytvořit')
					->onClick[] = callback($this, $this->article ? 'editArticle' : 'addArticle');
				
				$form->addHidden('users_id', $this->user->id);
				
				$data['altDate'] = $this->article ? $this->article->date->format('j.n.Y H:i') : date('j.n.Y H:i');
				$data['altExpirationDateFrom'] = $this->article && $this->article->expirationDateFrom != null ? $this->article->expirationDateFrom->format('j.n.Y H:i') : null;
				$data['altExpirationDateTo'] = $this->article &&  $this->article->expirationDateTo != null ? $this->article->expirationDateTo->format('j.n.Y H:i') : null;
				
				if ($this->article) {				
					$data['categories'] = $this->model->getArticlesCategories()->where('articles_id', $this->id)->fetchPairs('categories_id', 'categories_id');
					$data['pages'] = $this->model->getArticlesPages()->where('articles_id', $this->id)->fetchPairs('pages_id', 'pages_id');
					$data['tags'] = $this->model->getArticlesTags()->where('id_articles', $this->id)->fetchPairs('articles_id', 'articles_id');
					$data['date'] = $this->article->date->format('Y-m-d H:i');
					$data['expirationDateFrom'] = $this->article->expirationDateFrom != null ? $this->article->expirationDateFrom->format('Y-m-d H:i'): null;
					$data['expirationDateTo'] = $this->article->expirationDateTo != null ? $this->article->expirationDateTo->format('Y-m-d H:i') : null;
					
					$form->setValues($this->article);
				}
				
				$form->setValues($data);
				
				$form->setRenderer(new BootstrapFormRenderer());
				
				return $form;
			});
		}
		
		public function addArticle ($button) {
			$values = $button->parent->values;
			
// 			$values['url'] = Strings::webalize($values['name'].$this->lang);
			
			$lastPosition = $this->model->getArticles()->where('sections_id', $this->id)->order('position DESC')->fetch();
			

			$values['created'] = date('Y-m-d H:i:s');
			$values['date'] = isset($values['altDate']) ? ($values['altDate'] == '' ? date('Y-m-d') : $values['date']) : date('Y-m-d');
			$values['expirationDateFrom'] = isset($values['altExpirationDateFrom']) ? ($values['altExpirationDateFrom'] == null ? null : $values['expirationDateFrom']) : null;
			$values['expirationDateTo'] = isset($values['altExpirationDateTo']) ? ($values['altExpirationDateTo'] == null ? null : $values['expirationDateTo']) : null;
			$values['sections_id'] = $this->section->id;
			$values['users_id'] = $this->user->getIdentity()->getId();
			$values['galleries_id'] = $this->model->getGalleries()->insert(array());
			$values['filestores_id'] = $this->model->getFilestores()->insert(array());
			$values['position'] = !$lastPosition ? 0 : $lastPosition->position+1;
			
			if (!empty($values['categories'])) {
				$categories = $values['categories'];
			}
			
			if (!empty($values['pages'])) {
				$pages = $values['pages'];
			}
			
			if (!empty($values['tags'])) {
				$tags = $values['tags'];
			}
			
			unset($values['altDate']);
			unset($values['altExpirationDateFrom']);
			unset($values['altExpirationDateTo']);
			unset($values['categories']);
			unset($values['pages']);
			unset($values['tags']);
			
			$lastID = $this->model->getArticles()->insert($values);
			$lastID->update(array('pid' => $lastID->id));
			
			if (!empty($categories)) {
				foreach ($categories as $category) {
					$data['articles_id'] = $lastID;
					$data['categories_id'] = $category;
					
					$this->model->getArticlesCategories()->insert($data);
				}
			}
			
			if (!empty($pages)) {
				foreach ($pages as $page) {
					$data = array();
					$data['articles_id'] = $lastID;
					$data['pages_id'] = $page;
					
					$this->model->getArticlesPages()->insert($data);
				}
			}
			
			if (!empty($tags)) {
				$data = array();				
				$module = array(1 => 'editors_id', 2 => 'articles_id', 3 => 'products_id');
				
				foreach ($tags as $tag) {
					$data['id_articles'] = $lastID;
					$data[$module[$this->model->getSectionsTags()->where('id_section', $this->section->id)->fetch()->sections->modules->id]] = $tag;
						
					$this->model->getArticlesTags()->insert($data);
				}
			}
				
			$this->flashMessage('Článek byl úspěšně vytvořen');
			$this->redirect('Articles:View', array('id' => $this->id, 'grid-order' => 'position ASC'));
		}
		
		public function editArticle ($button) {
			$values = $button->parent->values;
			unset($values['add']);
						
//  			$values['url'] = Strings::webalize($values['name'.$this->lang]);
			
			$this->model->getArticlesCategories()->where('articles_id', $this->id)->delete();
			$this->model->getArticlesPages()->where('articles_id', $this->id)->delete();
			$this->model->getArticlesTags()->where('id_articles', $this->id)->delete();
			
			$values['date'] = isset($values['altDate']) ? ($values['altDate'] == '' ? date('Y-m-d') : $values['date']) : date('Y-m-d');
			$values['expirationDateFrom'] = isset($values['altExpirationDateFrom']) ? ($values['altExpirationDateFrom'] == null ? null : $values['expirationDateFrom']) : null;
			$values['expirationDateTo'] = isset($values['altExpirationDateTo']) ? ($values['altExpirationDateTo'] == null ? null : $values['expirationDateTo']) : null;
			
			if (!empty($values['categories'])) {
				$categories = $values['categories'];
			}
			
			if (!empty($values['pages'])) {
				$pages = $values['pages'];
			}
			
			if (!empty($values['tags'])) {
				$tags = $values['tags'];
			}
			
			unset($values['altDate']);
			unset($values['altExpirationDateFrom']);
			unset($values['altExpirationDateTo']);
			unset($values['categories']);
			unset($values['pages']);
			unset($values['tags']);
			
			$this->article->update($values);
			
			if (!empty($categories)) {
				foreach ($categories as $category) {
					$data['articles_id'] = $this->id;
					$data['categories_id'] = $category;
						
					$this->model->getArticlesCategories()->insert($data);
				}
			}
			
			if (!empty($pages)) {
				foreach ($pages as $page) {
					$data = array();
					$data['articles_id'] = $this->id;
					$data['pages_id'] = $page;
					
					$this->model->getArticlesPages()->insert($data);
				}
			}
			
			if (!empty($tags)) {
				$data = array();			
				$module = array(1 => 'editors_id', 2 => 'articles_id', 3 => 'products_id');
			
				foreach ($tags as $tag) {
					$data['id_articles'] = $this->id;
					$data[$module[$this->model->getSectionsTags()->where('id_section', $this->section->id)->fetch()->sections->modules->id]] = $tag;
			
					$this->model->getArticlesTags()->insert($data);
				}
			}
			
			$this->flashMessage('Článek byl úspěšně upraven');
	  		$this->redirect('Articles:View', array('id' => $this->article->sections_id, 'grid-order' => 'position ASC'));
		}
		
		public function addVersion ($button) {
			$values = $button->parent->values;
			$values['sections_id'] = $this->section->id;
			
			unset($values['categories']);
			unset($values['date']);
			unset($values['altDate']);
			unset($values['altExpirationDateFrom']);
			unset($values['altExpirationDateTo']);
				
			if (!$this->model->getArticles()->where((array)$values)->where('id', $this->article->id)->fetch()) {
				$values['pid'] = $this->id;
				$values['created'] = date('Y-m-d H:i:s');
				$article = $this->article->toArray();
		
				unset($article['id']);
				$version = $this->model->getArticles()->insert($article);
		
				$version->update($values);
		
				$this->redirect('this', array('version' => $version->id));
			}
			else $this->redirect('this');
		}
		
		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}
		
		public function createComponentFiles ($name) {
			return new \FilesPresenter($this, $name);
		}

		public function createComponentSeo ($name) {
			return new Seo($this, $name);
		}
		
		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}
		
		public function handleVisibility($id, $articleID, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$ids = (array)$articleID;
			
			foreach ($ids as $id) {
				$article = $this->model->getArticles()->wherePrimary($id)->fetch();
				
				$this->model->getArticles()->where('pid', $article->pid)->update(array("visibility" => $vis));
			}			
			
			$this->flashMessage('Nastavení zobrazení článku změněno!');
		}
		
		public function handleHighlight($id, $articleID, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$ids = (array)$articleID;
			
			foreach ($ids as $id) {
				$article = $this->model->getArticles()->wherePrimary($id)->fetch();
				
				$this->model->getArticles()->where('pid', $article->pid)->update(array("highlight" => $vis));
			}
			
			$this->flashMessage('Nastavení zvýraznění článku změněno!');
		}
		
		public function handleDelete($id, $articleID) {
   			$this->sid = $id;
   			$ids = (array)$articleID;
   			
   			foreach ($ids as $val) {   
   				$article = $this->model->getArticles()->wherePrimary($val)->fetch();
	   			$imgs = $this->model->getGalleriesImages()->where('galleries_id', $article->galleries_id)->fetchPairs('id', 'id');
	   			$files = $this->model->getFilestoresFiles()->where('filestores_id', $article->filestores_id)->fetchPairs('id', 'id');
	   
	   			$this['gallery']->handleDelete($article->galleries_id, $imgs);	
	   			$this['files']->handleDelete($article->filestores_id, $files);
	   			
				$this->model->getGalleries()->wherePrimary($article->galleries_id)->delete();
				$this->model->getFilestores()->wherePrimary($article->filestores_id)->delete();
				
				$this->model->getArticles()->where('pid', $article->pid)->delete();
				$this->model->getArticlesCategories()->where('articles_id', $article->pid)->delete();
				$this->model->getArticlesTags()->where('articles_id', $article->pid)->delete();
				$this->model->getArticlesTags()->where('id_articles', $article->pid)->delete();
   			}
		}
		
		public function handleDeleteVersion ($id, $vid) {
			$this->presenter->model->getArticles()->wherePrimary($vid)->delete();
		
			$this->presenter->redirect('this', array('version' => null, 'grid-order' => null));
		}
		
		public function handleChangeOrder () {
			$positions = $_GET['positions'];
// 			unset($positions['do']);
			
			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->model->getArticles()->where('pid', $value)->update($values);
			}
			
			$this->flashMessage('Pořadí bylo změněno');
		}
		
		public function createComponentGrid () {
			return new DataGrid($this->articles);
		}
		
		public function createComponentVersions () {
			return new VersionsGrid($this->versions);
		}
		
		public function handleCopy ($id, $sid, $lang) {
			foreach ($this->article as $key => $val) {
				if (Strings::match($key, '/_'.$lang.'/')) {
					$index = Strings::replace($key, '/_'.$lang.'/');
					
					$values[$key] = $this->article->$index;
				}
			}
			
			if (count($values)) {
				$this->article->update($values);
			}
			
			$this->redirect('this');
		}
	}