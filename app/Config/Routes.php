<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultController('Login');

$routes->get('/', 'Login::index');
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::index');

// Payment provider webhook routes (no authentication required)
$routes->post('payments/webhook/(:segment)', 'Payments\Webhook::handle/$1');
$routes->get('payments/status/(:segment)/(:segment)', 'Payments\Webhook::status/$1/$2');

$routes->add('no_access/index/(:segment)', 'No_access::index/$1');
$routes->add('no_access/index/(:segment)/(:segment)', 'No_access::index/$1/$2');