<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;

final class GroupController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $id   = (int) $user['id'];

        $this->view('groups/index', [
            'title'    => 'Groups',
            'section'  => 'feed',
            'myGroups' => Group::forUser($id, 50),
            'invites'  => Group::invitesFor($id),
            'discover' => Group::discover($id, 8),
        ]);
    }

    public function discover(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('groups/discover', [
            'title'    => 'Discover groups',
            'section'  => 'discover',
            'discover' => Group::discover((int) $user['id'], 40),
            'myGroups' => Group::forUser((int) $user['id'], 50),
        ]);
    }

    public function create(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('groups/create', [
            'title'    => 'Create a group',
            'section'  => 'create',
            'myGroups' => Group::forUser((int) $user['id'], 50),
        ]);
    }

    public function store(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $fields = [
            'name'        => (string) $request->input('name', ''),
            'description' => (string) $request->input('description', ''),
            'privacy'     => (string) $request->input('privacy', 'public'),
        ];

        $validator = Validator::make($fields, [
            'name'        => 'required|min:3|max:120',
            'description' => 'max:2000',
            'privacy'     => 'required|in:public,private',
        ], ['name' => 'Group name']);

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/groups/create');
        }

        $groupId = Group::create((int) $user['id'], $fields['name'], $fields['description'], $fields['privacy']);

        $cover = $request->file('cover');
        if ($cover) {
            $path = Upload::image($cover, Upload::GROUP);
            if ($path) {
                Group::setCover($groupId, $path);
            }
        }

        $group = Group::find($groupId);
        Session::flash('success', 'Your group is ready. Invite some friends to get it going.');
        $this->redirect('/g/' . $group['slug']);
    }

    /** Load the group, the viewer's membership and whether they may read it. */
    private function context(Request $request, bool $requireMember = false): array
    {
        $user  = $this->auth($request);
        $group = Group::findByHandle((string) $request->param('handle', ''));

        if (!$group) {
            Response::notFound('This group does not exist, or the link is broken.');
        }

        $groupId    = (int) $group['id'];
        $userId     = (int) $user['id'];
        $membership = Group::membership($groupId, $userId);
        $isMember   = $membership !== null && $membership['status'] === 'member';
        $canRead    = $group['privacy'] === 'public' || $isMember;

        if ($requireMember && !$isMember) {
            Response::forbidden('Join this group to see that page.');
        }

        return [
            'viewer'      => $user,
            'group'       => $group,
            'membership'  => $membership,
            'isMember'    => $isMember,
            'isAdmin'     => Group::isAdmin($groupId, $userId),
            'isModerator' => Group::isModerator($groupId, $userId),
            'canRead'     => $canRead,
            'memberCount' => Group::memberCount($groupId),
            'pendingCount'=> Group::pendingCount($groupId),
            'title'       => $group['name'],
        ];
    }

    public function show(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('groups/show', $ctx + [
            'tab'     => 'discussion',
            'posts'   => $ctx['canRead'] ? Post::forGroup((int) $ctx['group']['id'], (int) $ctx['viewer']['id'], 15) : [],
            'members' => Group::members((int) $ctx['group']['id'], 'member', 9),
        ]);
    }

    public function about(Request $request): void
    {
        $ctx = $this->context($request);
        $this->view('groups/about', $ctx + ['tab' => 'about']);
    }

    public function members(Request $request): void
    {
        $ctx = $this->context($request);
        if (!$ctx['canRead']) {
            Response::forbidden('Join this group to see its members.');
        }
        $this->view('groups/members', $ctx + [
            'tab'     => 'members',
            'members' => Group::members((int) $ctx['group']['id'], 'member', 500),
        ]);
    }

    public function photos(Request $request): void
    {
        $ctx = $this->context($request);
        if (!$ctx['canRead']) {
            Response::forbidden('Join this group to see its photos.');
        }
        $this->view('groups/photos', $ctx + [
            'tab'    => 'photos',
            'photos' => Post::groupPhotos((int) $ctx['group']['id'], 120),
        ]);
    }

    public function requests(Request $request): void
    {
        $ctx = $this->context($request, true);
        if (!$ctx['isModerator']) {
            Response::forbidden('Only admins and moderators can review join requests.');
        }
        $this->view('groups/requests', $ctx + [
            'tab'      => 'requests',
            'requests' => Group::members((int) $ctx['group']['id'], 'pending', 200),
        ]);
    }

    public function inviteForm(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->view('groups/invite', $ctx + [
            'tab'     => 'invite',
            'friends' => Group::invitableFriends((int) $ctx['group']['id'], (int) $ctx['viewer']['id']),
            'invited' => Group::members((int) $ctx['group']['id'], 'invited', 200),
        ]);
    }

    public function settings(Request $request): void
    {
        $ctx = $this->context($request, true);
        if (!$ctx['isAdmin']) {
            Response::forbidden('Only group admins can change these settings.');
        }
        $this->view('groups/settings', $ctx + ['tab' => 'settings']);
    }

    public function updateSettings(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isAdmin']) {
            Response::forbidden('Only group admins can change these settings.');
        }

        $fields = [
            'name'        => (string) $request->input('name', ''),
            'description' => (string) $request->input('description', ''),
            'privacy'     => (string) $request->input('privacy', 'public'),
        ];

        $validator = Validator::make($fields, [
            'name'        => 'required|min:3|max:120',
            'description' => 'max:2000',
            'privacy'     => 'required|in:public,private',
        ], ['name' => 'Group name']);

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/g/' . $ctx['group']['slug'] . '/settings');
        }

        Group::update((int) $ctx['group']['id'], $fields);
        Session::flash('success', 'Group settings saved.');
        $this->redirect('/g/' . $ctx['group']['slug'] . '/settings');
    }

    public function updateCover(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isAdmin']) {
            Response::forbidden('Only group admins can change the cover photo.');
        }

        $file = $request->file('cover');
        if (!$file) {
            Session::flash('error', 'Choose an image first.');
            $this->back($request, '/g/' . $ctx['group']['slug']);
        }

        $path = Upload::image($file, Upload::GROUP);
        if (!$path) {
            Session::flash('error', Upload::lastError() ?: 'That image could not be uploaded.');
            $this->back($request, '/g/' . $ctx['group']['slug']);
        }

        Group::setCover((int) $ctx['group']['id'], $path);
        Session::flash('success', 'Cover photo updated.');
        $this->back($request, '/g/' . $ctx['group']['slug']);
    }

    public function join(Request $request): void
    {
        $ctx = $this->context($request);
        $this->csrf($request);

        $result = Group::join((int) $ctx['group']['id'], (int) $ctx['viewer']['id']);

        if ($result === 'pending') {
            foreach (Group::members((int) $ctx['group']['id']) as $member) {
                if ($member['role'] === 'admin' || $member['role'] === 'moderator') {
                    Notifier::send(
                        (int) $member['id'],
                        (int) $ctx['viewer']['id'],
                        'group_join_request',
                        'group',
                        (int) $ctx['group']['id'],
                        '/g/' . $ctx['group']['slug'] . '/requests'
                    );
                }
            }
            Session::flash('success', 'Your request to join was sent to the admins.');
        } elseif ($result === 'member') {
            Session::flash('success', 'You joined ' . $ctx['group']['name'] . '.');
        }

        $this->back($request, '/g/' . $ctx['group']['slug']);
    }

    public function leave(Request $request): void
    {
        $ctx = $this->context($request);
        $this->csrf($request);

        Group::leave((int) $ctx['group']['id'], (int) $ctx['viewer']['id']);
        Session::flash('success', 'You left ' . $ctx['group']['name'] . '.');
        $this->redirect('/groups');
    }

    public function invite(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);

        $target = User::find((int) $request->param('userId'));
        if (!$target) {
            $this->fail('That person is no longer on FaceClone.', 404);
        }

        if (Group::invite((int) $ctx['group']['id'], (int) $target['id'])) {
            Notifier::send(
                (int) $target['id'],
                (int) $ctx['viewer']['id'],
                'group_invite',
                'group',
                (int) $ctx['group']['id'],
                '/g/' . $ctx['group']['slug']
            );
        }

        if ($request->isAjax()) {
            $this->ok(['invited' => true]);
        }
        Session::flash('success', full_name($target) . ' was invited.');
        $this->back($request, '/g/' . $ctx['group']['slug'] . '/invite');
    }

    public function approve(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isModerator']) {
            Response::forbidden('Only admins and moderators can approve members.');
        }

        $userId = (int) $request->param('userId');
        if (Group::approve((int) $ctx['group']['id'], $userId)) {
            Notifier::send(
                $userId,
                (int) $ctx['viewer']['id'],
                'group_approved',
                'group',
                (int) $ctx['group']['id'],
                '/g/' . $ctx['group']['slug']
            );
        }

        Session::flash('success', 'Member approved.');
        $this->back($request, '/g/' . $ctx['group']['slug'] . '/requests');
    }

    public function reject(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isModerator']) {
            Response::forbidden('Only admins and moderators can decline requests.');
        }

        Group::reject((int) $ctx['group']['id'], (int) $request->param('userId'));
        Session::flash('success', 'Request declined.');
        $this->back($request, '/g/' . $ctx['group']['slug'] . '/requests');
    }

    public function removeMember(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isAdmin']) {
            Response::forbidden('Only group admins can remove members.');
        }

        $userId = (int) $request->param('userId');
        if ($userId === (int) $ctx['group']['creator_id']) {
            Session::flash('error', 'The group creator cannot be removed.');
            $this->back($request, '/g/' . $ctx['group']['slug'] . '/members');
        }

        Group::removeMember((int) $ctx['group']['id'], $userId);
        Session::flash('success', 'Member removed.');
        $this->back($request, '/g/' . $ctx['group']['slug'] . '/members');
    }

    public function setRole(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isAdmin']) {
            Response::forbidden('Only group admins can change roles.');
        }

        Group::setRole((int) $ctx['group']['id'], (int) $request->param('userId'), (string) $request->input('role', 'member'));
        Session::flash('success', 'Role updated.');
        $this->back($request, '/g/' . $ctx['group']['slug'] . '/members');
    }

    public function destroy(Request $request): void
    {
        $ctx = $this->context($request, true);
        $this->csrf($request);
        if (!$ctx['isAdmin']) {
            Response::forbidden('Only group admins can delete a group.');
        }

        Group::delete((int) $ctx['group']['id']);
        Session::flash('success', 'The group and all of its posts were deleted.');
        $this->redirect('/groups');
    }
}
