<?php

namespace App\Http\Controllers;

use App\Helpers\HtmlForm;
use App\Http\Requests\SignatureAuthorizationRequest;
use App\Models\SignatureAuthorization;
use Illuminate\Http\Request;

class SignatureAuthorizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $user = auth()->user();

        $signature = SignatureAuthorization::where('user_id', $user->id)->first();


        return view('signature_authorizations.index', [
            'signature' => $signature,
                'signatureAuthorizationsHtml' => SignatureAuthorization::htmlForm($signature),

                'user' => $user,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //

        return view('signature_authorizations.create', [
            'signatureAuthorizationsHtml' => SignatureAuthorization::htmlForm(new SignatureAuthorization()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();


        SignatureAuthorization::create([

            'active' => true,
            'user_id' => $user->id,
            'name' =>  $request['name'],
            'position' =>  $request['position'],
            'id_number' =>  $request['id_number'],

            'contact_email' =>  $request['contact_email'],
            'contact_number' =>  $request['contact_number'],
            'alternative_number' =>  $request['alternative_number'],
            'signature_image' =>  $request['signature_image'],

        ]);

        return redirect()->route('signature_authorizations.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(SignatureAuthorization $signature)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SignatureAuthorization $signature_authorization)
    {
        //
        return view('signature_authorizations.edit', [
            'signatureAuthorizationsHtml' => SignatureAuthorization::htmlForm($signature_authorization),
            'signature' => $signature_authorization,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,  $signature_id)
    {
        $signature = SignatureAuthorization::where('id', $signature_id)->first();

        $signature->name =  $request['name'];
        $signature->position =  $request['position'];
        $signature->id_number =  $request['id_number'];

        $signature->contact_email =  $request['contact_email'];
        $signature->contact_number =  $request['contact_number'];
        $signature->alternative_number =  $request['alternative_number'];

        $sig_base64 = $request['signature_image'];
        if ($sig_base64)
        {
            $signature->signature_image = $request['signature_image'];
        }

        $signature->save();

        return redirect()->route('signature_authorizations.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SignatureAuthorization $signature)
    {
        //
    }
}
