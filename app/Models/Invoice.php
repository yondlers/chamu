<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{

    use HasFactory;

    protected $fillable = [
        'active',
        'name',
        'account_type',
        'description',
        'lease_id',
        'transaction_id',
        'type',
        'invoice_date',
        'amount',
        'balance',
        'notes',
        'document_id',
        'team_id',
    ];

    // Relationships
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Additional Methods
    public function markAsInactive()
    {
        $this->active = false;
        $this->save();
    }

    public static function htmlFilter($from_date, $to_date, $tenant_id, $property_id)
    {
        return '
            <div class="flex space-x-4 items-center">
                <div class="w-full">
                    <!-- From Date -->
                    ' . HtmlForm::generateInput('from_date', 'date', $from_date, false) . '
                </div>
                <div class="w-full">
                    <!-- To Date -->
                    ' . HtmlForm::generateInput('to_date', 'date', $to_date, false) . '
                </div>
            </div>
            
        

            <div class="flex space-x-4 items-center">
                <div class="w-full">
                    <!-- Tenant -->
                    ' . HtmlForm::generateSelect('tenant_id', Tenant::where('team_id', auth()->user()->current_team_id)->get(), $tenant_id, false) . '
                </div>
                <div class="w-full">
                    <!-- Property -->
                    ' . HtmlForm::generateSelect('property_id', Property::where('team_id', auth()->user()->current_team_id)->get(), $property_id, false) . '
                </div>
            </div>
            
            <br/>

            <!-- Submit -->
            ' . HtmlForm::submitButtonInput('Filter') . '
        ';
    }

    public static function htmlForm(Invoice $invoice)
    {
        return '
            <!-- Name -->
            ' . HtmlForm::generateInput('name', 'text', $invoice->name, true) . '
            <!-- Lease -->
            ' . HtmlForm::generateSelect('lease_id', Lease::where('team_id', auth()->user()->current_team_id)->get(), $invoice->lease_id, true) . '
            <!-- Account Type -->
            ' . HtmlForm::generateSelect('account_type', Constants::ACCOUNT_TYPE, $invoice->account_type, true) . '
            <!-- Description -->
            ' . HtmlForm::generateInput('description', 'text', $invoice->description, true) . '
            <!-- Type -->
            ' . HtmlForm::generateSelect('type', Constants::DEBIT_CREDIT, $invoice->type, true) . '
            <!-- Amount -->
            ' . HtmlForm::generateInput('amount', 'number', $invoice->amount, true) . '
            <!-- Invoice Date -->
            ' . HtmlForm::generateInput('invoice_date', 'date', $invoice->invoice_date, true) . '
            <!-- Notes -->
            ' . HtmlForm::generateInput('notes', 'date', $invoice->notes, false) . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }
}
