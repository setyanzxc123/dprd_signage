<?php

namespace App\Controllers;

use App\Libraries\Otp\OtpPendingSession;

class LoginController extends BaseController
{
    public function index()
    {
        if (session()->has('auth_user')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        if (session()->has('member_auth')) {
            return redirect()->to(base_url('agenda'));
        }

        $access = strtolower(trim((string) $this->request->getGet('akses')));
        if (! in_array($access, ['anggota', 'admin'], true)) {
            $access = 'anggota';
        }

        $pendingSession = new OtpPendingSession();
        $pending = $pendingSession->get();
        $appSession = session();

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private')
            ->setHeader('Pragma', 'no-cache')
            ->setBody(view('auth/login', [
                'pageTitle'        => 'Masuk Sistem DPRD',
                'access'           => $access,
                'member_step'      => is_array($pending) ? 'verify' : 'request',
                'masked_phone'     => is_array($pending) ? ($pending['masked'] ?? '') : '',
                'retry_after'      => is_array($pending) ? $pendingSession->retryAfter($pending) : 0,
                'form_error'       => $appSession->getFlashdata('auth_form_error'),
                'otp_success'      => $appSession->getFlashdata('member_otp_success'),
                'old_phone'        => $appSession->getFlashdata('auth_old_phone'),
                'old_username'     => $appSession->getFlashdata('auth_old_username'),
                'flash_success'    => $appSession->getFlashdata('success'),
            ]));
    }
}
