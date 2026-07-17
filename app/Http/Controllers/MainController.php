<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\User;

class MainController extends Controller
{
    public function home(){
        $id = session('user.id');
        $user = User::find($id)->toArray();
        $notes = User::find($id)->notes();

        dd($user);
        dd($notes);

        return view('home',[
            'user' => $user,
            'notes' => $notes,
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

