<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Suburb;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    //
    public function search(Request $request)
    {
//        $search = $request->get('search');
        $search = (array) $request->input('search', []);


        return view('search.search', [
            'listings' => Listing::search1($search),
            'locations' => Suburb::get(),
            'search' => $request->search
        ]);

    }

    public function place($listing_id, $search)
    {
        $search = explode(',', $search);

        return view('search.place', [
            'locations' => Suburb::get(),
            'search' => $search,
            'listing' => Listing::where('id', $listing_id)->first()
        ]);
    }
}
