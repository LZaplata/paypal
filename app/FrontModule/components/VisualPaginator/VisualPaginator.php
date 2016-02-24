<?php

use Nette\Utils\Paginator;

use Nette\Application\UI\Control;
use Nette\Http\Url;

/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 * @link       http://extras.nettephp.com
 * @package    Nette Extras
 * @version    $Id: VisualPaginator.php 4 2009-07-14 15:22:02Z david@grudl.com $
 */

/*use Nette\Paginator;*/



/**
 * Visual paginator control.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2009 David Grudl
 * @package    Nette Extras
 */
class VisualPaginator extends Control
{
	/** @var Paginator */
	private $paginator;

	/** @persistent */
	public $page = 1;



	/**
	 * @return Nette\Paginator
	 */
	public function getPaginator()
	{
		if (!$this->paginator) {
			$this->paginator = new Paginator();
		}
		return $this->paginator;
	}



	/**
	 * Renders paginator.
	 * @return void
	 */
	public function render()
	{
		$paginator = $this->getPaginator();
		$page = $paginator->page;
		if ($paginator->pageCount < 2) {
			$steps = array($page);

		} else {
			$arr = range(max($paginator->firstPage, $page - 3), min($paginator->lastPage, $page + 3));
			$count = 100;
			$quotient = ($paginator->pageCount - 1) / $count;
			for ($i = 0; $i <= $count; $i++) {
				$arr[] = round($quotient * $i) + $paginator->firstPage;
			}
			sort($arr);
			$steps = array_values(array_unique($arr));
		}
		
		$values = $_GET;
		$data = array();
		
		if (isset($values['data'])) {
			parse_str(Url::unescape($values['data']), $data);
		}
			
		unset($values['do']);
		unset($values['data']);
		unset($data['do']);
			
		$params = array_merge($values, $data);

		$this->template->steps = $steps;
		$this->template->paginator = $paginator;
		$this->template->params = $params;
		$this->template->setFile(dirname(__FILE__) . '/template.phtml');
		$this->template->render();
	}



	/**
	 * Loads state informations.
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->getPaginator()->page = $this->page;
	}

}