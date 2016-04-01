<?php
	use Nette\Http\Request;

	use Nette\Utils\Strings;

	use Nette\Http\Url;

	use Nette\Http\UrlScript;

	use Nette\Application\Routers\Route,
		Nette\Application\Routers\RouteList,
		Nette\Application\Routers\SimpleRouter,
		Nette\Application\Routers\CliRouter;
use Nette\Configurator;
use Tracy\Debugger;

	// Load Nette Framework
	require __DIR__ . '/../vendor/autoload.php';

	// Configure application
	$configurator = new Nette\Configurator;
	$configurator->setTempDirectory(__DIR__ . '/../temp');

	// Enable Nette Debugger for error visualisation & logging
	$configurator->setDebugMode(true);
	Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/../log');

	// Enable RobotLoader - this will load all classes automatically
	$configurator->createRobotLoader()
		->addDirectory(__DIR__)
		->addDirectory(__DIR__ . '/../vendor/others')
		->register();

	// Create Dependency Injection container from config.neon file
	$configurator->addConfig(__DIR__ . '/config/config.neon', Configurator::AUTO);
	$configurator->addConfig(__DIR__ . '/config/config.webloader.neon');
	$container = $configurator->createContainer();

	// Init application
	$application = $container->application;

	// Setup router using mod_rewrite detection
	if ($container->parameters['consoleMode']) {
		$container->router[] = new CliRouter(array('action' => 'Cli:default'));
// 		$container->removeService('httpRequest');
// 		$container->addService('httpRequest', function() {
// 			// Podle potreby muzeme pouzit nastaveni z configu nebo vzit z parametru prikazove radky, aj.
// 			$uri = new UrlScript();
// 			$uri->scheme = 'http';
// 			$uri->port = Url::$defaultPorts['http'];
// 			$uri->host = 'localhost';
// 			$uri->path = '/';
// 			$uri->canonicalize();
// 			$uri->path = Strings::fixEncoding($uri->path);
// 			$uri->scriptPath = '/';
// 			return new Request($uri, array(), array(), array(), array(), array(), 'GET', null, null);
// 		});

// 		$application->allowedMethods = false;
// 		$container->router[] = new CliRouter(array('action' => 'Cli:default'));
	}
	else {
		$langs['cs'] = '';

		if (count($eshops = $container->model->getPagesModules()->where('modules_id', 3)->where('pages_modules.position', 1)->where('pages.highlight', 0))) {
			foreach ($eshops as $eshop) {
				$eshopNames[] = $eshop->pages->url;
			}
		}

		if ($booking = $container->model->getPagesModules()->where('modules_id', 4)->fetch()) {
			$bookingNames[] = $booking->pages->url;
		}

		foreach ($container->model->getLanguages() as $lang) {
			$langs[$lang->key] = '_'.$lang->key;
			$url = 'url'.$langs[$lang->key];

			if (count($eshops)) {
				foreach ($eshops as $eshop) {
					$eshopNames[] = $eshop->pages->$url;
				}
			}

			if ($booking) {
				$bookingNames[] = $booking->pages->$url;
			}
		}

		$container->router[] = new Route('index.php', 'Front:Uvod:default', Route::ONE_WAY/*,Route::SECURED*/);

		$container->router[] = $adminEshopRouter = new RouteList('AdminEshop');
		$adminEshopRouter[] = new Route('admin/eshop/<presenter>/<action>[/<id>]', array(
			'modul' => 'Admin:Eshop',
			'presenter' => 'Products',
			'action' => 'default'
		)/*,Route::SECURED*/);

		$container->router[] = $adminBookingRouter = new RouteList('AdminBooking');
		$adminBookingRouter[] = new Route('admin/booking/<presenter>/<action>[/<id>]', array(
			'modul' => 'Admin:Booking',
			'presenter' => 'Rooms',
			'action' => 'default'
		)/*,Route::SECURED*/);

		$container->router[] = $adminRouter = new RouteList('Admin');
		$adminRouter[] = new Route('admin/<presenter>/<action>[/<id>]', 'Homepage:default'/*,Route::SECURED*/);

		$container->router[] = $frontBookingRouter = new RouteList('FrontBooking');
		$frontBookingRouter[] = new Route('[<lang [a-z]{2}>+<currency=czk [a-z]{3}>/]<booking>/<presenter>/<action>[/<id>]', array(
				'lang' => array (
						Route::FILTER_TABLE => $langs
				),
				'booking' => array (
						Route::PATTERN => isset($bookingNames) && count($bookingNames) ? implode('|', $bookingNames) : 'booking'
				),
				'presenter' => 'Homepage',
				'action' => 'default'
		)/*,Route::SECURED*/);

		$container->router[] = $frontEshopRouter = new RouteList('FrontEshop');
		$frontEshopRouter[] = new Route('[<lang [a-z]{0,2}>+<currency=czk [a-z]{3}>/]<eshop>/<category>+c<cid>/[<product>+<presenter><pid>.htm]', array(
			'lang' => array (
					Route::FILTER_TABLE => $langs
			),
			'eshop' => array (
					Route::PATTERN => isset($eshopNames) && count($eshopNames) ? implode('|', $eshopNames) : 'eshop'
			),
			'presenter' => array(
					Route::VALUE => 'Categories',
					Route::FILTER_TABLE => array(
						'p' => 'Products'
					)
			),
			'action' => 'view'
		)/*,Route::SECURED*/);
		$frontEshopRouter[] = new Route('[<lang [a-z]{0,2}>+<currency=czk [a-z]{3}>/]<eshop>/<presenter>/<action>[/<id>]', array(
			'lang' => array (
					Route::FILTER_TABLE => $langs
			),
			'eshop' => array (
					Route::PATTERN => isset($eshopNames) && count($eshopNames) ? implode('|', $eshopNames) : 'eshop'
			),
			'presenter' => 'Homepage',
			'action' => 'default'
		)/*,Route::SECURED*/);

		$container->router[] = $frontRouter = new RouteList('Front');
		$frontRouter[] = new Route('<presenter>/<action>[/<id>]', array(
			'presenter' => array (
					Route::PATTERN => 'mailing|feed'
			)
		)/*,Route::SECURED*/);

		$frontRouter[] = new Route('[<lang [a-z]{0,2}>+<currency=czk [a-z]{3}>/][<url>/][<category>+c<cid>/][<tag>+t<tid>/][<article>+a<aid>.htm]', array(
			'lang' => array (
						Route::FILTER_TABLE => $langs
					),
			'presenter' => 'Page',
			'action' => 'default',
			'url' => array (
					Route::PATTERN => '.*',
					Route::FILTER_OUT => function ($url) {
						return $url;
					}
			)
		)/*,Route::SECURED*/);

// 		$frontRouter[] = new Route('<presenter>/<action>[/<id>]', 'Mailing:view'/*,Route::SECURED*/);
	}

	// Run the application!
 	$application->catchExceptions = !$configurator->isDebugMode();
	$application->errorPresenter = "Error";
	$container->application->run();