<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Event;

final class EventController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $id   = (int) $user['id'];

        $this->view('events/index', [
            'title'    => 'Events',
            'upcoming' => Event::upcoming($id, 30),
            'past'     => Event::past($id, 10),
            'hosting'  => Event::hostedBy($id),
        ]);
    }

    public function create(Request $request): void
    {
        $this->auth($request);
        $this->view('events/create', ['title' => 'Create an event']);
    }

    public function store(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $fields = [
            'title'       => (string) $request->input('title', ''),
            'description' => (string) $request->input('description', ''),
            'location'    => (string) $request->input('location', ''),
            'starts_at'   => (string) $request->input('starts_at', ''),
        ];

        $validator = Validator::make($fields, [
            'title'       => 'required|min:3|max:150',
            'description' => 'max:4000',
            'location'    => 'max:190',
            'starts_at'   => 'required|date',
        ], ['starts_at' => 'Start date and time']);

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/events/create');
        }

        $cover = $request->file('cover');
        if ($cover) {
            $fields['cover'] = Upload::image($cover, Upload::COVER);
        }
        $fields['starts_at'] = date('Y-m-d H:i:s', strtotime($fields['starts_at']));

        $eventId = Event::create((int) $user['id'], $fields);
        Event::rsvp($eventId, (int) $user['id'], 'going');

        Session::flash('success', 'Your event is live.');
        $this->redirect('/events/' . $eventId);
    }

    public function show(Request $request): void
    {
        $user  = $this->auth($request);
        $event = Event::withCounts($request->intParam('id'), (int) $user['id']);

        if (!$event) {
            Response::notFound('That event does not exist.');
        }

        $this->view('events/show', [
            'title'       => $event['title'],
            'event'       => $event,
            'host'        => \App\Models\User::find((int) $event['host_id']),
            'going'       => Event::attendees((int) $event['id'], 'going'),
            'interested'  => Event::attendees((int) $event['id'], 'interested'),
            'isHost'      => (int) $event['host_id'] === (int) $user['id'],
        ]);
    }

    public function rsvp(Request $request): void
    {
        $user  = $this->auth($request);
        $this->csrf($request);
        $event = Event::find($request->intParam('id'));

        if (!$event) {
            $this->fail('That event does not exist.', 404);
        }

        Event::rsvp((int) $event['id'], (int) $user['id'], (string) $request->input('status', 'going'));

        if ($request->isAjax()) {
            $this->ok(['event' => Event::withCounts((int) $event['id'], (int) $user['id'])]);
        }
        $this->back($request, '/events/' . $event['id']);
    }

    public function destroy(Request $request): void
    {
        $user  = $this->auth($request);
        $this->csrf($request);
        $event = Event::find($request->intParam('id'));

        if (!$event || (int) $event['host_id'] !== (int) $user['id']) {
            Response::forbidden('Only the host can delete this event.');
        }

        Event::delete((int) $event['id']);
        Session::flash('success', 'Event deleted.');
        $this->redirect('/events');
    }
}
