<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\AssetInformation;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    //
    public function index()
    {
        $listings = Listing::where('team_id', auth()->user()->id)->get();

        return view('listings.index', [
            'listings' => $listings
        ]);
    }

    public function create()
    {
        return view('listings.create', [
            'listingHtml' => Listing::htmlForm(new Listing())
        ]);

    }

    public function store(Request $request)
    {

        $listing = new Listing();

        $listing->unit_id = $request->input('unit_id');
        $listing->title = $request->input('title');
        $listing->description = $request->input('description');

        $listing->team_id = auth()->user()->current_team_id;

        $listing->save();

        return redirect()->route('listings.index', 'Created Listing Successfully');

    }

    public function show(Listing $listing)
    {
        $asset_information_id = $listing->unit->asset_information_id;
        if ($asset_information_id)
        {
            $asset_information = $listing->unit->assetInformation;
        } else {
            $asset_information = new AssetInformation();
        }

        return view('listings.show', [
            'listing' => $listing,
            'assetInfoHtml' => AssetInformation::htmlListingShow($asset_information)
        ]);

    }

    public function edit(Listing $listing)
    {
        return view('listings.edit', [
            'listing' => $listing,
            'listingHtml' => Listing::htmlForm($listing)
        ]);

    }

    public function assetInfo(Listing $listing)
    {
        $asset_information_id = $listing->unit->asset_information_id;
        if ($asset_information_id)
        {
            $asset_information = $listing->unit->assetInformation;
            $createNotUpdate = false;
        } else {
            $asset_information = new AssetInformation();
            $createNotUpdate = true;
        }

        return view('listings.asset_info', [
            'listing' => $listing,
            'assetInfoHtml' => AssetInformation::htmlListingForm($asset_information),
            'createNotUpdate' => $createNotUpdate
        ]);
    }

    public function assetInfoPost(Request $request, Listing $listing, $createNotUpdate)
    {
        $asset_information_id = $listing->unit->asset_information_id;
        if ($asset_information_id)
        {
            $asset_information = $listing->unit->assetInformation;
        } else {
            $asset_information = new AssetInformation();
        }

        $asset_information->number_of_bedrooms = $request['number_of_bedrooms'];
        $asset_information->number_of_bathrooms = $request['number_of_bathrooms'];
        $asset_information->number_of_garages = $request['number_of_garages'];
        $asset_information->number_of_kitchens = $request['number_of_kitchens'];
        $asset_information->number_of_parking = $request['number_of_parking'];
        $asset_information->is_furnished = $request['is_furnished'];
        $asset_information->is_pet_friendly = $request['is_pet_friendly'];
        $asset_information->has_garden = $request['has_garden'];
        $asset_information->has_pool = $request['has_pool'];
        $asset_information->has_balcony = $request['has_balcony'];
        $asset_information->security_features = $request['security_features'];
        $asset_information->km_from_hospital = $request['km_from_hospital'];
        $asset_information->km_from_school = $request['km_from_school'];
        $asset_information->km_from_mall = $request['km_from_mall'];//
        $asset_information->erf_size = $request['erf_size'];

        $asset_information->is_estate = 0;
        $asset_information->team_id = auth()->user()->current_team_id;
        $asset_information->is_property = true;
        $asset_information->is_room = false;
        $asset_information->is_room_sharing = false;

        $asset_information->save();

        $asset_information_id = $asset_information->id;

        $unit = $listing->unit;
        $unit->asset_information_id = $asset_information_id;
        $unit->save();


        return redirect()->route('listings.show', $listing)->with('success', 'Listing info saved.');
    }

    public function update(Request $request, Listing $listing)
    {

    }
}
