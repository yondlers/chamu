<?php

namespace App\Http\Controllers;

use App\Models\Communication;
use Illuminate\Http\Request;
// use

class CommunicationController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();
        $communications = Communication::where('team_id', $user->current_team_id)->get();

        return view('communications.index', [
            'communications' => $communications
        ]);
    }

    public function create()
    {
        return view('communications.create', [
            'communicationHtml' => Communication::htmlForm(new Communication())
        ]);
    }

    public function store(Request $request){

        $user = auth()->user();

        Communication::create([
            'active' => true,
            'subject' => $request['subject'],
            'body' => $request['body'],
            'recipient_id' => $request['recipient_id'],
            'cc' => $request['cc'],
            'status' => 'pending',
            'sent_at' => now(),
            'team_id' => $user->current_team_id,
        ]);

        AuditLog::log('Sent Communication', 'Recipient: ' . $request['recipient_id'] . ' and Subject: ' . $request['subject']);

        return redirect()->route('communications.index')->with('success', 'Communication updated.');

    }

    public function show(Communication $communication)
    {

        return view('communications.show', [
            'communication' => $communication
        ]);
    }

    public function edit(Communication $communication)
    {
        return view('communications.edit', [
            'communicationHtml' => Communication::htmlForm($communication)
        ]);
    }

    public function update(Request $request, Communication $communication)
    {
        $user = auth()->user();

        $communication->subject = $request['subject'];
        $communication->body = $request['body'];
        $communication->recipient_id = $request['recipient_id'];
        $communication->cc = $request['cc'];
        $communication->updated_by = $user->id;
//        $communication->status = $request['status'];
//        $communication->sent_at = $request['sent_at'];

        $communication->update();


    }
}
