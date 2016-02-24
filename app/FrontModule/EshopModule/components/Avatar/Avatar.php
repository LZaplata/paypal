<?php
	namespace FrontEshopModule;

	use Nette\Application\UI\Control;
	
	use Nette\Application\UI\Form;
	
	use Nette\Image;
	
	use Nette\Utils\Finder;		

	class Avatar extends Control {			
		public $user;
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			$this->user = $parent->user;
		}
		
		public function checkAvatar(){
			$dir = WWW_DIR . '/files/avatars/';
			$file_cropper = $dir.'/'.$this->user->id.'-0.png';
			$file_final = $dir.'/'.$this->user->id.'.png';
			if(file_exists($file_cropper)){
				return 1;
			}elseif(file_exists($file_final)){
				return 2;
			}else{
				return 0;
			}
		}
		
		public function handleuploadAvatar () {
			$httpRequest = $this->presenter->context->getService('httpRequest');		
			$basePath = $httpRequest->url->basePath;		
			$files = $httpRequest->getFiles();
			foreach($files as $file){
				$original = $file->move(WWW_DIR . '/files/avatars/temp_'. $this->user->id .'.png', 100);
				$image = Image::fromFile($original);
				$image->resize(1024, 1024, Image::SHRINK_ONLY);	
				$image->save(WWW_DIR . '/files/avatars/'.$this->user->id.'-0.png', 100);
				unlink($original);
			}	
		}
		
		public function createComponentCropForm () {
			$form = new Form();	
			$form->addHidden('left');
			$form->addHidden('top');
			$form->addHidden('width');
			$form->addHidden('height');
			$form->addGroup('')
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('crop', 'Oříznout')->setAttribute('class','btn btn-large btn-primary');			
			$form->onSuccess[] = callback ($this, 'cropImage');								
			return $form;
		}
		
		public function cropImage ($form) {
			$values = $form->getValues();				
			$thumb = Image::fromFile(WWW_DIR . '/files/avatars/'.$this->user->id.'-0.png');
			$thumb->crop($values['left'], $values['top'], $values['width'], $values['height']);
			$thumb->resize(50, 50, Image::STRETCH);
			$thumb->save(WWW_DIR . '/files/avatars/'.$this->user->id.'.png');
			$image = Image::fromFile(WWW_DIR . '/files/avatars/'.$this->user->id.'-0.png');
			@unlink(WWW_DIR.'/files/avatars/'.$this->user->id.'-0.png');
			$this->redirect('this');
		}			
		
		public function handledeleteAvatar(){
			$dir = WWW_DIR . '/files/avatars/';		
			$file_cropper = $dir.'/'.$this->user->id.'-0.png';
			$file_final = $dir.'/'.$this->user->id.'.png';
			if(file_exists($file_cropper)){
				@unlink($file_cropper);
			}elseif(file_exists($file_final)){
				@unlink($file_final);
			}
			$this->presenter->flashMessage('Úspěšně smazán avatar.','success');
			$this->redirect('this');
		}			
		
		public function render () {
			$this->template->setFile(__DIR__.'/avatar.latte');
			$this->template->check = $this->checkAvatar();
			$this->template->render();
		}
	} 