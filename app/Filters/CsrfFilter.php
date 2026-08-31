<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

class CsrfFilter implements FilterInterface
{
    private const FRIENDLY_MESSAGE = 'Sesi formulir telah berakhir. Muat ulang halaman lalu ulangi tindakan Anda.';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        try {
            service('security')->verify($request);
        } catch (SecurityException $e) {
            return $this->handleFailure($request);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function handleFailure(IncomingRequest $request): ResponseInterface
    {
        log_message('warning', 'CSRF verification failed on "{path}" from IP {ip}.', [
            'path' => $request->getUri()->getPath(),
            'ip'   => $request->getIPAddress(),
        ]);

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => false,
                    'message' => self::FRIENDLY_MESSAGE,
                    'csrf'    => ['name' => csrf_token(), 'hash' => csrf_hash()],
                ]);
        }

        if ($request->getUri()->getSegment(1) === 'login') {
            return redirect()
                ->to($this->loginUrl())
                ->with('auth_form_error', self::FRIENDLY_MESSAGE);
        }

        return redirect()->back()->with('error', self::FRIENDLY_MESSAGE);
    }

    private function loginUrl(): string
    {
        $akses = str_contains(previous_url() ?? '', 'akses=admin') ? 'admin' : 'anggota';

        return base_url('login?akses=' . $akses);
    }
}
