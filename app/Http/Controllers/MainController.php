<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;

class MainController extends Controller
{
    public function home(){
        $user = session('user');

        return view('home',[
            'user' => $user,
        ]);
    }

    public function notepost(Request $request){

        $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'text' =>  ['required', 'string', 'max:3000'],
        ]);

        Note::create([

            'user_id' => auth()->id(),
            'title' => $request->input('title'),
            'text' => $request->input('text'),

        ]);

        return redirect('/home'); 
    }
}

