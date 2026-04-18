<?php

$routes->group('update-struktur-db', static function($routes) {
	$routes->add('/', '\App\Modules\UpdateStrukturDb\Controllers\UpdateStrukturDb::index');
	$routes->add('(:segment)', '\App\Modules\UpdateStrukturDb\Controllers\UpdateStrukturDb::$1');
});
