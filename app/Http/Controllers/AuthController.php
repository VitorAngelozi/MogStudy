<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(){
        return view('login');
    }

    public function loginsubmit(Request $request){
       echo $request->input('email');
       echo "<br>";
       echo $request->input('password');
    }

    public function logout(){
        return view('logout');
    }
}