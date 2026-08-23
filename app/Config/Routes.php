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
$routes->get('go/jadwal-banmus/(:num)/live',   'RedirectController::liveBanmus/$1');
$routes->get('go/jadwal-banmus/(:num)/berkas', 'RedirectController::berkasBanmus/$1');
$routes->get('go/jadwal-umum/(:num)/live',      'RedirectController::liveGeneral/$1');
$routes->get('go/jadwal-umum/(:num)/berkas',    'RedirectController::berkasGeneral/$1');

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
    $routes->get('jadwal-banmus/(:num)/live',   'Member\ScheduleLinkController::liveBanmus/$1');
    $routes->get('jadwal-banmus/(:num)/berkas', 'Member\ScheduleLinkController::berkasBanmus/$1');
    $routes->get('jadwal-banmus/(:num)/undangan', 'Member\ScheduleInvitationController::banmus/$1');
    $routes->get('jadwal-umum/(:num)/live',       'Member\ScheduleLinkController::liveGeneral/$1');
    $routes->get('jadwal-umum/(:num)/berkas',     'Member\ScheduleLinkController::berkasGeneral/$1');
    $routes->get('jadwal-umum/(:num)/undangan',   'Member\ScheduleInvitationController::general/$1');
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

    // Agenda Internal: Banmus
    $routes->get( 'jadwal-banmus',                                 'Admin\JadwalBanmusController::index');
    $routes->get( 'jadwal-banmus/create',                          'Admin\JadwalBanmusController::create');
    $routes->post('jadwal-banmus/store',                           'Admin\JadwalBanmusController::store');
    $routes->get( 'jadwal-banmus/(:num)',                          'Admin\JadwalBanmusController::show/$1');
    $routes->get( 'jadwal-banmus/(:num)/edit',                     'Admin\JadwalBanmusController::edit/$1');
    $routes->post('jadwal-banmus/(:num)/update',                   'Admin\JadwalBanmusController::update/$1');
    $routes->post('jadwal-banmus/(:num)/delete',                   'Admin\JadwalBanmusController::delete/$1');
    $routes->post('jadwal-banmus/(:num)/item/store',               'Admin\JadwalBanmusController::storeItem/$1');
    $routes->post('jadwal-banmus/(:num)/item/(:num)/update',       'Admin\JadwalBanmusController::updateItem/$1/$2');
    $routes->post('jadwal-banmus/(:num)/item/(:num)/delete',       'Admin\JadwalBanmusController::deleteItem/$1/$2');

    // Jadwal Umum — target tunggal seluruh kegiatan non-Banmus
    $routes->get( 'jadwal-umum',                      'Admin\GeneralScheduleController::index');
    $routes->get( 'jadwal-umum/create',               'Admin\GeneralScheduleController::create');
    $routes->post('jadwal-umum/store',                'Admin\GeneralScheduleController::store');
    $routes->get( 'jadwal-umum/(:num)/edit',          'Admin\GeneralScheduleController::edit/$1');
    $routes->post('jadwal-umum/(:num)/update',        'Admin\GeneralScheduleController::update/$1');
    $routes->post('jadwal-umum/(:num)/delete',        'Admin\GeneralScheduleController::delete/$1');

    // Workspace Seluruh Agenda (Fase 5)
    $routes->get('kalender', 'Admin\AgendaWorkspaceController::kalender');

    // Pengaturan Signage
    $routes->get( 'pengaturan',              'Admin\SettingController::index');
    $routes->post('pengaturan/save',         'Admin\SettingController::save');
    $routes->post('pengaturan/media/delete', 'Admin\SettingController::deleteMedia');
    $routes->post('pengaturan/media-upload/start',  'Admin\SettingController::startMediaUpload');
    $routes->post('pengaturan/media-upload/chunk',  'Admin\SettingController::uploadMediaChunk');
    $routes->post('pengaturan/media-upload/cancel', 'Admin\SettingController::cancelMediaUpload');

    // Profil akun admin
    $routes->get( 'profile',        'Admin\ProfileController::index');
    $routes->post('profile/update', 'Admin\ProfileController::update');
});

// ── API v1 Publik (tanpa auth) ───────────────────────────────────────
$routes->group('api/v1/publik', function ($routes) {
    $routes->get('jadwal', 'Api\PublicController::jadwal');
});

$routes->group('api/v1/anggota', ['filter' => 'memberapi'], function ($routes) {
    $routes->get('jadwal', 'Api\MemberScheduleController::jadwal');
});

// ── API v1 Auth mobile (bearer token) ───────────────────────────────
$routes->group('api/v1/auth', ['namespace' => 'App\Controllers\Api\V1'], static function ($routes) {
    $routes->post('login',       'AuthController::login');
    $routes->post('otp/request', 'AuthController::otpRequest');
    $routes->post('otp/verify',  'AuthController::otpVerify');
    $routes->post('logout',      'AuthController::logout');
    $routes->get('me',           'AuthController::me');
});

// ── API v1 CRUD admin (bearer token + grup admin) ──────────────────
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1', 'filter' => 'apiadmin'], static function ($routes) {
    $routes->get('ruangan',            'RuanganController::index');
    $routes->get('ruangan/(:num)',     'RuanganController::show/$1');
    $routes->post('ruangan',           'RuanganController::create');
    $routes->put('ruangan/(:num)',     'RuanganController::update/$1');
    $routes->patch('ruangan/(:num)',   'RuanganController::update/$1');
    $routes->delete('ruangan/(:num)',  'RuanganController::delete/$1');

    $routes->get('unit-rapat',            'UnitRapatController::index');
    $routes->get('unit-rapat/(:num)',     'UnitRapatController::show/$1');
    $routes->post('unit-rapat',           'UnitRapatController::create');
    $routes->put('unit-rapat/(:num)',     'UnitRapatController::update/$1');
    $routes->patch('unit-rapat/(:num)',   'UnitRapatController::update/$1');
    $routes->delete('unit-rapat/(:num)',  'UnitRapatController::delete/$1');

    $routes->get('anggota',            'AnggotaController::index');
    $routes->get('anggota/(:num)',     'AnggotaController::show/$1');
    $routes->post('anggota',           'AnggotaController::create');
    $routes->put('anggota/(:num)',     'AnggotaController::update/$1');
    $routes->patch('anggota/(:num)',   'AnggotaController::update/$1');
    $routes->delete('anggota/(:num)',  'AnggotaController::delete/$1');

    $routes->get('jadwal-umum',            'JadwalUmumController::index');
    $routes->get('jadwal-umum/(:num)',     'JadwalUmumController::show/$1');
    $routes->post('jadwal-umum',           'JadwalUmumController::create');
    $routes->put('jadwal-umum/(:num)',     'JadwalUmumController::update/$1');
    $routes->patch('jadwal-umum/(:num)',   'JadwalUmumController::update/$1');
    $routes->delete('jadwal-umum/(:num)',  'JadwalUmumController::delete/$1');
    $routes->post('jadwal-umum/(:num)/undangan',   'JadwalUmumController::storeInvitation/$1');
    $routes->delete('jadwal-umum/(:num)/undangan', 'JadwalUmumController::deleteInvitation/$1');

    $routes->get('jadwal-banmus',              'JadwalBanmusController::index');
    $routes->get('jadwal-banmus/(:num)',       'JadwalBanmusController::show/$1');
    $routes->post('jadwal-banmus',             'JadwalBanmusController::create');
    $routes->put('jadwal-banmus/(:num)',       'JadwalBanmusController::update/$1');
    $routes->patch('jadwal-banmus/(:num)',     'JadwalBanmusController::update/$1');
    $routes->delete('jadwal-banmus/(:num)',    'JadwalBanmusController::delete/$1');
    $routes->post('jadwal-banmus/(:num)/dokumen', 'JadwalBanmusController::storeDocument/$1');

    $routes->get('jadwal-banmus/(:num)/item',            'JadwalBanmusController::indexItems/$1');
    $routes->get('jadwal-banmus/(:num)/item/(:num)',     'JadwalBanmusController::showItem/$1/$2');
    $routes->post('jadwal-banmus/(:num)/item',           'JadwalBanmusController::storeItem/$1');
    $routes->put('jadwal-banmus/(:num)/item/(:num)',     'JadwalBanmusController::updateItem/$1/$2');
    $routes->patch('jadwal-banmus/(:num)/item/(:num)',   'JadwalBanmusController::updateItem/$1/$2');
    $routes->delete('jadwal-banmus/(:num)/item/(:num)',  'JadwalBanmusController::deleteItem/$1/$2');
    $routes->post('jadwal-banmus/(:num)/item/(:num)/undangan',   'JadwalBanmusController::storeItemInvitation/$1/$2');
    $routes->delete('jadwal-banmus/(:num)/item/(:num)/undangan', 'JadwalBanmusController::deleteItemInvitation/$1/$2');
});

// ── API Signage (backward compatible) ────────────────────────────────
$routes->get('api/signage/jadwal', 'Api\SignageController::jadwal');
$routes->get('api/signage/cuaca',  'Api\SignageController::cuaca');

// Webhook status provider WhatsApp (autentikasi menggunakan secret provider).
$routes->post('webhooks/otp/fazpass', 'Webhook\FazpassController::status');
