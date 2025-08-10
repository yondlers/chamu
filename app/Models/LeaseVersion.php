<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaseVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'lease_id',
        'url_code',

        'lease_template_id',
        'signature_authorization_id',
        'debit_date',
        'rent_amount',
        'type',
        'name',
        'url_code',
        'tenant_signed_on',
        'tenant_signed_ip_address',
        'tenant_signed_device',
        'contract',

        'file_prefix',
        'lease_contract_file',

        'start_date',
        'end_date',
        'team_id',


    ];

    public function lease()
    {
        return $this->belongsTo(Lease::class, 'lease_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

 

    /**
     * Html Form
     */
    public static function htmlForm(LeaseVersion $lease_version)
    {
        return '
            <!-- Lease -->
            ' . HtmlForm::generateSelect($lease_version, Lease::where('team_id', auth()->user()->current_team_id)->get(), $lease_version->lease_id, true) . '

            <div>
                <!-- Lease Template -->
                ' . HtmlForm::generateSelect('lease_template_id', LeaseTemplate::where('team_id', auth()->user()->current_team_id)->get(),  $lease_version->lease_template_id, true). '
            </div>
            <div>
                <!-- Lease Contract File -->
                ' .HtmlForm::generateInput('lease_contract_file', 'file', null, false) . '
            </div>

            <!-- Signature Authorization -->
            ' . HtmlForm::generateSelect('signature_authorization_id', SignatureAuthorization::where('team_id', auth()->user()->current_team_id)->get(), $lease_version->signature_authorization_id, true) . '
            <!-- Name -->
            ' . HtmlForm::generateInput('name', 'text', $lease_version->name, true) . '
            <!-- Debit Date -->
            ' . HtmlForm::generateSelect('debit_date', Constants::DEBIT_DATES, $lease_version->debit_date, true) . '
            <!-- Rent Amount -->
            ' . HtmlForm::generateInput('rent_amount', 'number', $lease_version->rent_amount, true) . '
            <!-- Start Date -->
            ' . HtmlForm::generateInput('start_date', 'date', $lease_version->start_date, true) . '
            <!-- End Date -->
            ' . HtmlForm::generateInput('end_date', 'date', $lease_version->end_date, true) . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }


}
