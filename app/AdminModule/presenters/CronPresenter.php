<?php
	namespace AdminModule;
	
	class CronPresenter extends \BasePresenter {
		public function actionPosition () {
			set_time_limit(120);
			
			$url = "www.grundresort.cz";
			$kw = "wellness hotel";
			$start=0;
			$htmlGoogle = file_get_contents("http://www.google.cz/search?q=".urlencode($kw)."&start=".$start."&hl=cs");
			preg_match_all(';(<li class="g"><h3 class="r"><a href="/url.*">.*</a></h3><div class="s".*><br/><div>.*</div></div></li>);U', $htmlGoogle, $resultsGoogle);
			
			print_r($resultsGoogle);
			
// 			foreach ($this->context->params['keywords'] as $kw) {
// 				$positionGoogle = 1;
// 				$positionSeznam = 1;
				
// 				for ($i=0; $i<10; $i++) {
// 					$start = $i * 10;
			
// 					$htmlGoogle = file_get_contents("http://www.google.cz/search?q=".urlencode($kw)."&start=".$start."&hl=cs");
// 					$htmlSeznam = file_get_contents("http://search.seznam.cz/?q=".urlencode($kw)."&from=".($start+1));
			
// 					preg_match_all(';<li class="g">(<h3.*>.*</h3><div class="s".*>.*</div>)</li>;U', $htmlGoogle, $resultsGoogle);
// 					preg_match_all(';<div.*data-elm="rf.*".*>(.*)</div>;U', $htmlSeznam, $resultsSeznam);
			
// 					foreach ($resultsGoogle[1] as $result) {
// 						if (strpos($result, $url) && $positionGoogle) {
// 							echo 'Pozice Google '.$kw.': '.$positionGoogle;
// 							// 							$values['positionGoogle'] = $positionGoogle;
// 							$positionGoogle = false;
// 							break;
// 						}
// 						$positionGoogle++;
// 					}
			
// 					foreach ($resultsSeznam[1] as $result) {
// 						if (strpos($result, $url) && $positionSeznam) {
// 							echo 'Pozice Seznam '.$kw.': '.$positionSeznam;
// 							// 							$values['positionSeznam'] = $positionSeznam;
// 							$positionSeznam = false;
// 							break;
// 						}
// 						$positionSeznam++;
// 					}
// 				}
			
// 				if ($positionGoogle >= 100) {
// 					echo "100";
// 					// 					$values['positionGoogle'] = 100;
// 				}
			
// 				if ($positionSeznam >= 100) {
// 					echo "100";
// 					// 					$values['positionSeznam'] = 100;
// 				}
			
// 				// 				$values['date'] = date('Y-m-d');
// 				// 				$values['keyword'] = $kw;
			
// 				// 				$this->model->getStatistics()->insert($values);
// // 			}
		}
	}