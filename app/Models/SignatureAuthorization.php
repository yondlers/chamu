<?php

namespace App\Models;

use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignatureAuthorization extends Model
{
    use HasFactory;

    protected $fillable = [

        'active',
        'user_id',
        'name',
        'position',
        'id_number',
        'contact_number',
        'alternative_number',
        'contact_email',
        'signature_image',
    ];

    /**
     * Relationships
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

//    public function team()
//    {
//        return $this->belongsTo(Team::class);
//    }


    public static function htmlForm($signature) {

        $user = auth()->user();

        if($signature == null)
        {
            $signature = new SignatureAuthorization();

            $signature->name = $user->name;
            $signature->contact_email = $user->email;
        }



        return
            '
            <!-- Name -->
            ' . HtmlForm::generateInput('name', 'text', $signature->name, true) . '

            <!-- Email -->
            ' . HtmlForm::generateInput('contact_email', 'email', $signature->contact_email, true) . '

            <!-- Contact Number -->
            ' . HtmlForm::generateInput('contact_number', 'text', $signature->contact_number, true) . '

            <!-- Alternative Number -->
            ' . HtmlForm::generateInput('alternative_number', 'text', $signature->alternative_number, false) . '

            <!-- Position -->
            ' . HtmlForm::generateInput('position', 'text', $signature->position, true) . '

            <!-- ID Number -->
            ' . HtmlForm::generateInput('id_number', 'text', $signature->id_number, true) . '

            <!-- Signature Image -->
            ' . HtmlForm::generateImage($signature->signature_image) . '
            <!-- Signature -->
            ' . HtmlForm::signature() . '


            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }
}
