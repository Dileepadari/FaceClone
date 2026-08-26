<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Block;
use App\Models\User;
use App\Models\UserSetting;

final class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        $this->auth($request);
        $this->redirect('/settings/account');
    }

    public function account(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('settings/account', [
            'title'   => 'Account settings',
            'section' => 'account',
        ]);
    }

    public function updateAccount(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $fields = [
            'first_name' => (string) $request->input('first_name', ''),
            'last_name'  => (string) $request->input('last_name', ''),
            'username'   => strtolower((string) $request->input('username', '')),
            'email'      => strtolower((string) $request->input('email', '')),
        ];

        $validator = Validator::make($fields, [
            'first_name' => 'required|max:60',
            'last_name'  => 'required|max:60',
            'username'   => 'required|username',
            'email'      => 'required|email|max:190',
        ]);

        if ($fields['username'] !== '' && User::usernameExists($fields['username'], (int) $user['id'])) {
            $validator->fail('username', 'That username is already taken.');
        }
        if ($fields['email'] !== '' && User::emailExists($fields['email'], (int) $user['id'])) {
            $validator->fail('email', 'Another account already uses that email address.');
        }

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/settings/account');
        }

        User::updateProfile((int) $user['id'], $fields);
        User::updateEmail((int) $user['id'], $fields['email']);
        Auth::forgetCache();

        Session::flash('success', 'Your account details were saved.');
        $this->redirect('/settings/account');
    }

    public function updatePassword(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $current = (string) $request->raw('current_password', '');
        $next    = (string) $request->raw('password', '');

        $validator = Validator::make([
            'current_password' => $current,
            'password'         => $next,
            'password_confirm' => $request->raw('password_confirm', ''),
        ], [
            'current_password' => 'required',
            'password'         => 'required|min:8|max:100',
            'password_confirm' => 'required|matches:password',
        ], [
            'current_password' => 'Current password',
            'password'         => 'New password',
            'password_confirm' => 'Password confirmation',
        ]);

        if (!password_verify($current, $user['password_hash'])) {
            $validator->fail('current_password', 'Your current password is not correct.');
        }

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/settings/account');
        }

        User::updatePassword((int) $user['id'], $next);
        Session::flash('success', 'Your password was changed.');
        $this->redirect('/settings/account');
    }

    public function privacy(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('settings/privacy', [
            'title'    => 'Privacy settings',
            'section'  => 'privacy',
            'settings' => UserSetting::forUser((int) $user['id']),
        ]);
    }

    public function updatePrivacy(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        UserSetting::update((int) $user['id'], [
            'default_privacy' => $this->pick($request->input('default_privacy'), ['public', 'friends', 'only_me'], 'friends'),
            'who_can_friend'  => $this->pick($request->input('who_can_friend'), ['everyone', 'friends_of_friends'], 'everyone'),
            'who_can_message' => $this->pick($request->input('who_can_message'), ['everyone', 'friends'], 'everyone'),
            'show_online'     => $request->bool('show_online') ? 1 : 0,
        ]);

        Session::flash('success', 'Privacy settings saved.');
        $this->redirect('/settings/privacy');
    }

    public function notifications(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('settings/notifications', [
            'title'    => 'Notification settings',
            'section'  => 'notifications',
            'settings' => UserSetting::forUser((int) $user['id']),
        ]);
    }

    public function updateNotifications(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        UserSetting::update((int) $user['id'], [
            'notify_reactions' => $request->bool('notify_reactions') ? 1 : 0,
            'notify_comments'  => $request->bool('notify_comments')  ? 1 : 0,
            'notify_friends'   => $request->bool('notify_friends')   ? 1 : 0,
            'notify_messages'  => $request->bool('notify_messages')  ? 1 : 0,
            'notify_groups'    => $request->bool('notify_groups')    ? 1 : 0,
        ]);

        Session::flash('success', 'Notification settings saved.');
        $this->redirect('/settings/notifications');
    }

    public function appearance(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('settings/appearance', [
            'title'    => 'Appearance',
            'section'  => 'appearance',
            'settings' => UserSetting::forUser((int) $user['id']),
        ]);
    }

    public function updateAppearance(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $theme = $this->pick($request->input('theme'), ['light', 'dark', 'system'], 'light');
        UserSetting::update((int) $user['id'], ['theme' => $theme]);

        if ($request->isAjax()) {
            $this->ok(['theme' => $theme]);
        }
        Session::flash('success', 'Appearance saved.');
        $this->redirect('/settings/appearance');
    }

    public function blocking(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('settings/blocking', [
            'title'   => 'Blocking',
            'section' => 'blocking',
            'blocked' => Block::listFor((int) $user['id']),
        ]);
    }

    public function deactivate(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        if (!password_verify((string) $request->raw('password', ''), $user['password_hash'])) {
            Session::flash('error', 'Enter your password to deactivate your account.');
            $this->redirect('/settings/account');
        }

        User::deactivate((int) $user['id']);
        Auth::logout();

        Session::start();
        Session::flash('success', 'Your account is deactivated. Log in again at any time to restore it.');
        $this->redirect('/login');
    }

    public function deleteAccount(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        if (!password_verify((string) $request->raw('password', ''), $user['password_hash'])) {
            Session::flash('error', 'Enter your password to delete your account.');
            $this->redirect('/settings/account');
        }
        if ((string) $request->input('confirm', '') !== 'DELETE') {
            Session::flash('error', 'Type DELETE to confirm that you want to remove your account.');
            $this->redirect('/settings/account');
        }

        $id = (int) $user['id'];
        Auth::logout();
        User::delete($id);

        Session::start();
        Session::flash('success', 'Your account and all of its content were permanently deleted.');
        $this->redirect('/register');
    }

    /** Constrain a submitted value to a known set. */
    private function pick(mixed $value, array $allowed, string $default): string
    {
        $value = (string) $value;
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
