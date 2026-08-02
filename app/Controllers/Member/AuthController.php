<?php

namespace App\Controllers\Member;

use App\Controllers\BaseController;
use App\Libraries\Otp\OtpPendingSession;
use App\Libraries\Otp\OtpService;
use App\Libraries\PhoneNumberService;
use App\Models\MemberAccountModel;
use Config\Otp;

class AuthController extends BaseController
{
    private const GENERIC_REQUEST_MESSAGE = 'Jika nomor terdaftar dan dapat menerima WhatsApp, kode OTP akan segera dikirim.';
    private const DUMMY_OTP_HASH = '$2y$10$4RbmKauQYgMBcef3l.0pZ.A2OC8LIa1DSANAIiWfeBUMhhEB6vwfq';

    public function loginPage()
    {
        if (session()->has('member_auth')) {
            return redirect()->to(base_url('agenda'));
        }

        return redirect()->to(base_url('login?akses=anggota'));
    }

    public function requestOtp()
    {
        if ($redirect = $this->authenticatedRedirect()) {
            return $redirect;
        }

        $phone = $this->localPhone((string) $this->request->getPost('no_wa'));
        $ip = $this->request->getIPAddress();
        if (! $this->allowRequest($phone, $ip)) {
            return $this->requestFailure('Terlalu banyak permintaan. Silakan coba kembali beberapa saat lagi.');
        }

        $account = $phone !== null ? (new MemberAccountModel())->findLoginByPhone($phone) : null;
        $eligible = $account !== null && (int) $account['aktif'] === 1;

        $config = new Otp();
        $retryAfter = $config->resendCooldownSeconds;
        $otpExpiresAt = time() + $config->ttlSeconds;
        if ($eligible) {
            $result = (new OtpService())->request(
                (int) $account['account_id'],
                '62' . $phone,
                $ip,
            );
            $retryAfter = max(1, (int) ($result->retryAfter ?? $config->resendCooldownSeconds));
            $otpExpiresAt = $result->expiresAt ?? $otpExpiresAt;
        } else {
            // Menyamakan biaya minimum agar status nomor tidak mudah ditebak.
            password_verify('000000', self::DUMMY_OTP_HASH);
        }

        (new OtpPendingSession())->begin(
            $eligible ? (int) $account['account_id'] : 0,
            $eligible ? (int) $account['anggota_id'] : 0,
            $this->phoneHash($phone),
            $this->maskPhone($phone),
            $retryAfter,
            $otpExpiresAt,
        );

        return $this->verificationRedirect(success: self::GENERIC_REQUEST_MESSAGE);
    }

    public function verifyOtp()
    {
        if ($redirect = $this->authenticatedRedirect()) {
            return $redirect;
        }

        $pendingSession = new OtpPendingSession();
        $pending = $pendingSession->get();
        if (! is_array($pending)) {
            return $this->requestFailure('Sesi verifikasi berakhir. Silakan minta kode baru.');
        }

        $code = trim((string) $this->request->getPost('otp'));
        $accountId = (int) ($pending['account_id'] ?? 0);
        $account = $accountId > 0 ? $this->currentPendingAccount($pending) : null;
        $currentPhone = $account !== null ? $this->localPhone((string) $account['no_wa']) : null;
        if ($accountId > 0 && ($account === null || $currentPhone === null)) {
            $pendingSession->forget();

            return $this->requestFailure('Data akun berubah atau sudah tidak aktif. Silakan masukkan nomor kembali.');
        }

        $verified = $account !== null
            ? (new OtpService())->verify(
                $accountId,
                $code,
                $this->request->getIPAddress(),
                '62' . $currentPhone,
            )
            : null;
        if ($account === null) {
            password_verify($code, self::DUMMY_OTP_HASH);
        }

        if ($verified?->success !== true) {
            $status = $verified?->status;
            $message = $status === 'too_many_attempts'
                ? 'Terlalu banyak percobaan. Silakan minta kode OTP baru.'
                : 'Kode OTP tidak valid atau sudah kedaluwarsa.';

            return $this->verificationRedirect(error: $message);
        }

        session()->remove('auth_user');
        $pendingSession->forget();
        session()->regenerate(true);
        session()->set('member_auth', [
            'account_id' => (int) $account['account_id'],
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => $account['name'],
        ]);
        (new MemberAccountModel())->update($accountId, ['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(base_url('agenda'), 303);
    }

    public function resendOtp()
    {
        if ($redirect = $this->authenticatedRedirect()) {
            return $redirect;
        }

        $pendingSession = new OtpPendingSession();
        $pending = $pendingSession->get();
        if (! is_array($pending)) {
            return $this->requestFailure('Sesi verifikasi berakhir. Silakan masukkan nomor kembali.');
        }

        $retryAt = (int) ($pending['retry_at'] ?? 0);
        if ($retryAt > time()) {
            return $this->verificationRedirect(
                error: 'Kode belum dapat dikirim ulang. Tunggu hingga hitung mundur selesai.',
            );
        }

        $accountId = (int) ($pending['account_id'] ?? 0);
        $config = new Otp();
        $retryAfter = $config->resendCooldownSeconds;
        $otpExpiresAt = time() + $config->ttlSeconds;
        $ip = $this->request->getIPAddress();
        if (! $this->allowPendingRequest($pending, $ip)) {
            $pendingSession->refresh(
                $pending,
                $config->resendCooldownSeconds,
                (int) ($pending['otp_expires_at'] ?? time()),
            );

            return $this->verificationRedirect(
                error: 'Terlalu banyak permintaan. Silakan coba kembali beberapa saat lagi.',
            );
        }

        if ($accountId > 0) {
            $account = $this->currentPendingAccount($pending);
            $phone = $account !== null ? $this->localPhone((string) $account['no_wa']) : null;
            if ($account === null || $phone === null) {
                $pendingSession->forget();

                return $this->requestFailure('Data akun berubah atau sudah tidak aktif. Silakan masukkan nomor kembali.');
            }

            $result = (new OtpService())->request(
                $accountId,
                '62' . $phone,
                $ip,
            );
            $retryAfter = max(1, (int) ($result->retryAfter ?? $config->resendCooldownSeconds));
            $otpExpiresAt = $result->expiresAt ?? (int) ($pending['otp_expires_at'] ?? $otpExpiresAt);
        }

        $pendingSession->refresh($pending, $retryAfter, $otpExpiresAt);

        return $this->verificationRedirect(success: self::GENERIC_REQUEST_MESSAGE);
    }

    public function resetOtp()
    {
        (new OtpPendingSession())->forget();

        return redirect()->to(base_url('login?akses=anggota'), 303);
    }

    public function logout()
    {
        session()->remove('member_auth');
        (new OtpPendingSession())->forget();
        session()->regenerate(true);
        session()->setFlashdata('success', 'Anda berhasil keluar dari akun anggota.');

        return redirect()->to(base_url('login?akses=anggota'), 303);
    }

    private function allowRequest(?string $phone, string $ip): bool
    {
        $config = new Otp();
        $ipAllowed = service('throttler')->check(
            'member_otp_ip_' . hash('sha256', $ip),
            $config->maxRequestsPerIp,
            $config->requestWindowSeconds,
        );
        $phoneAllowed = $phone === null
            || service('throttler')->check(
                'member_otp_phone_' . $this->phoneHash($phone),
                $config->maxRequestsPerPhone,
                $config->requestWindowSeconds,
            );

        return $ipAllowed && $phoneAllowed;
    }

    /** @param array<string, mixed> $pending */
    private function allowPendingRequest(array $pending, string $ip): bool
    {
        $config = new Otp();
        $ipAllowed = service('throttler')->check(
            'member_otp_ip_' . hash('sha256', $ip),
            $config->maxRequestsPerIp,
            $config->requestWindowSeconds,
        );
        $phoneAllowed = service('throttler')->check(
            'member_otp_phone_' . (string) ($pending['phone_hash'] ?? 'invalid'),
            $config->maxRequestsPerPhone,
            $config->requestWindowSeconds,
        );

        return $ipAllowed && $phoneAllowed;
    }

    private function localPhone(string $phone): ?string
    {
        $normalized = PhoneNumberService::normalizeIndonesia($phone);
        $localPhone = str_starts_with($normalized, '62') ? substr($normalized, 2) : '';

        return preg_match('/^8\d{7,11}$/', $localPhone) === 1 ? $localPhone : null;
    }

    private function maskPhone(?string $phone): string
    {
        if ($phone === null || strlen($phone) < 6) {
            return '+62 •••• ••••';
        }

        return '+62 ' . substr($phone, 0, 3) . '••••' . substr($phone, -3);
    }

    private function phoneHash(?string $phone): string
    {
        return hash('sha256', $phone === null ? 'invalid-member-phone' : '62' . $phone);
    }

    /** @param array<string, mixed> $pending */
    private function currentPendingAccount(array $pending): ?array
    {
        $account = (new MemberAccountModel())->findActiveSessionAccount(
            (int) ($pending['account_id'] ?? 0),
            (int) ($pending['anggota_id'] ?? 0),
        );
        if ($account === null) {
            return null;
        }

        $phone = $this->localPhone((string) $account['no_wa']);
        if ($phone === null || ! hash_equals((string) $pending['phone_hash'], $this->phoneHash($phone))) {
            return null;
        }

        return $account;
    }

    private function authenticatedRedirect()
    {
        if (session()->has('auth_user')) {
            return redirect()->to(base_url('admin/dashboard'), 303);
        }

        if (session()->has('member_auth')) {
            return redirect()->to(base_url('agenda'), 303);
        }

        return null;
    }

    private function requestFailure(string $message)
    {
        session()->setFlashdata([
            'auth_form_error' => $message,
            'auth_old_phone'  => trim((string) $this->request->getPost('no_wa')),
        ]);

        return $this->loginRedirect();
    }

    private function verificationRedirect(?string $success = null, ?string $error = null)
    {
        if ((new OtpPendingSession())->get() === null) {
            return $this->requestFailure('Sesi verifikasi berakhir. Silakan masukkan nomor kembali.');
        }

        if ($success !== null) {
            session()->setFlashdata('member_otp_success', $success);
        }
        if ($error !== null) {
            session()->setFlashdata('auth_form_error', $error);
        }

        return $this->loginRedirect();
    }

    private function loginRedirect()
    {
        return redirect()->to(base_url('login?akses=anggota'), 303);
    }
}
