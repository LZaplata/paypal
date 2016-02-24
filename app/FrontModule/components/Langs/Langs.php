<?php
	namespace FrontModule;

	use Nette\Application\UI\Control;

	class Langs extends Control {			
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/langs.latte');
	
			$this->template->langs = $this->presenter->langs->where('visibility', 1);
			
			$this->template->render();
		}
		
		public function renderNames () {
			$this->template->setFile(__DIR__.'/langsNames.latte');
		
			$this->template->langs = $this->presenter->langs->where('visibility', 1);
				
			$this->template->render();
		}
	} 