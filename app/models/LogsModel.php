<?php
	namespace App\Model;

	use Nette\Object;
	use Nette\Database\Context;
				
	class Logs extends Object {
		/** @var Nette\Database\Context */		
		private $database;
		
		public function __construct(Context $database) {
			$this->database = $database;
		}
		
		/**
		 * @param array $values
		 * @return \Nette\Database\Table\Selection
		 */
		public function getSearchLog ($values = false) {
			$log = $this->database->table('log_search');
			
			if ($values) {
				$log->where($values);
			}
			
			return $log;
		}
		
		/**
		 * @return \Nette\Database\Table\Selection
		 */
		public function getSearchLogWithCounts () {			
			return $this->database->query('SELECT *, COUNT(id) AS count FROM (SELECT * FROM log_search ORDER BY date DESC) AS temp GROUP BY query ORDER BY date DESC LIMIT 5');
		}
		
		/**
		 * @return \Nette\Database\Table\Selection
		 */
		public function getLoginLog () {
			return $this->database->table('log_login');
		}
	}