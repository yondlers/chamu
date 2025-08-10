<?php

namespace App\Models;

use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [

        'active',
        'subject',
        'body',
        'recipient_id',
        'cc',
        'status',
        'sent_at',
        'document_id',
        'team_id',
    ];

    /**
     * Relationships
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }


    public static function htmlForm(Communication $communication)
    {
        return '
            <!-- Subject -->
            ' . HtmlForm::generateInput('subject', 'text', $communication->subject, true) . '
            <!-- Body -->
            ' . HtmlForm::generateInput('body', 'text', $communication->body, true) . '
            <!-- Recipient -->
            ' . HtmlForm::generateSelect('recipient_id', Tenant::where('team_id', auth()->user()->current_team_id)->get(), $communication->recipient_id, true) . '
            <!-- CC -->
            ' . HtmlForm::generateInput('cc', 'email', $communication->cc, false) . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }

}
