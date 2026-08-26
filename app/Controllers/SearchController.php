<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;

final class SearchController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $term = trim((string) $request->query('q', ''));
        $tab  = (string) $request->query('tab', 'all');

        if ($term === '') {
            $this->view('search/index', [
                'title'  => 'Search',
                'term'   => '',
                'tab'    => $tab,
                'people' => [],
                'posts'  => [],
                'groups' => [],
                'recent' => Friendship::friends((int) $user['id'], 8),
            ]);
            return;
        }

        // A leading # searches post text for the hashtag itself.
        $postTerm = str_starts_with($term, '#') ? $term : $term;

        $people = in_array($tab, ['all', 'people'], true)
            ? User::search($term, (int) $user['id'], $tab === 'people' ? 50 : 6)
            : [];
        $posts = in_array($tab, ['all', 'posts'], true)
            ? Post::search($postTerm, (int) $user['id'], $tab === 'posts' ? 30 : 5)
            : [];
        $groups = in_array($tab, ['all', 'groups'], true)
            ? Group::search($term, $tab === 'groups' ? 40 : 5)
            : [];

        $this->view('search/index', [
            'title'  => 'Search results for ' . $term,
            'term'   => $term,
            'tab'    => $tab,
            'people' => $people,
            'posts'  => $posts,
            'groups' => $groups,
            'recent' => [],
        ]);
    }

    /** Search-bar typeahead. */
    public function typeahead(Request $request): void
    {
        $user = $this->auth($request);
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            $this->ok(['results' => []]);
        }

        $results = [];
        foreach (User::search($term, (int) $user['id'], 5) as $person) {
            $results[] = [
                'type'     => 'person',
                'label'    => full_name($person),
                'sub'      => $person['city'] ?: '@' . $person['username'],
                'image'    => avatar_url($person),
                'url'      => profile_url($person),
                'rounded'  => true,
            ];
        }
        foreach (Group::search($term, 3) as $group) {
            $results[] = [
                'type'    => 'group',
                'label'   => $group['name'],
                'sub'     => number_short((int) $group['member_count']) . ' members',
                'image'   => $group['cover'] ?: '',
                'url'     => '/g/' . $group['slug'],
                'rounded' => false,
            ];
        }

        $this->ok(['results' => $results, 'term' => $term]);
    }
}
