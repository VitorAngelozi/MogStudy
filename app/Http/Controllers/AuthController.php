<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(){
        return view('login');
    }

    public function loginsubmit(Request $request){


        //form validation
        $request->validate(
            [
                'email' => 'required',
                'password' => 'required'

            ]
        );

        //get user input
        $email = $request->input('email');
        $password = $request->input('password');

        echo $email;
        echo "<br>";
        echo $password;
    }

    public function logout(){
        return view('logout');
    }
}