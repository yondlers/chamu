<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function register(Request $request)
    {
        dd($request);
    }

    public function signup(Request $request)
    {
        dd(1);
    }

    public function login()
    {
        return view('users.login');
        // dd(1);
    }
}
