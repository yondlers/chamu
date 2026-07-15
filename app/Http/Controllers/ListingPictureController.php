<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingPicture;
use Illuminate\Http\Request;

class ListingPictureController extends Controller
{
    //

    public function index()
    {

    }

    public function create()
    {

    }

    public function store(Request $request){

    }

    public function show(ListingPicture $listing_picture)
    {

    }

    public function upload($listing_id)
    {
        $listing = Listing::where('id', $listing_id)->first();

        $pictures = ListingPicture::where('listing_id', $listing_id)->get();

        if ($pictures->isEmpty()) {
            $picture = new ListingPicture();
        }

        return view('listing_pictures.upload', [
            'listing' => $listing,
            'pictures' => $pictures
        ]);
    }

    public function uploadPost(Request $request, $listing_id)
    {
        $listing_picture = new ListingPicture();

        $listing_picture->listing_id = $listing_id;
        $listing_picture->team_id = auth()->user()->current_team_id;

        $filePrefix = 'listing/' . $listing_id;

        if ($request->hasFile('image_1')) {
            $image_1 = $request->file('image_1')->store($filePrefix, 'public');
            $listing_picture->image_1 = $image_1;
        }

        if ($request->hasFile('image_2')) {
            $image_2 = $request->file('image_2')->store($filePrefix, 'public');
            $listing_picture->image_2 = $image_2;
        }

        if ($request->hasFile('image_3')) {
            $image_3 = $request->file('image_3')->store($filePrefix, 'public');
            $listing_picture->image_3 = $image_3;
        }

        if ($request->hasFile('image_4')) {
            $image_4 = $request->file('image_4')->store($filePrefix, 'public');
            $listing_picture->image_4 = $image_4;
        }

        if ($request->hasFile('image_5')) {
            $image_5 = $request->file('image_5')->store($filePrefix, 'public');
            $listing_picture->image_5 = $image_5;
        }

        if ($request->hasFile('image_6')) {
            $image_6 = $request->file('image_6')->store($filePrefix, 'public');
            $listing_picture->image_6 = $image_6;
        }

        if ($request->hasFile('image_7')) {
            $image_7 = $request->file('image_7')->store($filePrefix, 'public');
            $listing_picture->image_7 = $image_7;
        }

        if ($request->hasFile('image_8')) {
            $image_8 = $request->file('image_8')->store($filePrefix, 'public');
            $listing_picture->image_8 = $image_8;
        }

        if ($request->hasFile('image_9')) {
            $image_9 = $request->file('image_9')->store($filePrefix, 'public');
            $listing_picture->image_9 = $image_9;
        }

        if ($request->hasFile('image_10')) {
            $image_10 = $request->file('image_10')->store($filePrefix, 'public');
            $listing_picture->image_10 = $image_10;
        }

        $listing_picture->save();

        return response()->json([
            'success' => true,
            'redirect_url' => route('listings.show', ['listing' => $listing_id]),
            'message' => 'Images uploaded successfully!',
        ]);
    }

    public function edit(ListingPicture $listing_picture)
    {
//        dd($listing_picture);
        $listing = $listing_picture->listing;

        $pictures = $listing_picture;

        return view('listing_pictures.edit', [
            'listing' => $listing,
            'pictures' => $pictures
        ]);
    }

    public function update(Request $request, ListingPicture $listing_picture)
    {

    }

    public function destroy(ListingPicture $listing_picture)
    {


    }

}
