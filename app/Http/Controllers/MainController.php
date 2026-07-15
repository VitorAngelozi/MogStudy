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

    public function notepost(Request $request){
        $notePost = $request->input('contentPost');

        return view('/home', [

            'notePost'=> $notePost,

        ]);
    
    }
}

