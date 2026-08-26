<?php
/**
 * Route table. $router is provided by public/index.php.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\EventController;
use App\Controllers\FeedController;
use App\Controllers\FriendController;
use App\Controllers\GroupController;
use App\Controllers\MarketplaceController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\PostController;
use App\Controllers\ProfileController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\StoryController;

// ---- authentication ---------------------------------------------------------
$router->get('/login',            [AuthController::class, 'showLogin']);
$router->post('/login',           [AuthController::class, 'login']);
$router->get('/register',         [AuthController::class, 'showRegister']);
$router->post('/register',        [AuthController::class, 'register']);
$router->get('/forgot-password',  [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password',   [AuthController::class, 'showReset']);
$router->post('/reset-password',  [AuthController::class, 'reset']);
$router->post('/logout',          [AuthController::class, 'logout']);
$router->get('/logout',           [AuthController::class, 'logout']);

// ---- feed -------------------------------------------------------------------
$router->get('/',            [FeedController::class, 'index']);
$router->get('/feed/more',   [FeedController::class, 'more']);
$router->get('/watch',       [FeedController::class, 'watch']);
$router->get('/saved',       [FeedController::class, 'saved']);
$router->get('/memories',    [FeedController::class, 'memories']);

// ---- posts ------------------------------------------------------------------
$router->post('/posts',                 [PostController::class, 'store']);
$router->get('/posts/{id}',             [PostController::class, 'show']);
$router->post('/posts/{id}/update',     [PostController::class, 'update']);
$router->post('/posts/{id}/delete',     [PostController::class, 'destroy']);
$router->post('/posts/{id}/react',      [PostController::class, 'react']);
$router->get('/posts/{id}/reactions',   [PostController::class, 'reactions']);
$router->post('/posts/{id}/save',       [PostController::class, 'save']);
$router->post('/posts/{id}/share',      [PostController::class, 'share']);
$router->get('/posts/{id}/comments',    [CommentController::class, 'index']);

// ---- comments ---------------------------------------------------------------
$router->post('/comments',              [CommentController::class, 'store']);
$router->post('/comments/{id}/update',  [CommentController::class, 'update']);
$router->post('/comments/{id}/delete',  [CommentController::class, 'destroy']);
$router->post('/comments/{id}/react',   [CommentController::class, 'react']);

// ---- stories ----------------------------------------------------------------
$router->get('/stories',                 [StoryController::class, 'index']);
$router->get('/stories/create',          [StoryController::class, 'create']);
$router->post('/stories',                [StoryController::class, 'store']);
$router->get('/stories/user/{id}',       [StoryController::class, 'viewer']);
$router->post('/stories/{id}/seen',      [StoryController::class, 'seen']);
$router->get('/stories/{id}/viewers',    [StoryController::class, 'viewers']);
$router->post('/stories/{id}/delete',    [StoryController::class, 'destroy']);

// ---- profile ----------------------------------------------------------------
$router->get('/u/{handle}',                 [ProfileController::class, 'show']);
$router->get('/u/{handle}/about',           [ProfileController::class, 'about']);
$router->get('/u/{handle}/friends',         [ProfileController::class, 'friends']);
$router->get('/u/{handle}/photos',          [ProfileController::class, 'photos']);
$router->get('/u/{handle}/groups',          [ProfileController::class, 'groups']);
$router->get('/u/{handle}/followers',       [ProfileController::class, 'followers']);
$router->get('/u/{handle}/following',       [ProfileController::class, 'following']);
$router->post('/profile/avatar',            [ProfileController::class, 'updateAvatar']);
$router->post('/profile/cover',             [ProfileController::class, 'updateCover']);
$router->post('/profile/intro',             [ProfileController::class, 'updateIntro']);
$router->post('/profile/details',           [ProfileController::class, 'updateDetails']);
$router->get('/avatar/{id}',                [ProfileController::class, 'generatedAvatar']);

// ---- friends ----------------------------------------------------------------
$router->get('/friends',                  [FriendController::class, 'index']);
$router->get('/friends/requests',         [FriendController::class, 'requests']);
$router->get('/friends/suggestions',      [FriendController::class, 'suggestions']);
$router->get('/friends/all',              [FriendController::class, 'all']);
$router->post('/friends/{id}/request',    [FriendController::class, 'request']);
$router->post('/friends/{id}/accept',     [FriendController::class, 'accept']);
$router->post('/friends/{id}/decline',    [FriendController::class, 'decline']);
$router->post('/friends/{id}/cancel',     [FriendController::class, 'cancel']);
$router->post('/friends/{id}/remove',     [FriendController::class, 'remove']);
$router->post('/friends/{id}/follow',     [FriendController::class, 'follow']);
$router->post('/friends/{id}/block',      [FriendController::class, 'block']);
$router->post('/friends/{id}/unblock',    [FriendController::class, 'unblock']);

// ---- groups -----------------------------------------------------------------
$router->get('/groups',                        [GroupController::class, 'index']);
$router->get('/groups/create',                 [GroupController::class, 'create']);
$router->post('/groups',                       [GroupController::class, 'store']);
$router->get('/groups/discover',               [GroupController::class, 'discover']);
$router->get('/g/{handle}',                    [GroupController::class, 'show']);
$router->get('/g/{handle}/about',              [GroupController::class, 'about']);
$router->get('/g/{handle}/members',            [GroupController::class, 'members']);
$router->get('/g/{handle}/photos',             [GroupController::class, 'photos']);
$router->get('/g/{handle}/requests',           [GroupController::class, 'requests']);
$router->get('/g/{handle}/invite',             [GroupController::class, 'inviteForm']);
$router->get('/g/{handle}/settings',           [GroupController::class, 'settings']);
$router->post('/g/{handle}/settings',          [GroupController::class, 'updateSettings']);
$router->post('/g/{handle}/cover',             [GroupController::class, 'updateCover']);
$router->post('/g/{handle}/join',              [GroupController::class, 'join']);
$router->post('/g/{handle}/leave',             [GroupController::class, 'leave']);
$router->post('/g/{handle}/invite/{userId}',   [GroupController::class, 'invite']);
$router->post('/g/{handle}/approve/{userId}',  [GroupController::class, 'approve']);
$router->post('/g/{handle}/reject/{userId}',   [GroupController::class, 'reject']);
$router->post('/g/{handle}/remove/{userId}',   [GroupController::class, 'removeMember']);
$router->post('/g/{handle}/role/{userId}',     [GroupController::class, 'setRole']);
$router->post('/g/{handle}/delete',            [GroupController::class, 'destroy']);

// ---- messenger --------------------------------------------------------------
$router->get('/messages',                    [MessageController::class, 'index']);
$router->get('/messages/new/{userId}',       [MessageController::class, 'startWith']);
$router->get('/messages/{id}',               [MessageController::class, 'show']);
$router->post('/messages/{id}',              [MessageController::class, 'send']);
$router->get('/messages/{id}/poll',          [MessageController::class, 'poll']);
$router->get('/api/messages/unread',         [MessageController::class, 'unread']);
$router->get('/api/messages/recent',         [MessageController::class, 'recent']);

// ---- notifications ----------------------------------------------------------
$router->get('/notifications',                 [NotificationController::class, 'index']);
$router->get('/api/notifications',             [NotificationController::class, 'dropdown']);
$router->post('/notifications/read-all',       [NotificationController::class, 'readAll']);
$router->get('/notifications/{id}/open',       [NotificationController::class, 'open']);
$router->post('/notifications/{id}/delete',    [NotificationController::class, 'destroy']);

// ---- search -----------------------------------------------------------------
$router->get('/search',            [SearchController::class, 'index']);
$router->get('/api/search',        [SearchController::class, 'typeahead']);

// ---- settings ---------------------------------------------------------------
$router->get('/settings',                   [SettingsController::class, 'index']);
$router->get('/settings/account',           [SettingsController::class, 'account']);
$router->post('/settings/account',          [SettingsController::class, 'updateAccount']);
$router->post('/settings/password',         [SettingsController::class, 'updatePassword']);
$router->get('/settings/privacy',           [SettingsController::class, 'privacy']);
$router->post('/settings/privacy',          [SettingsController::class, 'updatePrivacy']);
$router->get('/settings/notifications',     [SettingsController::class, 'notifications']);
$router->post('/settings/notifications',    [SettingsController::class, 'updateNotifications']);
$router->get('/settings/appearance',        [SettingsController::class, 'appearance']);
$router->post('/settings/appearance',       [SettingsController::class, 'updateAppearance']);
$router->get('/settings/blocking',          [SettingsController::class, 'blocking']);
$router->post('/settings/deactivate',       [SettingsController::class, 'deactivate']);
$router->post('/settings/delete',           [SettingsController::class, 'deleteAccount']);

// ---- events -----------------------------------------------------------------
$router->get('/events',                 [EventController::class, 'index']);
$router->get('/events/create',          [EventController::class, 'create']);
$router->post('/events',                [EventController::class, 'store']);
$router->get('/events/{id}',            [EventController::class, 'show']);
$router->post('/events/{id}/rsvp',      [EventController::class, 'rsvp']);
$router->post('/events/{id}/delete',    [EventController::class, 'destroy']);

// ---- marketplace ------------------------------------------------------------
$router->get('/marketplace',                  [MarketplaceController::class, 'index']);
$router->get('/marketplace/create',           [MarketplaceController::class, 'create']);
$router->post('/marketplace',                 [MarketplaceController::class, 'store']);
$router->get('/marketplace/selling',          [MarketplaceController::class, 'selling']);
$router->get('/marketplace/item/{id}',        [MarketplaceController::class, 'show']);
$router->post('/marketplace/item/{id}/sold',  [MarketplaceController::class, 'markSold']);
$router->post('/marketplace/item/{id}/delete',[MarketplaceController::class, 'destroy']);
