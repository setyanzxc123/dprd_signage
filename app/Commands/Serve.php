<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Commands\Server\Serve as FrameworkServe;

/**
 * Development server that serves build assets with immutable cache headers,
 * which the framework rewrite router and the built-in server do not provide.
 * Apache serves these headers via public/assets/.htaccess instead.
 */
class Serve extends FrameworkServe
{
    public function run(array $params)
    {
        $php  = CLI::getOption('php') ?? PHP_BINARY;
        $host = CLI::getOption('host') ?? 'localhost';
        $port = (int) (CLI::getOption('port') ?? 8080) + $this->portOffset;

        CLI::write('CodeIgniter development server started on http://' . $host . ':' . $port, 'green');
        CLI::write('Press Control-C to stop.');

        passthru(
            $this->buildServeCommand($php, $host, $port, FCPATH, dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'serve_rewrite.php'),
            $status,
        );

        if ($status !== EXIT_SUCCESS && $this->portOffset < $this->tries) {
            $this->portOffset++;
            $this->run($params);
        }
    }
}
