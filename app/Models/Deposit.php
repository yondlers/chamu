<?php

namespace App\Models;

use App\Helpers\HtmlForm;
use App\Helpers\LookUp;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    //
    protected $fillable = [
        'unit_id',
        'lease_id',
        'deposit_amount',
        'deposit_name',
        'team_id'
    ];

    /**
     * Get the unit that owns this unit.
     */
    public function unit()
    {
        return $this->belongsTo(Property::class, 'unit_id');
    }



    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public static function htmlForm(Deposit $deposit)
    {
        return '
            <!-- Deposit Name -->
            ' . HtmlForm::generateInput('deposit_name', 'text', $deposit->deposit_name, true) . '
            <!-- Deposit Amount -->
            ' . HtmlForm::generateInput('deposit_amount', 'decimal', $deposit->deposit_amount, true) . '
            <!-- Unit -->
            ' . HtmlForm::generateSelect('unit_id', Unit::where('team_id', auth()->user()->current_team_id)->get(), $deposit->unit_id, true) . '


            <!--Submit -->
            ' . HtmlForm::submitButtonInput() . '


        ';
    }

}
