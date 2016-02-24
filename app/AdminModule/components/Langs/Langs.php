<?php
	namespace AdminModule;

	use Nette\Application\UI\Control;

	class Langs extends Control {		
		public $lang;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->lang = 'cs';
		}
		
		public function render ($float = true) {			
			$this->template->setFile(__DIR__.'/langs.latte');
			
			$this->template->langs = $this->presenter->langs;
			$this->template->float = $float;
			
			$this->template->render();
		}
		/*
		public function handleChangeLang ($key) {					
			$this->lang = $key;
			
			if ($key == 'cs' || $key == 'cz') {
				$this->presenter->lang = '';
			}
			else {
				$this->presenter->lang = '_'.$key;
			}
			
			if ($this->presenter->presenterName == 'Structure') {
				$this->presenter->handleLoadBlocks();
			}
			
// 			$this->presenter->getComponent('Files')->invalidateControl();
			
			$this->presenter->invalidateControl();
		}
		*/
	} 