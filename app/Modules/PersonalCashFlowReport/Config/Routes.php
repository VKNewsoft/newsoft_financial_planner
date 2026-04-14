<?php

$routes->group('personal-cash-flow-report', static function($routes) {
	$routes->add('/', '\App\Modules\PersonalCashFlowReport\Controllers\PersonalCashFlowReport::index');
	$routes->add('(:segment)', '\App\Modules\PersonalCashFlowReport\Controllers\PersonalCashFlowReport::$1');
	$routes->add('(:segment)/(:any)', '\App\Modules\PersonalCashFlowReport\Controllers\PersonalCashFlowReport::$1/$2');
});
