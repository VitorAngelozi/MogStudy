<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function store(Request $request, User $user)
    {
        abort_if($request->user()->id === $user->id, 422, 'Voce nao pode adicionar a si mesmo.');

        $exists = Friendship::query()
            ->where(function ($query) use ($request, $user) {
                $query->where('requester_id', $request->user()->id)
                    ->where('addressee_id', $user->id);
            })
            ->orWhere(function ($query) use ($request, $user) {
                $query->where('requester_id', $user->id)
                    ->where('addressee_id', $request->user()->id);
            })
            ->exists();

        abort_if($exists, 422, 'Ja existe um pedido ou amizade com esse usuario.');

        Friendship::create([
            'requester_id' => $request->user()->id,
            'addressee_id' => $user->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        return redirect()->route('dashboard')->with('status', 'Pedido de amizade enviado.');
    }

    public function accept(Request $request, Friendship $friendship)
    {
        abort_unless($friendship->addressee_id === $request->user()->id, 403);

        $friendship->forceFill([
            'status' => Friendship::STATUS_ACCEPTED,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Pedido de amizade aceito.');
    }

    public function destroy(Request $request, Friendship $friendship)
    {
        abort_unless($friendship->involves($request->user()->id), 403);

        $friendship->delete();

        return redirect()->route('dashboard')->with('status', 'Amizade atualizada.');
    }
}
