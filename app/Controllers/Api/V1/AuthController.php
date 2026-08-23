<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Libraries\Api\ApiResponse;
use App\Libraries\Auth\MemberAccountService;
use App\Libraries\Otp\MemberOtpThrottle;
use App\Libraries\Otp\OtpService;
use App\Libraries\PhoneNumberService;
use App\Libraries\Security\AdminLoginThrottle;
use App\Models\AnggotaModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserIdentityModel;
use Config\Otp as OtpConfig;

/**
 * Endpoint autentikasi aplikasi mobile: login admin (username+password)
 * dan login anggota (OTP WhatsApp Fazpass), keduanya menukar kredensial
 * dengan bearer token Shield.
 */
class AuthController extends BaseController
{
    use ApiResponse;

    private const DUMMY_PASSWORD_HASH = '$2y$10$ffiPtSZb76eMM.xQCPAsUOshAStdyZLCoGmebeOjgZcrqi5OQhdwy';
    private const DUMMY_OTP_HASH = '$2y$10$4RbmKauQYgMBcef3l.0pZ.A2OC8LIa1DSANAIiWfeBUMhhEB6vwfq';

    /**
     * POST api/v1/auth/login — login admin, menukar username+password
     * dengan bearer token.
     */
    public function login(): ResponseInterface
    {
        $username = trim((string) $this->input('username'));
        $password = (string) $this->input('password');
        $ipAddress = $this->request->getIPAddress();
        $throttle = new AdminLoginThrottle();

        if (! $throttle->allows($username, $ipAddress)) {
            log_message('notice', 'Login admin API dibatasi: username_hash={username_hash}, ip_hash={ip_hash}', [
                'username_hash' => $throttle->usernameFingerprint($username),
                'ip_hash'       => hash('sha256', $ipAddress),
            ]);

            return $this->apiError('Terlalu banyak percobaan login. Silakan tunggu beberapa saat.', 429);
        }

        $model = new UserModel();
        $user = $username !== ''
            ? $model->withIdentities()->withGroups()->where('username', $username)->first()
            : null;

        $identity = $user instanceof User ? $user->getEmailIdentity() : null;
        $storedHash = (string) ($identity?->secret2 ?? self::DUMMY_PASSWORD_HASH);
        $passwordValid = password_verify($password, $storedHash);

        if ($user instanceof User && $passwordValid && $user->isActivated()
            && $user->inGroup('superadmin', 'operator')) {
            $throttle->clearUsername($username);

            return $this->apiSuccess($this->tokenPayload($user, 'Login berhasil.'));
        }

        log_message('notice', 'Login admin API gagal: username_hash={username_hash}, ip_hash={ip_hash}', [
            'username_hash' => $throttle->usernameFingerprint($username),
            'ip_hash'       => hash('sha256', $ipAddress),
        ]);

        return $this->apiError('Username atau password tidak sesuai.', 401);
    }

    /**
     * POST api/v1/auth/otp/request — kirim OTP WhatsApp ke nomor anggota.
     * Respons selalu generik agar status pendaftaran nomor tidak bocor.
     */
    public function otpRequest(): ResponseInterface
    {
        $phone = $this->localPhone((string) $this->input('no_wa'));
        $ip = $this->request->getIPAddress();

        $throttle = new MemberOtpThrottle();
        if (! $throttle->allows($phone === null ? null : $this->phoneHash($phone), $ip)) {
            return $this->apiError('Terlalu banyak permintaan. Silakan coba kembali beberapa saat lagi.', 429);
        }

        $account = $phone !== null ? (new AnggotaModel())->findLoginByPhone($phone) : null;
        $eligible = $account !== null && (int) $account['aktif'] === 1;

        $config = new OtpConfig();
        $retryAfter = $config->resendCooldownSeconds;

        if ($eligible) {
            $result = (new OtpService())->request((int) $account['anggota_id'], '62' . $phone);
            $retryAfter = max(1, (int) ($result->retryAfter ?? $config->resendCooldownSeconds));
        } else {
            // Samakan biaya verifikasi agar nomor tidak mudah ditebak.
            password_verify('000000', self::DUMMY_OTP_HASH);
        }

        return $this->apiSuccess([
            'message'     => 'Jika nomor terdaftar dan dapat menerima WhatsApp, kode OTP akan segera dikirim.',
            'retry_after' => $retryAfter,
        ]);
    }

    /**
     * POST api/v1/auth/otp/verify — verifikasi kode OTP anggota lalu
     * terbitkan bearer token.
     */
    public function otpVerify(): ResponseInterface
    {
        $phone = $this->localPhone((string) $this->input('no_wa'));
        $code = trim((string) $this->input('otp'));

        $account = $phone !== null ? (new AnggotaModel())->findLoginByPhone($phone) : null;
        $eligible = $account !== null && (int) $account['aktif'] === 1;

        $verified = null;
        if ($eligible) {
            $verified = (new OtpService())->verify((int) $account['anggota_id'], $code);
        } else {
            password_verify($code !== '' ? $code : '000000', self::DUMMY_OTP_HASH);
        }

        if ($verified?->success !== true) {
            if (($verified?->status ?? null) === 'too_many_attempts') {
                return $this->apiError('Terlalu banyak percobaan. Silakan minta kode OTP baru.', 429);
            }

            return $this->apiError('Kode OTP tidak valid atau sudah kedaluwarsa.', 401);
        }

        $user = (new MemberAccountService())->ensureUserForAnggota((int) $account['anggota_id']);
        if ($user === null) {
            return $this->apiError('Akun anggota tidak dapat disiapkan. Hubungi administrator.', 500);
        }

        (new AnggotaModel())->update((int) $account['anggota_id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->apiSuccess($this->tokenPayload($user, 'Login berhasil.'));
    }

    /**
     * POST api/v1/auth/logout — cabut bearer token yang sedang dipakai.
     */
    public function logout(): ResponseInterface
    {
        $user = service('requestIdentity')->currentUser();
        if ($user === null) {
            return $this->apiUnauthorized();
        }

        $rawToken = $this->bearerToken();
        if ($rawToken !== null) {
            $identities = model(UserIdentityModel::class);
            $token = $identities->getAccessTokenByRawToken($rawToken);
            if ($token !== null) {
                $identities->delete($token->id);
            }
        }

        return $this->apiSuccess(['message' => 'Logout berhasil.']);
    }

    /**
     * GET api/v1/me — identitas pemilik token beserta profilnya.
     */
    public function me(): ResponseInterface
    {
        $user = service('requestIdentity')->currentUser();
        if ($user === null) {
            return $this->apiUnauthorized();
        }

        return $this->apiSuccess(['user' => $this->userPayload($user)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenPayload(User $user, string $message): array
    {
        $scopes = $user->inGroup('anggota')
            ? ['agenda.read', 'resource.read']
            : ['*'];

        $token = $user->generateAccessToken($this->deviceName(), $scopes);

        return [
            'message'      => $message,
            'token_type'   => 'Bearer',
            'access_token' => $token->raw_token,
            'user'         => $this->userPayload($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $payload = [
            'id'       => (int) $user->id,
            'username' => (string) $user->username,
            'name'     => (string) $user->name,
            'groups'   => $user->getGroups() ?? [],
        ];

        if ($user->inGroup('anggota')) {
            $anggota = (new AnggotaModel())->where('user_id', $user->id)->first();
            if ($anggota !== null) {
                $payload['anggota'] = [
                    'id'      => (int) $anggota['id'],
                    'jabatan' => $anggota['jabatan'],
                    'fraksi'  => $anggota['fraksi'],
                    'komisi'  => $anggota['komisi'],
                ];
            }
        }

        return $payload;
    }

    private function deviceName(): string
    {
        $device = preg_replace('/[^A-Za-z0-9 _.\-]/', '', (string) $this->input('device'));

        return substr(trim((string) $device), 0, 64) ?: 'mobile';
    }

    private function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');

        return str_starts_with($header, 'Bearer ')
            ? trim(substr($header, 7))
            : null;
    }

    private function localPhone(string $phone): ?string
    {
        $normalized = PhoneNumberService::normalizeIndonesia($phone);
        $localPhone = str_starts_with($normalized, '62') ? substr($normalized, 2) : '';

        return preg_match('/^8\d{7,11}$/', $localPhone) === 1 ? $localPhone : null;
    }

    private function phoneHash(?string $phone): string
    {
        return hash('sha256', $phone === null ? 'invalid-member-phone' : '62' . $phone);
    }
}
