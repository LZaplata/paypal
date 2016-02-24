<?php	
	use AdminModule\FilesGrid;

use AdminModule\Langs;

	use Nette\Application\UI\Control;

	use Nette\Application\UI\Form;

	class FilesPresenter extends Control {
		public $filestore;
		public $files = array();
		public $file;
		public $article;
		public $edit;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->getFiles();
		}
		
		public function getFiles () {
			$this->files = $this->presenter->model->getFilestoresFiles()->where('filestores_id', $this->presenter->id)->order('position ASC');
		}
		
		public function render() {
			if ($this->edit) {
				$this->template->setFile(__DIR__.'/position.latte');
			}
			else {
				$this->template->setFile(__DIR__.'/view.latte');
			}
			
			$this->template->files = $this->files;
			
			$this->template->render();
		}
		
		public function handleEditFile ($id) {
			$this->presenter->id = $id;
			
			$this->file = $this->presenter->model->getFilestoresFiles()->wherePrimary($id)->fetch();
// 			$this->article = $this->presenter->model->getArticles()->wherePrimary($this->file->articles_id)->fetch();
			$this->presenter->section = $this->presenter->model->getSections()->wherePrimary($this->presenter->sid)->fetch();
			
			$this->edit = true;
			
			$this->template->file = $this->file;
			$this->template->article = $this->article;
		}
		
		public function handleUpload () {
			$httpRequest = $this->presenter->context->getService('httpRequest');
				
			$basePath = $httpRequest->url->basePath;
				
			$files = $httpRequest->getFiles();
			
			foreach ($files as $file) {
				$lastPosition = $this->presenter->model->getFilestoresFiles()->where('filestores_id', $this->presenter->id)->order('position DESC')->fetch();
				
				$values['name'] = $file->getSanitizedName();
				$values['filestores_id'] = $this->presenter->id;
				$values['position'] = !$lastPosition ? 0 : $lastPosition->position+1;
				
				$file->move(WWW_DIR . '/files/files/f'.$this->presenter->id.'-'  . $file->getSanitizedName());
				
				if (!$this->presenter->model->getFilestoresFiles()->where(array('filestores_id' => $this->presenter->id, 'name' => $values['name']))->fetch()) {
						$this->presenter->model->getFilestoresFiles()->insert($values);
				}
			}
		}
		
		public function handleVisibility ($id, $fileID, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->presenter->model->getFilestoresFiles()->wherePrimary($fileID)->update(array("visibility" => $vis));
		
			$this->presenter->flashMessage('Nastavení zobrazení souboru změněno!');
		}
		
		public function handleHighlight($id, $fileID, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->presenter->model->getFilestoresFiles()->wherePrimary($fileID)->update(array("highlight" => $vis));
		
			$this->presenter->flashMessage('Nastavení zvýraznění souboru změněno!');
			if ($this->presenter->isAjax()) {
				$this->invalidateControl('filesTable');
			}
		}
		
		public function handleDelete ($id, $fileID) {
			$ids  = (array)$fileID;
			
			foreach($ids as $val){			
				$file = $this->presenter->model->getFilestoresFiles()->wherePrimary($val)->fetch();
				
				$this->presenter->model->getFilestoresFiles()->wherePrimary($val)->delete();
				
				$dir = WWW_DIR . "/files/files/f$id-";
				
				if (file_exists($dir.$file->name)) {
					unlink($dir.$file->name);
				}
					
				$this->presenter->flashMessage('Soubor byl smazán');
			}
		}
		
		public function createComponentAddForm () {
			$this->presenter->section = $this->presenter->model->getSections()->wherePrimary($this->presenter->sid)->fetch();
			
			$form = new Form();
			
			$form->addGroup('Základní informace');
			$form->addText('title'.$this->presenter->lang, 'Titulek:');
			
// 			$form->addMultiSelect('tags', 'Tagy:', array_combine(explode(',', trim(str_replace(', ', ',', $this->presenter->section->tags))), explode(',', trim(str_replace(', ', ',', $this->presenter->section->tags)))))
// 				->setOption('description', 'Držte CTRL pro výběr více možností');
			
			$form->addGroup('')
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->file ? 'Upravit' : 'Vytvořit');
			
			$form->addHidden('id', $this->presenter->id);
				
			$form->onSuccess[] = callback ($this, 'editFile');
			
			if ($this->file) {
				$values['title'] = $this->file->title;
				$values['tags'] = $this->presenter->model->getFilestoresFilesTags()->where('filestores_files_id', $this->presenter->id)->fetchPairs('name', 'name');
				
				$form->setValues($values);
			}
			
			return $form;
		}
		
		public function editFile ($form) {
			$values = $form->getValues();
			
// 			$this->presenter->model->getFilestoresFilesTags()->where('filestores_files_id', $values['id'])->delete();
// 			foreach ($values['tags'] as $tag) {
// 				$data['name'] = trim($tag);
// 				$data['filestores_files_id'] = $values['id'];
			
// 				$this->presenter->model->getFilestoresFilesTags()->insert($data);
// 			}
				
// 			unset($values['tags']);
			
			$this->presenter->model->getFilestoresFiles()->wherePrimary($values['id'])->update($values);
			
			$this->presenter->flashMessage('Údaje o souboru byly změněny');
			$this->presenter->redirect($this->presenter->presenterName.':files', array($this->presenter->id, $this->presenter->sid, $this->presenter->urlID));
		}
		
		public function handleChangeOrder () {
			$positions = $_GET['positions'];
			// 			unset($positions['do']);
		
			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->presenter->model->getFilestoresFiles()->wherePrimary($value)->update($values);
			}
		
			$this->presenter->flashMessage('Pořadí bylo změněno');
		}
		
		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}
		
		public function createComponentGrid () {
			return new FilesGrid($this->files);
		}
		
		public function handlePosition () {
			$this->edit = 'position';
		}
	}