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
            //rules
            [
                'email' => 'required|email',
                'password' => 'required|min:6|max:16'

            ],
            // error messages
            [
                'email.required'=> 'The email address is mandatory.',
                'password.required'=> 'The password is mandatory.',
                'password.min'=> 'The password requires at least 6 caracters',
                'password.max'=> 'The password maximum caracters is 16.'



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