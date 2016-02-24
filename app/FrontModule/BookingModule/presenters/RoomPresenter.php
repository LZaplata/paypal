<?php
	namespace FrontBookingModule;
	
	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Application\UI\Form;
	
	use Nette\Application\UI\Multiplier;

	class RoomPresenter extends BasePresenter {
		public $areal;
		public $actualRoom;
		public $rooms;
		public $objects = array();
		public $prices;
		/** @persistent */	
		public $search = array();
		
		public function actionView () {
			$this->actualRoom = $this->model->getBookingRooms()->wherePrimary($this->rid)->fetch();
			$this->areal = $this->actualRoom->pid == 0 ? $this->actualRoom : $this->getParentRoom($this->actualRoom->pid);						
			if($this->actualRoom->layout == 1)
			{	
				if($this->search){
					$objects = $this->searchingObjects();
					$combinations = $this->getCombinations();
					$this->objects = $this->getObjects($objects, $combinations);
				}
			}elseif($this->actualRoom->layout == 2){
								
			}
		}
		
		public function renderView () {			
			$this->template->keywords = '';
			$this->template->title = '';
			$this->template->desc = '';
			$this->template->areal = $this->areal;
			$this->template->room = $this->actualRoom;
			$this->template->rooms = $this->getRoomChildrens($this->areal->id);
			$this->template->objects = $this->objects;
			$this->template->prices = $this->prices;
		}
		
		public function getRoomChildrens ($id) {
			return $this->rooms = $this->model->getBookingRooms()->where('pid', $id)->order('name ASC');
		}
		
		public function getParentRoom ($id) {
			return $this->model->getBookingRooms()->where('id', $id)->fetch();
		}
		
		public function createComponentSearchForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addText('dateFrom','Od')
				->setAttribute('class','datetime');
			$form->addText('dateTo','Do')
				->setAttribute('class','datetime');
			$form->addText('capacity','Počet osob');
			$form->addSubmit('submit','Vyhledat');
			$form->onSuccess[] = callback ($this, 'search');
			
			if ($this->search) {
				$form->setValues($this->search);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		
		/**
		 * Naleznuti vsech zabranych "objektu" v rozsahu(od - do)
		 * Vsechny "objekty", ktere jsou k dispozici pro dannou "mistnost"
		 * Odecteni vsech zabranych "objektu" od vsech objektu v danne "mistnosti"
		 * @return array
		 */
		public function searchingObjects(){
			$values = $this->search;

			$bookings = $this->model->getBookingBookings()->where('(dateFrom <= ? AND dateTo >= ?) OR (dateFrom <= ? AND dateTo >= ?)', $values['dateFrom'], $values['dateFrom'], $values['dateTo'], $values['dateTo'])->fetchPairs('id', 'booking_objects_id');
			
			$objects = $this->model->getBookingObjects()->where('booking_rooms_id', $this->rid);
			
			if (count($bookings)) {
				$objects->where('id NOT IN ?', array_values($bookings));
			}

			return $objects;
		}
		
		/**
		 * Vraceni vsech kombinaci dle kapacity
		 * @return array
		 */
		public function getCombinations () {
			$amount = $this->search['capacity'];
			$combinations = $amount;
			$result = array();
				
			//každý krok zvyšuje počet členů v kombinaci
			for ($i = 1; $i <= $amount; $i++, $combinations *= $amount) {
				//iterace do maximálního počtu kombinací pro n-členů
				for ($j = 1; $j <= $combinations; $j ++) {
					$capacity = "";
					$count = 0;
					$combination = $j;
						
					//pro každý člen v kobinaci rozšířim string
					for ($k = 1; $k <= $i; $k++) {
						$capacity .= $amount - ($combination % $amount);
						$count += $amount - ($combination % $amount);
						$combination /= $amount;
					}
						
					if ($count == $amount) {
						//prevedes text na pole
						$array = str_split($capacity);
						//srovnas cisla od nejmensiho po nejvetsi
						sort($array);
						//ulozis do pole
						$result[] = $array;
					}
				}
			}
				
			//vyberes kazdy prvek od zacatku dokonce
			for($i=0;$i<count($result);$i++){
				//od vybraného prvku do konce
				for($x=$i;$x<(count($result)-1);$x++){
					//porovnas zda pole jsou stejne -> unset
					if($result[$i] == $result[$x + 1]){
						$index[] = $i;
					}
				}
			}
			
			if (isset($index)) {
				foreach($index as $value){
					unset($result[$value]);
				}
			}
			
			return $result;			
		}
		
		/**
		 * Ziskani seskupeni objektu dle kombinaci
		 * @param array $objects
		 * @param array $combinations
		 * @return array
		 */
		public function getObjects($objects, $combinations) {
			$return = array();
			$prices = array();
			$i = 1;
			
			//prochazeji jednotlivych kombinaci			
			foreach($combinations as $combination){
				$rows = null;
				$data = clone $objects;
				$price = 0;
				
				if (count($combination) == 1) {
					foreach ($data->where('capacity', $combination[0]) as $row) {
						$return[$i] = array($row);
						$prices[$i][$row->id] = $this->getObjectPrice($row);
						
						$i++;
					}
				}
				else {				
					$data = $data->where('capacity IN ?',$combination)->limit(30);
					//pocet kombinaci
					if(count($data)) {
						$count = array();
						
						foreach($combination as $capacity){							
							$valuesCount = array_count_values($combination);							
							$data2 = clone $data;
							$query = $data2->where('capacity', $capacity);
							$objectsCount = count($query);
							
							if ($row = $query->where('id NOT IN ?', $rows == null ? array(0) : array_keys($rows))->fetch()) {
								$rows[$row->id] = $row;
								$prices[$i][$row->id] = $this->getObjectPrice($row);
							}
							
							//do pole pocet
							$count[] = $valuesCount[$capacity] <= $objectsCount ? $objectsCount : 0;
						}
						
						sort($count);
						
						//prvni nejmensi
						if($count[0] != 0){
							$return[$i] = $rows;

							$i++;
						}
					}
				}
			}
			
			$this->prices = $prices;

			return $return;
		}
		
		public function getObjectPrice ($object) {
			$room = $object->booking_rooms;
			//ceny objektu                       
			$prices = $object->related('booking_prices');				
			if ($this->search) {
				//od-do				
				$startDate = strtotime($this->search['dateFrom']);
				$endDate = strtotime($this->search['dateTo']);				
				//pokud bude vice cen pro danny objekt
				if($prices->count() > 1){
					//pocatecni cena
					$price = 0;
					//projizdim od - do 
					while ($startDate < $endDate) {
						$priceObject = clone $prices;
						//ziska jednu cenu od dateFrom 
						$priceObject = $priceObject->where('dateFrom < ?',date('Y-m-d',$startDate))->order('dateFrom DESC')->limit(1)->fetch();
						//dump($priceObject->price);
						$price += $priceObject->price;						
						//pricte dle intervalu a jednotky
						$startDate = strtotime('+'.$room->interval * $room->interval_divisor.' minutes', $startDate);						
					}
				}else{
					$price = $prices->fetch();
					//zjistíme časový interval mezi zadanými daty v minutách				
					$interval = ($endDate - $startDate) / 60;
					//kolikrát se cena objektu musí násobit vzhledem k intervalu u pokoje
					$multi = $interval / ($room->interval * $room->interval_divisor);
					//pokud je nastaven interval na noc, odečteme "1 den"
// 					if ($room->interval_divisor == 1440) {
// 						$multi -= 1;
// 					}
				}
			}
			
			if ($prices->count() > 1) {
				return $price;
			}else{
				return $price->price * $multi;
			}
		}
		
		public function search ($form) {
			$values = $form->getValues(TRUE);
			$this->redirect('this',array('search'=>$values));						
		}
		
		public function createComponentBooking($name){
			return new Multiplier(function ($index) {		
				return new Booking($index, $this->objects, $this->sectionSession, $this->search, $this->prices);
			});
		}
	}