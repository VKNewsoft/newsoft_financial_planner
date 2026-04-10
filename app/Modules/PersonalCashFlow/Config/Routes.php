<?php

/**
 * Route modul Personal Cash Flow
 */

$routes->group('personal-cash-flow', static function($routes) {
	$routes->add('/', '\App\Modules\PersonalCashFlow\Controllers\PersonalCashFlow::index');
	$routes->add('(:segment)', '\App\Modules\PersonalCashFlow\Controllers\PersonalCashFlow::$1');
	$routes->add('(:segment)/(:any)', '\App\Modules\PersonalCashFlow\Controllers\PersonalCashFlow::$1/$2');
	$routes->add('(:segment)/(:any)/(:any)', '\App\Modules\PersonalCashFlow\Controllers\PersonalCashFlow::$1/$2/$3');
});
