<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home
$routes->get('/', 'SignageController::index');

// Layar TV Signage
$routes->get('signage', 'SignageController::index');

// Portal Publik — akses via QR Code / direct link
$routes->get('jadwal', 'PublicController::index');

// Redirect QR Code signage — mengarahkan ke URL asli dari jadwal publik tertentu
$routes->get('go/jadwal/(:num)/live',   'RedirectController::live/$1');
$routes->get('go/jadwal/(:num)/berkas', 'RedirectController::berkas/$1');

// Auth (login/logout, di luar group admin)
$routes->get( 'admin/login',  'Admin\AuthController::loginPage');
$routes->post('admin/login',  'Admin\AuthController::loginProcess');
$routes->get( 'admin/logout', 'Admin\AuthController::logout');

// Admin — semua route dilindungi filter auth
$routes->group('admin', ['filter' => 'auth'], function ($routes) {

    // Dashboard
    $routes->get('',          'Admin\DashboardController::index');
    $routes->get('dashboard', 'Admin\DashboardController::index');

    // Master Data: Anggota DPRD
    $routes->get( 'anggota',                'Admin\MemberController::index');
    $routes->get( 'anggota/create',         'Admin\MemberController::create');
    $routes->post('anggota/store',          'Admin\MemberController::store');
    $routes->get( 'anggota/(:num)/edit',    'Admin\MemberController::edit/$1');
    $routes->post('anggota/(:num)/update',  'Admin\MemberController::update/$1');
    $routes->get( 'anggota/(:num)/delete',  'Admin\MemberController::delete/$1');

    // Master Data: Ruangan Rapat
    $routes->get( 'ruangan',               'Admin\RoomController::index');
    $routes->get( 'ruangan/create',        'Admin\RoomController::create');
    $routes->post('ruangan/store',         'Admin\RoomController::store');
    $routes->get( 'ruangan/(:num)/edit',   'Admin\RoomController::edit/$1');
    $routes->post('ruangan/(:num)/update', 'Admin\RoomController::update/$1');
    $routes->get( 'ruangan/(:num)/delete', 'Admin\RoomController::delete/$1');

    // Jadwal Rapat
    $routes->get( 'jadwal',                      'Admin\MeetingController::index');
    $routes->get( 'jadwal/create',               'Admin\MeetingController::create');
    $routes->post('jadwal/store',                'Admin\MeetingController::store');
    $routes->get( 'jadwal/(:num)/edit',          'Admin\MeetingController::edit/$1');
    $routes->post('jadwal/(:num)/update',        'Admin\MeetingController::update/$1');
    $routes->get( 'jadwal/(:num)/delete',        'Admin\MeetingController::delete/$1');
    $routes->post('jadwal/(:num)/toggle-publik', 'Admin\MeetingController::togglePublik/$1');


    // Notifikasi WA
    $routes->get( 'notifikasi',                  'Admin\NotificationController::index');
    $routes->post('notifikasi/(:num)/resend',    'Admin\NotificationController::resend/$1');
    $routes->post('notifikasi/(:num)/cancel',    'Admin\NotificationController::cancel/$1');

    // Pengaturan Signage
    $routes->get( 'pengaturan',              'Admin\SettingController::index');
    $routes->post('pengaturan/save',         'Admin\SettingController::save');
    $routes->post('pengaturan/media/delete', 'Admin\SettingController::deleteMedia');
    $routes->post('pengaturan/wa-test',      'Admin\SettingController::waTest');
    $routes->get( 'pengaturan/wa-status',    'Admin\SettingController::waStatus');
});

// ── API v1 Publik (tanpa auth) ───────────────────────────────────────
$routes->group('api/v1/publik', function ($routes) {
    $routes->get('jadwal', 'Api\PublicController::jadwal');
});

// ── API Signage (backward compatible) ────────────────────────────────
$routes->get('api/signage/jadwal', 'Api\SignageController::jadwal');
$routes->get('api/signage/cuaca',  'Api\SignageController::cuaca');

// ── Cron HTTP trigger — dilindungi CRON_SECRET_TOKEN di .env ─────────
// Gunakan untuk: cron-job.org, cPanel, atau VPS tanpa akses Spark CLI
// Contoh: GET /cron/wa-notif?token=your_secret_token
$routes->get('cron/wa-notif', 'CronController::sendWaNotifications');
