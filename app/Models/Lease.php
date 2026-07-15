<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lease extends Model
{
    use HasFactory;

    protected $fillable = [

        'active',
        'unit_id',
        'tenant_id',
        'lease_template_id',
        'late_fee',
        'late_fee_days',
        'utility_payer',
        'notice_period',
        'debit_date',
        'rent_amount',
        'type',
        'name',
        'url_code',
        'file_prefix',
        'lease_contract_file',
        'tenant_signature',
        'tenant_signed_at',
        'tenant_signed_ip_address',
        'tenant_signed_device',
        'contract',
        'start_date',
        'end_date',
        'signature_authorization_id',
        'team_id',
    ];

    public function leaseVersions()
    {
        return $this->hasMany(LeaseVersion::class);
    }


    /**
     * Relationships
     */

    public function signatureAuthorization()
    {
        return $this->belongsTo(SignatureAuthorization::class, 'signature_authorization_id');
    }

    public function leaseTemplate()
    {
        return $this->belongsTo(LeaseTemplate::class, 'lease_template_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }


    /**
     * Html Form
     */
    public static function htmlForm(Lease $lease)
    {
        $tenants = TeamTenant::where("team_id", auth()->user()->current_team_id)->get();
        $tenants_list = [];
        foreach ($tenants as $tenant) {
            array_push($tenants_list, $tenant->tenant);
        }

        $team = Team::getTeamList();

        return '
            <!-- Name -->
            ' . HtmlForm::generateInput("name", "text", $lease->name, true) . '

            <!-- Unit -->
            ' . HtmlForm::generateSelect("unit_id", Unit::where("team_id", auth()->user()->current_team_id)->get(), $lease->unit_id, true) . '
            <!-- Tenant -->
            ' . HtmlForm::generateSelect("tenant_id", $tenants_list, $lease->tenant_id, true) . '

            <div>
                <!-- Lease Template -->
                ' . HtmlForm::generateSelect("lease_template_id", LeaseTemplate::where("team_id", auth()->user()->current_team_id)->get(),  $lease->lease_template_id, false). '
            </div>
            <hr>
            <div>
                <!-- Disclamir -->
                ' . HtmlForm::generateDisclaimer("You can upload a signed lease here.", "text-md") . '
                <!-- Lease Contract File -->
                ' .HtmlForm::generateInput("lease_contract_file", "file", null, false) . '
            </div>

            <!-- Late Fee -->
            ' . HtmlForm::generateInput("late_fee", "number", $lease->late_fee, false) . '
            <!-- Late Fee Days -->
            ' . HtmlForm::generateInput("late_fee_days", "number", $lease->late_fee_days, false) . '

            <!-- Utility Payer -->
            ' . HtmlForm::generateSelect("utility_payer", ['tenant', 'owner'], $lease->utility_payer, true) . '
            <!-- Notice Period -->
            ' . HtmlForm::generateInput("notice_period", "number", $lease->notice_period, false) . '

            <!-- Debit Date -->
            ' . HtmlForm::generateSelect("debit_date", Constants::DEBIT_DATES, $lease->debit_date, true) . '
            <!-- Rent Amount -->
            ' . HtmlForm::generateInput("rent_amount", "number", $lease->rent_amount, true) . '

            <!-- Type -->
            ' . HtmlForm::generateSelect("type", ['initial' => "Initial", 'amendment' => "Amendment", 'extension' => "Extension"], $lease->type, true) . '

            <!-- Signature Authorization -->
            ' . HtmlForm::generateSelect("signature_authorization_id", $team, $lease->signature_authorization_id, true) . '


            <!-- Start Date -->
            ' . HtmlForm::generateInput("start_date", "date", $lease->start_date, true) . '
            <!-- End Date -->
            ' . HtmlForm::generateInput("end_date", "date", $lease->end_date, true) . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }




}
