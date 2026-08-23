<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * The default CORS configuration.
     *
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $default = [
        /**
         * Origins untuk header `Access-Control-Allow-Origin`.
         *
         * Konsumen utama API adalah aplikasi mobile (Flutter/native) yang tidak
         * mengirim header Origin sehingga tidak terdampak CORS. Wildcard `*`
         * dipilih agar API tetap bisa diakui dari klien browser mana pun untuk
         * pengujian/debug; aman karena autentikasi memakai bearer token di
         * header Authorization (bukan cookie — supportsCredentials=false).
         * Bila kelak ada dashboard web di origin tetap, ganti ke daftar eksplisit.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
         */
        'allowedOrigins' => ['*'],

        /**
         * Pola regex origin untuk header `Access-Control-Allow-Origin`.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
         */
        'allowedOriginsPatterns' => [],

        /**
         * Apakah mengirim header `Access-Control-Allow-Credentials`.
         *
         * Tetap false: API mobile memakai bearer token, bukan cookie sesi,
         * sehingga permintaan lintas origin tidak perlu membawa kredensial.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Credentials
         */
        'supportsCredentials' => false,

        /**
         * Header yang diizinkan pada preflight request.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
         */
        'allowedHeaders' => ['Authorization', 'Content-Type', 'Accept', 'X-Requested-With'],

        /**
         * Header respons yang diekspos ke skrip browser.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
         */
        'exposedHeaders' => [],

        /**
         * Metode HTTP yang diizinkan pada preflight request.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
         */
        'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

        /**
         * Berapa detik hasil preflight request boleh di-cache browser.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Max-Age
         */
        'maxAge' => 7200,
    ];
}
