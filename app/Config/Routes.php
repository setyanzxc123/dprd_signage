<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home
$routes->get('/', 'AgendaController::root');

// Agenda web responsif untuk publik dan anggota
$routes->get('agenda', 'AgendaController::index');
$routes->get('agenda/jadwal-banmus', 'AgendaController::banmus');
$routes->get('agenda/jadwal-banmus/(:num)/dokumen', 'AgendaController::banmusDocument/$1');

// Layar TV Signage
$routes->get('signage', 'SignageController::index');

// Portal Publik — akses via QR Code / direct link
$routes->get('jadwal', 'AgendaController::legacy');

// Redirect QR Code signage — mengarahkan ke URL asli dari jadwal publik tertentu
$routes->get('go/jadwal/(:num)/live',   'RedirectController::live/$1');
$routes->get('go/jadwal/(:num)/berkas', 'RedirectController::berkas/$1');

// Login satu pintu
$routes->get( 'login',         'LoginController::index');
$routes->post('login/admin',   'Admin\AuthController::loginProcess');
$routes->group('login/anggota', static function ($routes) {
    $routes->post('',            'Member\AuthController::requestOtp');
    $routes->post('verifikasi',  'Member\AuthController::verifyOtp');
    $routes->post('kirim-ulang', 'Member\AuthController::resendOtp');
    $routes->post('reset',       'Member\AuthController::resetOtp');
});

// Alias auth lama untuk kompatibilitas
$routes->get( 'admin/login',  'Admin\AuthController::loginPage');
$routes->post('admin/logout', 'Admin\AuthController::logout');

// Auth anggota DPRD (terpisah dari panel admin)
$routes->get( 'anggota/login',  'Member\AuthController::loginPage');
$routes->post('anggota/logout', 'Member\AuthController::logout');

// Portal anggota DPRD
$routes->group('anggota', ['filter' => 'memberauth'], function ($routes) {
    $routes->get('', 'Member\PortalController::index');
    $routes->get('jadwal/(:num)/live',   'Member\ScheduleLinkController::live/$1');
    $routes->get('jadwal/(:num)/berkas', 'Member\ScheduleLinkController::berkas/$1');
});

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
    $routes->post('anggota/(:num)/otp-darurat', 'Admin\MemberController::emergencyOtp/$1');
    $routes->post('anggota/(:num)/delete',  'Admin\MemberController::delete/$1');

    // Master Data: Ruangan Rapat
    $routes->get( 'ruangan',               'Admin\RoomController::index');
    $routes->get( 'ruangan/create',        'Admin\RoomController::create');
    $routes->post('ruangan/store',         'Admin\RoomController::store');
    $routes->get( 'ruangan/(:num)/edit',   'Admin\RoomController::edit/$1');
    $routes->post('ruangan/(:num)/update', 'Admin\RoomController::update/$1');
    $routes->post('ruangan/(:num)/delete', 'Admin\RoomController::delete/$1');

    // Master Data: Kelompok Peserta
    $routes->get( 'unit-rapat',               'Admin\UnitRapatController::index');
    $routes->get( 'unit-rapat/create',        'Admin\UnitRapatController::create');
    $routes->post('unit-rapat/store',         'Admin\UnitRapatController::store');
    $routes->get( 'unit-rapat/(:num)/edit',   'Admin\UnitRapatController::edit/$1');
    $routes->post('unit-rapat/(:num)/update', 'Admin\UnitRapatController::update/$1');
    $routes->post('unit-rapat/(:num)/delete', 'Admin\UnitRapatController::delete/$1');

    // Jadwal Rapat
    $routes->get( 'jadwal',                      'Admin\MeetingController::index');
    $routes->get( 'jadwal/create',               'Admin\MeetingController::create');
    $routes->post('jadwal/store',                'Admin\MeetingController::store');
    $routes->get( 'jadwal/(:num)/edit',          'Admin\MeetingController::edit/$1');
    $routes->post('jadwal/(:num)/update',        'Admin\MeetingController::update/$1');
    $routes->post('jadwal/(:num)/delete',        'Admin\MeetingController::delete/$1');

    // Jadwal Umum / kegiatan nonrapat
    $routes->get( 'agenda-umum',                      'Admin\GeneralAgendaController::index');
    $routes->get( 'agenda-umum/create',               'Admin\GeneralAgendaController::create');
    $routes->post('agenda-umum/store',                'Admin\GeneralAgendaController::store');
    $routes->get( 'agenda-umum/(:num)/edit',          'Admin\GeneralAgendaController::edit/$1');
    $routes->post('agenda-umum/(:num)/update',        'Admin\GeneralAgendaController::update/$1');
    $routes->post('agenda-umum/(:num)/delete',        'Admin\GeneralAgendaController::delete/$1');

    // Jadwal Semester / proyeksi SK Banmus
    $routes->get( 'jadwal-banmus',                      'Admin\BanmusProjectionController::index');
    $routes->get( 'jadwal-banmus/create',               'Admin\BanmusProjectionController::create');
    $routes->post('jadwal-banmus/store',                'Admin\BanmusProjectionController::store');
    $routes->get( 'jadwal-banmus/(:num)/edit',          'Admin\BanmusProjectionController::edit/$1');
    $routes->post('jadwal-banmus/(:num)/update',        'Admin\BanmusProjectionController::update/$1');
    $routes->post('jadwal-banmus/(:num)/delete',        'Admin\BanmusProjectionController::delete/$1');

    // Pengaturan Signage
    $routes->get( 'pengaturan',              'Admin\SettingController::index');
    $routes->post('pengaturan/save',         'Admin\SettingController::save');
    $routes->post('pengaturan/media/delete', 'Admin\SettingController::deleteMedia');

    // Profil akun admin
    $routes->get( 'profile',        'Admin\ProfileController::index');
    $routes->post('profile/update', 'Admin\ProfileController::update');
});

// ── API v1 Publik (tanpa auth) ───────────────────────────────────────
$routes->group('api/v1/publik', function ($routes) {
    $routes->get('jadwal', 'Api\PublicController::jadwal');
    $routes->get('agenda-umum', 'Api\PublicController::agendaUmum');
});

$routes->group('api/v1/anggota', ['filter' => 'memberapi'], function ($routes) {
    $routes->get('jadwal', 'Api\MemberScheduleController::jadwal');
});

// ── API Signage (backward compatible) ────────────────────────────────
$routes->get('api/signage/jadwal', 'Api\SignageController::jadwal');
$routes->get('api/signage/cuaca',  'Api\SignageController::cuaca');

// Webhook status provider WhatsApp (autentikasi menggunakan secret provider).
$routes->post('webhooks/otp/fazpass', 'Webhook\FazpassController::status');
