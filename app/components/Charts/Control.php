<?php
	use Nette\Application\UI\Control;

	class chartControl extends Control {
		public $type;
		public $name;
		public $filterColumn;
		public $defaultValue;
		
		public function __construct($type, $name) {
			parent::__construct();
						
			$this->name = $name;
			$this->type = $type;
		}
	}