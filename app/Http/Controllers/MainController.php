<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MainController extends Controller
{
    public function home(){
        $user = session('user');

        return view('home',[
            'user' => $user,
        ]);
    }

    public function index(){
        echo "Im inside the app!"
    }

    public function note(){
        echo "Im creating a new note!"
    }
}

