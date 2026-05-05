<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function loginPage(): string
    {
        if (session()->get('auth_user')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        return view('admin/auth/login', ['pageTitle' => 'Login Admin']);
    }

    public function loginProcess()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $model = new UserModel();
        $user  = $model->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            session()->set('auth_user', [
                'id'   => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
            ]);
            return redirect()->to(base_url('admin/dashboard'));
        }

        session()->setFlashdata('error', 'Email atau password salah.');
        return redirect()->to(base_url('admin/login'));
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('admin/login'));
    }
}
