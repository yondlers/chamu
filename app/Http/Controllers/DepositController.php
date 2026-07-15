<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();
        $team = $user->currentTeam;

        $deposits = Deposit::where('team_id', $team->id)->get();

        return view('deposits.index', [
            'deposits' => $deposits,
        ]);
    }

    public function add($unit_id){
        $deposit = new Deposit();
        $deposit->unit_id = $unit_id;

        return view('deposits.create', [
            'depositHtml' => Deposit::htmlForm($deposit),
        ]);
    }

    public function create()
    {
        return view('deposits.create', [
            'depositHtml' => Deposit::htmlForm(new Deposit()),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        Deposit::create([
            'deposit_amount' => $request['deposit_amount'],
            'deposit_name' => $request['deposit_name'],

            'team_id' => $user->current_team_id,
            'unit_id' => $request['unit_id'],
        ]);

        return redirect('/deposits');
    }

    public function show(Deposit $deposit)
    {
        return view('deposits.show', [
            'deposit' => $deposit,
        ]);
    }

    public function edit(Deposit $deposit)
    {
        return view('deposits.edit', [
            'deposit' => $deposit,
            'depositHtml' => Deposit::htmlForm($deposit),
        ]);
    }

    public function update(Request $request, Deposit $deposit)
    {
        $user = auth()->user();

        $deposit->deposit_amount = $request['deposit_amount'];
        $deposit->deposit_name = $request['deposit_name'];

        $deposit->save();
    }

    public function destroy(Deposit $deposit)
    {
        $deposit->delete();

        return redirect()->route('deposits.index');

    }
}
