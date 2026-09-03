<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\SecurityHeaders;

class SecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $config = config(SecurityHeaders::class);

        $response->setHeader('Permissions-Policy', $config->permissionsPolicy);

        if ($config->hstsEnabled && $this->isSecureRequest($request, $config)) {
            $value = 'max-age=' . $config->hstsMaxAge;

            if ($config->hstsIncludeSubDomains) {
                $value .= '; includeSubDomains';
            }

            if ($config->hstsPreload) {
                $value .= '; preload';
            }

            $response->setHeader('Strict-Transport-Security', $value);
        }

        if (! $response->hasHeader('Cache-Control')) {
            $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->setHeader('Pragma', 'no-cache');
        }

        return $response;
    }

    private function isSecureRequest(RequestInterface $request, SecurityHeaders $config): bool
    {
        if (method_exists($request, 'isSecure') && $request->isSecure()) {
            return true;
        }

        if (! $config->trustForwardedProto) {
            return false;
        }

        $forwardedProto = strtolower(trim(explode(',', $request->getHeaderLine('X-Forwarded-Proto'))[0]));

        return $forwardedProto === 'https';
    }
}
