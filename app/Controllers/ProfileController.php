<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Block;
use App\Models\Follow;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;

final class ProfileController extends Controller
{
    /** Resolve the profile being viewed and the shared header data. */
    private function context(Request $request): array
    {
        $viewer = $this->auth($request);
        $owner  = User::findByHandle((string) $request->param('handle', ''));

        if (!$owner) {
            Response::notFound('This person is not on FaceClone, or the link is broken.');
        }

        $ownerId  = (int) $owner['id'];
        $viewerId = (int) $viewer['id'];

        if (Block::exists($ownerId, $viewerId)) {
            Response::forbidden('This content is not available right now.');
        }

        return [
            'viewer'       => $viewer,
            'owner'        => $owner,
            'isSelf'       => $ownerId === $viewerId,
            'relationship' => Friendship::status($viewerId, $ownerId),
            'isFollowing'  => Follow::exists($viewerId, $ownerId),
            'friendCount'  => Friendship::friendCount($ownerId),
            'postCount'    => Post::countBy($ownerId),
            'mutuals'      => $ownerId === $viewerId ? [] : Friendship::mutualFriends($viewerId, $ownerId, 6),
            'mutualCount'  => $ownerId === $viewerId ? 0 : Friendship::mutualCount($viewerId, $ownerId),
            'hasStory'     => Story::forUser($ownerId, $viewerId) !== [],
        ];
    }

    public function show(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/timeline', $ctx + [
            'title'      => full_name($ctx['owner']),
            'tab'        => 'posts',
            'posts'      => Post::forProfile((int) $ctx['owner']['id'], (int) $ctx['viewer']['id'], 10),
            'photos'     => Post::photosOf((int) $ctx['owner']['id'], (int) $ctx['viewer']['id'], 9),
            'friends'    => Friendship::friends((int) $ctx['owner']['id'], 9),
        ]);
    }

    public function about(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/about', $ctx + [
            'title' => 'About ' . full_name($ctx['owner']),
            'tab'   => 'about',
        ]);
    }

    public function friends(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/friends', $ctx + [
            'title'   => full_name($ctx['owner']) . "'s friends",
            'tab'     => 'friends',
            'friends' => Friendship::friends((int) $ctx['owner']['id'], 200),
        ]);
    }

    public function photos(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/photos', $ctx + [
            'title'  => full_name($ctx['owner']) . "'s photos",
            'tab'    => 'photos',
            'photos' => Post::photosOf((int) $ctx['owner']['id'], (int) $ctx['viewer']['id'], 120),
        ]);
    }

    public function groups(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/groups', $ctx + [
            'title'  => full_name($ctx['owner']) . "'s groups",
            'tab'    => 'groups',
            'groups' => Group::forUser((int) $ctx['owner']['id'], 50),
        ]);
    }

    public function followers(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/people', $ctx + [
            'title'   => 'Followers',
            'tab'     => 'friends',
            'heading' => 'Followers',
            'people'  => Follow::followers((int) $ctx['owner']['id'], 200),
            'empty'   => full_name($ctx['owner']) . ' has no followers yet.',
        ]);
    }

    public function following(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('profile/people', $ctx + [
            'title'   => 'Following',
            'tab'     => 'friends',
            'heading' => 'Following',
            'people'  => Follow::following((int) $ctx['owner']['id'], 200),
            'empty'   => full_name($ctx['owner']) . ' is not following anyone yet.',
        ]);
    }

    public function updateAvatar(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $this->storeImage($request, $user, 'avatar', Upload::AVATAR, 'Profile photo updated.');
    }

    public function updateCover(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $this->storeImage($request, $user, 'cover', Upload::COVER, 'Cover photo updated.');
    }

    private function storeImage(Request $request, array $user, string $column, string $folder, string $success): never
    {
        $file = $request->file($column) ?? $request->file('image');
        if (!$file) {
            Session::flash('error', 'Choose an image first.');
            $this->back($request, profile_url($user));
        }

        $path = Upload::image($file, $folder);
        if (!$path) {
            Session::flash('error', Upload::lastError() ?: 'That image could not be uploaded.');
            $this->back($request, profile_url($user));
        }

        User::setImage((int) $user['id'], $column, $path);
        Auth::forgetCache();
        Session::flash('success', $success);
        $this->back($request, profile_url($user));
    }

    /** The Intro box: bio, work, education, city, relationship, website. */
    public function updateIntro(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $fields = [
            'bio'          => (string) $request->input('bio', ''),
            'work'         => (string) $request->input('work', ''),
            'education'    => (string) $request->input('education', ''),
            'city'         => (string) $request->input('city', ''),
            'hometown'     => (string) $request->input('hometown', ''),
            'relationship' => (string) $request->input('relationship', 'private'),
            'website'      => (string) $request->input('website', ''),
        ];

        $validator = Validator::make($fields, [
            'bio'          => 'max:255',
            'work'         => 'max:120',
            'education'    => 'max:120',
            'city'         => 'max:120',
            'hometown'     => 'max:120',
            'relationship' => 'in:single,in_a_relationship,engaged,married,complicated,private',
            'website'      => 'url|max:190',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, profile_url($user));
        }

        User::updateProfile((int) $user['id'], $fields);
        Auth::forgetCache();
        Session::flash('success', 'Your details were saved.');
        $this->back($request, profile_url($user));
    }

    /** Name, username, birthday and gender from the About tab. */
    public function updateDetails(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $fields = [
            'first_name' => (string) $request->input('first_name', ''),
            'last_name'  => (string) $request->input('last_name', ''),
            'username'   => strtolower((string) $request->input('username', '')),
            'dob'        => (string) $request->input('dob', ''),
            'gender'     => (string) $request->input('gender', 'custom'),
        ];

        $validator = Validator::make($fields, [
            'first_name' => 'required|max:60',
            'last_name'  => 'required|max:60',
            'username'   => 'required|username',
            'dob'        => 'date',
            'gender'     => 'in:male,female,custom',
        ]);

        if ($fields['username'] !== '' && User::usernameExists($fields['username'], (int) $user['id'])) {
            $validator->fail('username', 'That username is already taken.');
        }

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, profile_url($user) . '/about');
        }

        User::updateProfile((int) $user['id'], $fields);
        Auth::forgetCache();
        Session::flash('success', 'Your profile was updated.');
        $this->redirect('/u/' . $fields['username'] . '/about');
    }

    /**
     * Deterministic initials avatar, drawn as SVG so it costs nothing to serve
     * and stays crisp at any size.
     */
    public function generatedAvatar(Request $request): void
    {
        $name = (string) $request->query('n', '');
        if ($name === '') {
            $user = User::find($request->intParam('id'));
            $name = $user ? full_name($user) : 'FaceClone';
        }

        $parts    = preg_split('/\s+/', trim($name)) ?: [''];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[count($parts) - 1] ?? '', 0, 1));
        $initials = $initials !== '' ? $initials : '?';

        $palette = ['#1877f2', '#8a3ffc', '#e4405f', '#00a67d', '#f7931e', '#0f9bd7', '#c2185b', '#5e35b1'];
        $color   = $palette[abs(crc32($name)) % count($palette)];

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="120" height="120">' .
            '<rect width="120" height="120" rx="60" fill="%s"/>' .
            '<text x="60" y="60" fill="#fff" font-family="Segoe UI,Helvetica,Arial,sans-serif" font-size="46" ' .
            'font-weight="600" text-anchor="middle" dominant-baseline="central">%s</text></svg>',
            $color,
            htmlspecialchars($initials, ENT_QUOTES)
        );

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: public, max-age=604800');
        echo $svg;
        exit;
    }
}
