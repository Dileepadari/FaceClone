<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\PasswordReset;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->guest();
        $this->view('auth/login', ['title' => 'Log in to FaceClone'], 'layouts/auth');
    }

    public function login(Request $request): void
    {
        $this->guest();
        $this->csrf($request);

        $identifier = (string) $request->input('email', '');
        $password   = (string) $request->raw('password', '');

        $validator = Validator::make(
            ['email' => $identifier, 'password' => $password],
            ['email' => 'required', 'password' => 'required'],
            ['email' => 'Email or username']
        );

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/login');
        }

        if (!Auth::attempt($identifier, $password, $request->bool('remember'))) {
            Session::flashInput($request->all(), ['email' => 'Those credentials do not match our records.']);
            Session::flash('error', 'The email or password you entered is incorrect.');
            $this->redirect('/login');
        }

        $intended = Session::pull('intended', '/');
        $this->redirect(is_string($intended) && str_starts_with($intended, '/') ? $intended : '/');
    }

    public function showRegister(Request $request): void
    {
        $this->guest();
        $this->view('auth/register', ['title' => 'Create a new account'], 'layouts/auth');
    }

    public function register(Request $request): void
    {
        $this->guest();
        $this->csrf($request);

        $data = [
            'first_name' => (string) $request->input('first_name', ''),
            'last_name'  => (string) $request->input('last_name', ''),
            'email'      => strtolower((string) $request->input('email', '')),
            'password'   => (string) $request->raw('password', ''),
            'dob'        => (string) $request->input('dob', ''),
            'gender'     => (string) $request->input('gender', 'custom'),
        ];

        $validator = Validator::make($data + ['password_confirm' => $request->raw('password_confirm', '')], [
            'first_name'       => 'required|max:60',
            'last_name'        => 'required|max:60',
            'email'            => 'required|email|max:190',
            'password'         => 'required|min:8|max:100',
            'password_confirm' => 'required|matches:password',
            'dob'              => 'required|date',
            'gender'           => 'required|in:male,female,custom',
        ], [
            'password_confirm' => 'Password confirmation',
            'dob'              => 'Date of birth',
        ]);

        if ($data['email'] !== '' && User::emailExists($data['email'])) {
            $validator->fail('email', 'An account already uses that email address.');
        }
        if ($data['dob'] !== '' && strtotime($data['dob']) > strtotime('-13 years')) {
            $validator->fail('dob', 'You must be at least 13 years old to join.');
        }

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/register');
        }

        $data['username'] = User::suggestUsername($data['first_name'], $data['last_name']);
        $userId = User::create($data);

        Auth::login($userId);
        Session::flash('success', 'Welcome to FaceClone. Add a photo and a few details to finish your profile.');
        $this->redirect('/');
    }

    public function showForgot(Request $request): void
    {
        $this->guest();
        $this->view('auth/forgot', [
            'title' => 'Find your account',
            'link'  => Session::pull('_reset_link'),
        ], 'layouts/auth');
    }

    public function forgot(Request $request): void
    {
        $this->guest();
        $this->csrf($request);

        $email = strtolower((string) $request->input('email', ''));
        $user  = $email !== '' ? User::findByEmailOrUsername($email) : null;

        if ($user) {
            $token = PasswordReset::issue((int) $user['id']);
            // No mail transport is configured, so the link is surfaced in the UI.
            // Wire this to a mailer in production - see DEVDOC.md.
            Session::set('_reset_link', '/reset-password?token=' . $token);
        }

        Session::flash('success', 'If an account matches that email, a reset link has been created.');
        $this->redirect('/forgot-password');
    }

    public function showReset(Request $request): void
    {
        $this->guest();
        $token = (string) $request->query('token', '');
        $row   = PasswordReset::resolve($token);

        if (!$row) {
            Session::flash('error', 'That reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }

        $this->view('auth/reset', ['title' => 'Choose a new password', 'token' => $token], 'layouts/auth');
    }

    public function reset(Request $request): void
    {
        $this->guest();
        $this->csrf($request);

        $token = (string) $request->input('token', '');
        $row   = PasswordReset::resolve($token);
        if (!$row) {
            Session::flash('error', 'That reset link is invalid or has expired.');
            $this->redirect('/forgot-password');
        }

        $validator = Validator::make([
            'password'         => $request->raw('password', ''),
            'password_confirm' => $request->raw('password_confirm', ''),
        ], [
            'password'         => 'required|min:8|max:100',
            'password_confirm' => 'required|matches:password',
        ], ['password_confirm' => 'Password confirmation']);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('/reset-password?token=' . urlencode($token));
        }

        User::updatePassword((int) $row['user_id'], (string) $request->raw('password'));
        PasswordReset::consume((int) $row['id']);

        Session::flash('success', 'Your password has been changed. You can log in now.');
        $this->redirect('/login');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
