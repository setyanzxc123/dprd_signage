<?php

namespace App\Libraries\Auth;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

/**
 * Validasi dan penyimpanan profil admin (nama + ganti password) —
 * dipakai bersama halaman web admin dan API mobile agar aturannya
 * hanya punya satu rumah. Hash password dibuat Shield saat identitas
 * email_password disimpan.
 */
final class AdminProfileService
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 72;

    /**
     * Validasi payload profil.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed> ['error' => pesan] atau payload
     *                               ternormalisasi ['name', 'password', 'password_changed']
     */
    public function validatedProfile(array $input, User $user): array
    {
        $name                 = trim((string) ($input['name'] ?? ''));
        $currentPassword      = (string) ($input['current_password'] ?? '');
        $newPassword          = (string) ($input['new_password'] ?? '');
        $passwordConfirmation = (string) ($input['new_password_confirmation'] ?? '');

        $error           = $this->validateName($name);
        $passwordChanged = $currentPassword !== ''
            || $newPassword !== ''
            || $passwordConfirmation !== '';

        if ($error === null && $passwordChanged) {
            $error = $this->validatePasswordChange(
                $user,
                $currentPassword,
                $newPassword,
                $passwordConfirmation,
            );
        }

        if ($error !== null) {
            return ['error' => $error];
        }

        return [
            'name'             => $name,
            'password'         => $passwordChanged ? $newPassword : null,
            'password_changed' => $passwordChanged,
        ];
    }

    /**
     * @param array<string, mixed> $payload hasil validatedProfile
     */
    public function persistProfile(User $user, array $payload): bool
    {
        $user->name = $payload['name'];
        if ($payload['password'] !== null) {
            $user->password = $payload['password'];
        }

        return (bool) (new UserModel())->save($user);
    }

    private function validateName(string $name): ?string
    {
        $length = mb_strlen($name);

        if ($length < 3 || $length > 100) {
            return 'Nama admin harus terdiri dari 3 sampai 100 karakter.';
        }

        return null;
    }

    private function validatePasswordChange(
        User $user,
        string $currentPassword,
        string $newPassword,
        string $passwordConfirmation,
    ): ?string {
        if ($currentPassword === '' || $newPassword === '' || $passwordConfirmation === '') {
            return 'Semua kolom password wajib diisi.';
        }

        $currentHash = $this->passwordHash($user);

        if (! password_verify($currentPassword, $currentHash)) {
            return 'Password saat ini tidak sesuai.';
        }

        $length = mb_strlen($newPassword);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return sprintf(
                'Password baru harus terdiri dari %d sampai %d karakter.',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
            );
        }

        if (! hash_equals($newPassword, $passwordConfirmation)) {
            return 'Konfirmasi password baru tidak sesuai.';
        }

        if (password_verify($newPassword, $currentHash)) {
            return 'Password baru harus berbeda dari password saat ini.';
        }

        return null;
    }

    private function passwordHash(User $user): string
    {
        $identity = $user->getEmailIdentity();

        return (string) ($identity->secret2 ?? '');
    }
}
