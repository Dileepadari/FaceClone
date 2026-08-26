<?php
/**
 * Demo data. Everything here is generated - the photos are drawn with GD so
 * the repository carries no binary fixtures and uploads are real files on disk.
 */
declare(strict_types=1);

use App\Core\App;
use App\Core\Database;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Story;
use App\Models\User;

/** Draw a gradient panel with an optional caption and save it under public/uploads. */
function seed_image(string $folder, int $w, int $h, array $from, array $to, string $caption = '', int $seed = 0): string
{
    $img = imagecreatetruecolor($w, $h);

    for ($y = 0; $y < $h; $y++) {
        $t = $y / max(1, $h - 1);
        $c = imagecolorallocate(
            $img,
            (int) round($from[0] + ($to[0] - $from[0]) * $t),
            (int) round($from[1] + ($to[1] - $from[1]) * $t),
            (int) round($from[2] + ($to[2] - $from[2]) * $t)
        );
        imageline($img, 0, $y, $w, $y, $c);
    }

    // A few translucent circles so each image is visually distinct.
    mt_srand($seed ?: random_int(1, 99999));
    for ($i = 0; $i < 7; $i++) {
        $overlay = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(100, 118));
        $r = mt_rand((int) ($w / 6), (int) ($w / 2));
        imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), $r, $r, $overlay);
    }

    if ($caption !== '') {
        $white = imagecolorallocate($img, 255, 255, 255);
        $size  = max(3, (int) min(5, $w / 120));
        $tw    = imagefontwidth($size) * strlen($caption);
        imagestring($img, $size, (int) (($w - $tw) / 2), (int) ($h / 2 - 8), $caption, $white);
    }

    $dir = App::basePath('public/uploads/' . $folder);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = 'seed_' . bin2hex(random_bytes(6)) . '.jpg';
    imagejpeg($img, "$dir/$name", 86);
    imagedestroy($img);

    return "/uploads/$folder/$name";
}

function seed_database(Database $db, bool $force = false): void
{
    if (!$force && (int) $db->value('SELECT COUNT(*) FROM users', [], 0) > 0) {
        warn('The database already has users. Re-run with --force to wipe and reseed.');
        return;
    }

    if ($force) {
        out('Clearing existing data…');
        $db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($db->column('SHOW TABLES') as $table) {
            $db->pdo()->exec("TRUNCATE TABLE `$table`");
        }
        $db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');

        foreach (glob(App::basePath('public/uploads/*/seed_*')) ?: [] as $file) {
            @unlink($file);
        }
    }

    $palettes = [
        [[24, 119, 242], [66, 183, 255]],
        [[138, 63, 252], [227, 86, 167]],
        [[245, 81, 95], [159, 4, 27]],
        [[17, 153, 142], [56, 239, 125]],
        [[252, 74, 26], [247, 183, 51]],
        [[15, 32, 39], [44, 83, 100]],
        [[194, 24, 91], [123, 31, 162]],
        [[0, 166, 125], [0, 105, 92]],
    ];

    out('Creating people…');
    $people = [
        ['Dileep',  'Adari',      'dileep@faceclone.test',  'Bengaluru, India',  'Computer Science, RGUKT',   'Software engineer at ADK DEV',      'Building things that talk to other things.'],
        ['Aisha',   'Rahman',     'aisha@faceclone.test',   'Hyderabad, India',  'Design, NID',              'Product designer at Northwind',      'Type, colour, and long walks.'],
        ['Marcus',  'Bell',       'marcus@faceclone.test',  'Manchester, UK',    'History, Manchester',      'Photographer, freelance',            'Chasing light since 2011.'],
        ['Lena',    'Fischer',    'lena@faceclone.test',    'Berlin, Germany',   'Physics, TU Berlin',       'Data scientist at Helios',           'Coffee in, models out.'],
        ['Priya',   'Nair',       'priya@faceclone.test',   'Kochi, India',      'Literature, Calicut',      'Editor at Longform Weekly',          'I read too much and regret nothing.'],
        ['Tom',     'Okafor',     'tom@faceclone.test',     'Lagos, Nigeria',    'Business, UNILAG',         'Founder at Kite Logistics',          'Moving boxes, mostly.'],
        ['Sofia',   'Moretti',    'sofia@faceclone.test',   'Bologna, Italy',    'Culinary Arts, ALMA',      'Head chef at Trattoria Nove',        'If it has garlic, I am there.'],
        ['Ken',     'Watanabe',   'ken@faceclone.test',     'Osaka, Japan',      'Robotics, Osaka Uni',      'Robotics engineer at Kumo',          'Small robots, big plans.'],
        ['Grace',   'Mwangi',     'grace@faceclone.test',   'Nairobi, Kenya',    'Medicine, UoN',            'Paediatrician at Riverside',         'Tiny patients, enormous hearts.'],
        ['Diego',   'Santos',     'diego@faceclone.test',   'São Paulo, Brazil', 'Music, USP',               'Session guitarist',                  'Six strings and a passport.'],
        ['Hannah',  'Levy',       'hannah@faceclone.test',  'Tel Aviv, Israel',  'CS, Technion',             'Security researcher at Ironvault',   'I break things so you do not have to.'],
        ['Omar',    'Haddad',     'omar@faceclone.test',    'Amman, Jordan',     'Architecture, JU',         'Architect at Sandstone Studio',      'Buildings should breathe.'],
    ];

    $userIds = [];
    foreach ($people as $i => [$first, $last, $email, $city, $education, $work, $bio]) {
        $id = User::create([
            'first_name' => $first,
            'last_name'  => $last,
            'username'   => strtolower($first . '.' . $last),
            'email'      => $email,
            'password'   => 'password123',
            'dob'        => date('Y-m-d', strtotime('-' . (22 + $i) . ' years -' . ($i * 37) . ' days')),
            'gender'     => ['female', 'male', 'custom'][$i % 3],
        ]);

        $palette = $palettes[$i % count($palettes)];
        $db->execute(
            'UPDATE users SET avatar = ?, cover = ?, bio = ?, work = ?, education = ?, city = ?, hometown = ?,
                              relationship = ?, website = ?, last_seen = DATE_SUB(NOW(), INTERVAL ? MINUTE)
             WHERE id = ?',
            [
                seed_image('avatars', 400, 400, $palette[0], $palette[1], strtoupper($first[0] . $last[0]), 100 + $i),
                seed_image('covers', 1200, 420, $palette[1], $palette[0], '', 200 + $i),
                $bio,
                $work,
                $education,
                $city,
                $city,
                ['single', 'in_a_relationship', 'married', 'private'][$i % 4],
                $i % 3 === 0 ? 'https://example.com/' . strtolower($first) : null,
                $i < 4 ? 1 : ($i * 400),
                $id,
            ]
        );
        $userIds[] = $id;
    }
    ok(count($userIds) . ' people created (password for all: password123).');

    // --- friendships -------------------------------------------------------
    out('Wiring the social graph…');
    $me = $userIds[0];
    foreach (array_slice($userIds, 1, 7) as $friend) {
        Friendship::request($me, $friend);
        Friendship::accept($friend, $me);
    }
    // Pending requests waiting on the demo account.
    foreach (array_slice($userIds, 8, 2) as $requester) {
        Friendship::request($requester, $me);
    }
    // A request the demo account sent that is still pending.
    Friendship::request($me, $userIds[10]);

    // Cross links so "people you may know" has mutual friends to rank on.
    for ($i = 1; $i < count($userIds); $i++) {
        for ($j = $i + 1; $j < count($userIds); $j++) {
            if (($i * $j) % 3 !== 0) {
                continue;
            }
            Friendship::request($userIds[$i], $userIds[$j]);
            Friendship::accept($userIds[$j], $userIds[$i]);
        }
    }
    ok('Friendships, follows and pending requests created.');

    // --- groups ------------------------------------------------------------
    out('Creating groups…');
    $groupSpecs = [
        ['Weekend Photographers', 'Share what you shot this weekend. Any camera, any skill level. Be kind in the comments.', 'public'],
        ['Home Cooking Club',     'Recipes, disasters and everything in between. Photos of the burnt bits are encouraged.', 'public'],
        ['Indie Game Devs',       'Devlogs, playtesting and honest feedback for small teams shipping small games.', 'public'],
        ['Trail Runners',         'Routes, gear talk and race reports. Private so members can share locations freely.', 'private'],
    ];
    $groupIds = [];
    foreach ($groupSpecs as $i => [$name, $description, $privacy]) {
        $gid = Group::create($userIds[$i + 1], $name, $description, $privacy);
        Group::setCover($gid, seed_image('groups', 1200, 420, $palettes[($i + 2) % 8][0], $palettes[($i + 2) % 8][1], '', 300 + $i));
        foreach (array_slice($userIds, 0, 8) as $k => $member) {
            if ($member === $userIds[$i + 1]) {
                continue;
            }
            if (($k + $i) % 4 === 3) {
                continue; // leave some people out so Discover has something to show
            }
            $db->execute(
                'INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, ?, "member")
                 ON DUPLICATE KEY UPDATE status = "member"',
                [$gid, $member, $k % 5 === 1 ? 'moderator' : 'member']
            );
        }
        $groupIds[] = $gid;
    }
    // A pending join request for the private group, so the review screen has data.
    $db->execute(
        'INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, "member", "pending")
         ON DUPLICATE KEY UPDATE status = "pending"',
        [$groupIds[3], $userIds[9]]
    );
    ok(count($groupIds) . ' groups created.');

    // --- posts -------------------------------------------------------------
    out('Writing posts…');
    $texts = [
        "Finally shipped the thing I have been quietly building for four months. It is small, it is a bit rough at the edges, and I am absurdly proud of it.",
        "Unpopular opinion: the best debugging tool is a walk around the block.",
        "Six hours on a bug. The fix was one character. I am going to lie down.",
        "The light this morning was doing something unreasonable and I only had my phone. Still took it.",
        "Made bread for the first time without a recipe. It rose. I have never felt more powerful.",
        "Every time I think I understand CSS grid, CSS grid disagrees.",
        "Ran the ridge trail before sunrise. Twelve kilometres, no music, no thoughts, just breathing.",
        "Reading three books at once again, which means I am finishing none of them.",
        "Started learning to solder this weekend. My first joint looked like chewing gum. The fourth looked like a joint.",
        "If you have ever wondered whether anyone reads the changelog: I do, and I appreciate you writing it.",
        "Booked the flights. Two weeks, one backpack, no fixed plan past the first night.",
        "The studio is finally set up. Speakers at ear height, cables labelled, nothing on the floor. It will last a week.",
        "Trying to explain recursion to my nephew ended with him asking why I did not just use a loop. Fair.",
        "New camera body arrived. Immediately took forty photos of my cat, who is unimpressed.",
        "Three years at this job today. Still learning something every week, which is the only metric I really care about.",
        "The best code review I ever got was two words: 'why though'. It saved me a whole feature.",
    ];

    $postIds = [];
    foreach ($texts as $i => $text) {
        $author  = $userIds[$i % count($userIds)];
        $palette = $palettes[$i % count($palettes)];
        $withBg  = $i % 7 === 2;
        $withPic = $i % 3 === 0;

        $pid = Post::create([
            'user_id'        => $author,
            'group_id'       => null,
            'shared_post_id' => null,
            'content'        => $text,
            'background'     => $withBg ? 'bg' . (($i % 8) + 1) : null,
            'feeling'        => $i % 5 === 1 ? ['grateful', 'excited', 'tired', 'motivated'][$i % 4] : null,
            'location'       => $i % 6 === 4 ? ['Bengaluru', 'Berlin', 'Osaka', 'Lagos'][$i % 4] : null,
            'privacy'        => $i % 4 === 3 ? 'friends' : 'public',
        ]);

        if ($withPic && !$withBg) {
            $shots = ($i % 6 === 0) ? 3 : 1;
            for ($n = 0; $n < $shots; $n++) {
                Post::addMedia($pid, seed_image('posts', 1200, 800, $palette[0], $palette[1], '', 400 + $i * 10 + $n), 'image', $n);
            }
        }

        // Backdate so the feed has a believable spread and Memories has material.
        $db->execute(
            'UPDATE posts SET created_at = DATE_SUB(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
            [$i * 137 + random_int(5, 90), $pid]
        );
        $postIds[] = $pid;
    }

    // A post from exactly one year ago today, for the Memories page.
    $memoryId = Post::create([
        'user_id' => $me, 'group_id' => null, 'shared_post_id' => null,
        'content' => 'One year ago today I wrote my very first line of PHP. Look at us now.',
        'background' => 'bg2', 'feeling' => 'nostalgic', 'location' => null, 'privacy' => 'public',
    ]);
    $db->execute('UPDATE posts SET created_at = DATE_SUB(NOW(), INTERVAL 1 YEAR) WHERE id = ?', [$memoryId]);

    // Group posts.
    foreach ($groupIds as $gi => $gid) {
        $members = Group::members($gid, 'member', 6);
        foreach (array_slice($members, 0, 3) as $mi => $member) {
            $gp = Post::create([
                'user_id' => (int) $member['id'], 'group_id' => $gid, 'shared_post_id' => null,
                'content' => [
                    'Posting my favourite frame from the weekend. Critique welcome, be gentle.',
                    'Does anyone have a reliable method for this that does not involve guessing?',
                    'Reminder that the meetup is next Thursday. Bring whatever you are working on.',
                ][$mi],
                'background' => null, 'feeling' => null, 'location' => null, 'privacy' => 'public',
            ]);
            if ($mi === 0) {
                Post::addMedia($gp, seed_image('posts', 1200, 800, $palettes[$gi][0], $palettes[$gi][1], '', 600 + $gi * 5), 'image', 0);
            }
            $db->execute('UPDATE posts SET created_at = DATE_SUB(NOW(), INTERVAL ? HOUR) WHERE id = ?', [$mi * 6 + $gi, $gp]);
        }
    }

    // One share, so the shared-post embed is exercised.
    $shareId = Post::create([
        'user_id' => $me, 'group_id' => null, 'shared_post_id' => $postIds[1],
        'content' => 'This is the most useful advice in this whole feed.',
        'background' => null, 'feeling' => null, 'location' => null, 'privacy' => 'public',
    ]);
    $db->execute('UPDATE posts SET created_at = DATE_SUB(NOW(), INTERVAL 40 MINUTE) WHERE id = ?', [$shareId]);
    ok(count($postIds) + 1 . ' timeline posts, group posts and one share created.');

    // --- reactions and comments -------------------------------------------
    out('Adding reactions and comments…');
    $types    = Reaction::TYPES;
    $comments = [
        'This is excellent, congratulations.',
        'Saving this for later, thank you for writing it up.',
        'Genuinely laughed out loud at this.',
        'How long did this take you end to end?',
        'The composition here is lovely.',
        'I needed to read this today.',
        'Okay but where is the recipe.',
        'Been there. Solidarity.',
    ];

    foreach ($postIds as $i => $pid) {
        foreach ($userIds as $k => $uid) {
            if (($i + $k) % 3 === 0) {
                Reaction::toggle($uid, 'post', $pid, $types[($i + $k) % count($types)]);
            }
        }
        if ($i % 2 === 0) {
            $commenter = $userIds[($i + 3) % count($userIds)];
            $cid = Comment::create($pid, $commenter, $comments[$i % count($comments)]);
            Reaction::toggle($userIds[($i + 5) % count($userIds)], 'comment', $cid, 'like');

            if ($i % 4 === 0) {
                Comment::create($pid, $userIds[$i % count($userIds)], 'Thank you, that means a lot.', $cid);
            }
        }
    }
    ok('Reactions and comment threads created.');

    // --- stories -----------------------------------------------------------
    out('Publishing stories…');
    foreach (array_slice($userIds, 0, 6) as $i => $uid) {
        if ($i % 2 === 0) {
            Story::create($uid, [
                'type'  => 'photo',
                'media' => seed_image('stories', 720, 1280, $palettes[$i % 8][0], $palettes[$i % 8][1], '', 700 + $i),
            ]);
        } else {
            Story::create($uid, [
                'type'       => 'text',
                'text'       => ['Out for a run.', 'New track drops Friday.', 'Coffee number four.', 'Shipping today.'][$i % 4],
                'background' => 'bg' . (($i % 8) + 1),
            ]);
        }
    }
    // A second story for the demo account so the viewer has to advance.
    Story::create($me, ['type' => 'text', 'text' => 'Second slide. Tap right to move on.', 'background' => 'bg7']);
    ok('Stories published (they expire 24 hours from now).');

    // --- messages ----------------------------------------------------------
    out('Seeding conversations…');
    $threads = [
        [1, [['Hey, did you get a chance to look at the mockups?', 1], ['Just opened them. The second option is much stronger.', 0],
             ['Agreed. I will tighten the spacing and send v3 tonight.', 1], ['Perfect, thanks.', 0]]],
        [2, [['That shot you posted this morning is unreal.', 0], ['Thank you! Six attempts, one usable frame.', 1],
             ['Worth it. What lens?', 0], ['35mm, wide open. Handheld and slightly lucky.', 1]]],
        [3, [['Are you around for a call tomorrow?', 1], ['Morning works. 10:00 your time?', 0], ['Booked it.', 1]]],
        [4, [['Sending over the notes from the review now.', 0]]],
    ];
    foreach ($threads as [$partnerIndex, $lines]) {
        $partner = $userIds[$partnerIndex];
        $cid     = Conversation::between($me, $partner);
        foreach ($lines as $n => [$body, $fromPartner]) {
            $mid = Message::send($cid, $fromPartner ? $partner : $me, $body);
            $db->execute('UPDATE messages SET created_at = DATE_SUB(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
                [(count($lines) - $n) * 11 + $partnerIndex * 60, $mid]);
        }
        // Leave the last two threads unread for the demo account.
        if ($partnerIndex >= 3) {
            $db->execute('UPDATE conversation_participants SET last_read_at = NULL WHERE conversation_id = ? AND user_id = ?', [$cid, $me]);
        }
    }
    ok(count($threads) . ' conversations seeded.');

    // --- events and listings ----------------------------------------------
    out('Creating events and listings…');
    $events = [
        ['Sunrise Photo Walk',   'Meet at the north gate. Bring a tripod if you have one, and something warm.', 'Cubbon Park, Bengaluru', '+6 days 06:00'],
        ['Pasta Night',          'Hands-on session. We make three shapes and eat all of them.',                  'Trattoria Nove, Bologna', '+13 days 19:00'],
        ['Indie Playtest Jam',   'Bring a build, get twenty minutes of honest feedback from strangers.',          'The Hangar, Berlin',      '+20 days 14:00'],
    ];
    foreach ($events as $i => [$eTitle, $eDesc, $eLoc, $when]) {
        $eid = Event::create($userIds[$i + 1], [
            'title'       => $eTitle,
            'description' => $eDesc,
            'location'    => $eLoc,
            'starts_at'   => date('Y-m-d H:i:s', strtotime($when)),
            'cover'       => seed_image('covers', 1200, 500, $palettes[($i + 4) % 8][0], $palettes[($i + 4) % 8][1], '', 800 + $i),
        ]);
        foreach (array_slice($userIds, 0, 6) as $k => $uid) {
            Event::rsvp($eid, $uid, ($k + $i) % 3 === 0 ? 'interested' : 'going');
        }
    }

    $listings = [
        ['Cannondale road bike, 54cm',        'Ridden two summers, always stored indoors. New chain and cassette last month.', 420.00, 'hobbies',     'Bengaluru'],
        ['Herman Miller Aeron, size B',       'Genuine, bought refurbished in 2022. Some scuffs on the base, mechanism perfect.', 615.00, 'home',       'Berlin'],
        ['Fujifilm X-T30 with 27mm pancake',  'Around 8k actuations. Comes with two batteries, charger and the original box.',   690.00, 'electronics', 'Manchester'],
        ['Standing desk, electric, 160x80',   'Two motors, three memory presets. Disassembled and ready to collect.',            240.00, 'home',        'Osaka'],
        ['Box of paperbacks, about 40 books', 'Mostly literary fiction and a few thrillers. Free to whoever collects.',            0.00, 'free',        'Kochi'],
        ['Yamaha FG800 acoustic guitar',      'Great first guitar. Comes with a gig bag, capo and a set of spare strings.',      180.00, 'hobbies',     'São Paulo'],
    ];
    foreach ($listings as $i => [$lTitle, $lDesc, $price, $category, $location]) {
        Listing::create($userIds[($i + 2) % count($userIds)], [
            'title'       => $lTitle,
            'description' => $lDesc,
            'price'       => $price,
            'category'    => $category,
            'location'    => $location,
            'image'       => seed_image('posts', 900, 900, $palettes[($i + 1) % 8][0], $palettes[($i + 1) % 8][1], '', 900 + $i),
        ]);
    }
    ok(count($events) . ' events and ' . count($listings) . ' marketplace listings created.');

    // --- notifications -----------------------------------------------------
    out('Generating notifications…');
    $notifs = [
        [$userIds[1], 'reaction_post',  'post', $postIds[0], '/posts/' . $postIds[0]],
        [$userIds[2], 'comment_post',   'post', $postIds[0], '/posts/' . $postIds[0]],
        [$userIds[8], 'friend_request', 'user', $userIds[8], '/friends/requests'],
        [$userIds[9], 'friend_request', 'user', $userIds[9], '/friends/requests'],
        [$userIds[3], 'group_invite',   'group', $groupIds[0], '/g/' . Group::find($groupIds[0])['slug']],
        [$userIds[4], 'post_share',     'post', $postIds[1], '/posts/' . $postIds[1]],
        [$userIds[5], 'message_new',    'conversation', null, '/messages'],
    ];
    foreach ($notifs as $i => [$actor, $type, $entityType, $entityId, $url]) {
        $nid = Notification::create($me, $actor, $type, $entityType, $entityId, $url);
        $db->execute('UPDATE notifications SET created_at = DATE_SUB(NOW(), INTERVAL ? MINUTE), is_read = ? WHERE id = ?',
            [$i * 47 + 3, $i > 4 ? 1 : 0, $nid]);
    }
    ok(count($notifs) . ' notifications created.');

    // Saved posts so the Saved page is not empty.
    foreach (array_slice($postIds, 2, 3) as $pid) {
        $db->execute('INSERT IGNORE INTO saved_posts (user_id, post_id) VALUES (?, ?)', [$me, $pid]);
    }

    out('');
    ok('Seed complete.');
    out('');
    out('  Sign in with   dileep@faceclone.test  /  password123');
    out('  Every seeded account uses the same password.');
}
