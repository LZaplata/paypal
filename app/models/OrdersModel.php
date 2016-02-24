<?php
	namespace App\Model;

	use Nette\Object;
	use Nette\Database\Context;
	
	class Orders extends Object {
		/** @var Nette\Database\Context */		
		private $database;
		
		public function __construct(Context $database) {
			$this->database = $database;
		}
		
		/**
		 * @return \Nette\Database\Table\Selection
		 */
		public function getAll () {
			return $this->database->table('orders');
		}
		
		/**
		 * 
		 * @return \Nette\Database\Table\Selection
		 */
		public function getAllFinished () {
			return $this->getAll()->where('state >= ?', 0);
		}
	}