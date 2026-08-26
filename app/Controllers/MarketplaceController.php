<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Listing;

final class MarketplaceController extends Controller
{
    public function index(Request $request): void
    {
        $this->auth($request);
        $category = (string) $request->query('category', '');
        $search   = (string) $request->query('q', '');

        $this->view('marketplace/index', [
            'title'      => 'Marketplace',
            'listings'   => Listing::browse($category ?: null, $search),
            'category'   => $category,
            'search'     => $search,
            'categories' => Listing::CATEGORIES,
        ]);
    }

    public function create(Request $request): void
    {
        $this->auth($request);
        $this->view('marketplace/create', [
            'title'      => 'List something for sale',
            'categories' => Listing::CATEGORIES,
        ]);
    }

    public function store(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $fields = [
            'title'       => (string) $request->input('title', ''),
            'description' => (string) $request->input('description', ''),
            'price'       => (string) $request->input('price', '0'),
            'category'    => (string) $request->input('category', 'other'),
            'location'    => (string) $request->input('location', ''),
        ];

        $validator = Validator::make($fields, [
            'title'       => 'required|min:3|max:150',
            'description' => 'max:4000',
            'price'       => 'required|numeric',
            'category'    => 'required',
            'location'    => 'max:120',
        ]);

        if ((float) $fields['price'] < 0) {
            $validator->fail('price', 'Price cannot be negative.');
        }

        if ($validator->fails()) {
            $this->backWithErrors($request, $validator, '/marketplace/create');
        }

        $image = $request->file('image');
        if ($image) {
            $fields['image'] = Upload::image($image, Upload::POST);
            if (!$fields['image']) {
                Session::flash('error', Upload::lastError() ?: 'That photo could not be uploaded.');
                $this->redirect('/marketplace/create');
            }
        }

        $id = Listing::create((int) $user['id'], $fields);
        Session::flash('success', 'Your listing is live.');
        $this->redirect('/marketplace/item/' . $id);
    }

    public function show(Request $request): void
    {
        $user    = $this->auth($request);
        $listing = Listing::withSeller($request->intParam('id'));

        if (!$listing) {
            Response::notFound('That listing is no longer available.');
        }

        $this->view('marketplace/show', [
            'title'      => $listing['title'],
            'listing'    => $listing,
            'isSeller'   => (int) $listing['seller_id'] === (int) $user['id'],
            'categories' => Listing::CATEGORIES,
            'related'    => array_slice(Listing::browse($listing['category'], '', 5), 0, 4),
        ]);
    }

    public function selling(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('marketplace/selling', [
            'title'      => 'Your listings',
            'listings'   => Listing::forSeller((int) $user['id']),
            'categories' => Listing::CATEGORIES,
        ]);
    }

    public function markSold(Request $request): void
    {
        $user    = $this->auth($request);
        $this->csrf($request);
        $listing = Listing::find($request->intParam('id'));

        if (!$listing || (int) $listing['seller_id'] !== (int) $user['id']) {
            Response::forbidden('Only the seller can update this listing.');
        }

        Listing::markSold((int) $listing['id'], !$listing['is_sold']);
        Session::flash('success', $listing['is_sold'] ? 'Listing is available again.' : 'Marked as sold.');
        $this->back($request, '/marketplace/selling');
    }

    public function destroy(Request $request): void
    {
        $user    = $this->auth($request);
        $this->csrf($request);
        $listing = Listing::find($request->intParam('id'));

        if (!$listing || (int) $listing['seller_id'] !== (int) $user['id']) {
            Response::forbidden('Only the seller can delete this listing.');
        }

        Listing::delete((int) $listing['id']);
        Session::flash('success', 'Listing deleted.');
        $this->redirect('/marketplace/selling');
    }
}
