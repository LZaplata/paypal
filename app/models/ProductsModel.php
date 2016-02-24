<?php
	namespace App\Model;

	use Nette\Object;
	use Nette\Database\Context;
	
	class Products extends Object {
		/** @var Nette\Database\Context */		
		private $database;
		
		public function __construct(Context $database) {
			$this->database = $database;
		}
		
		/**
		 * @return \Nette\Database\Table\Selection
		 */
		public function getAll () {
			return $this->database->table('products');
		}
	}