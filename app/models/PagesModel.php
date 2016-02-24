<?php
	namespace App\Model;

	use Nette\Object;
	use Nette\Database\Context;
	
	class Pages extends Object {
		/** @var Nette\Database\Context */		
		private $database;
		
		public function __construct(Context $database) {
			$this->database = $database;
		}
		
		/**
		 * @return \Nette\Database\Table\Selection
		 */
		public function getAll () {
			return $this->database->table('pages');
		}
		
		/**
		 * @param array|int $id
		 * @return \Nette\Database\Table\Selection
		 */
		public function getAllByID ($id) {
			return $this->database->table('pages')->where('id', $id);
		}
		
		/**
		 * @return \Nette\Database\Table\Selection
		 */
		public function getAllModules () {
			return $this->database->table('pages_modules');
		}
		
		/**
		 * @param int|array $sid
		 * @return mixed
		 */
		public function getModulesBySid ($sid) {
			return $this->getAllModules()->where('sections_id', $sid);
		}
	}