<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use App\Helpers\LookUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',

        'user_id',

        'first_name',
        'middle_name',
        'last_name',

        'id_number',
        'passport_number',

        'date_of_birth',
        'email',
        'contact_number',
        'work_number',

        'gender_id',
        'marital_status_id',
        'ethnicity_id',
        'language_id',

        'suburb_id',
        'city_id',
        'province_id',
        'country_id',

        'address_line_1',
        'address_line_2',
        'postal_code',

        'occupation',
        'employer',
        'monthly_income',
        'credit_check_passed',
        'credit_check_report',

        'number_of_occupancies',
        'has_pets',
        'special_requirements',

        'emergency_name',
        'emergency_relationship',
        'emergency_number',

        'blacklisted',

        'file_prefix',
        'id_document_file_name',
        'bank_statements_file_name',
        'proof_of_income_file_name',
        'credit_report_file_name'
    ];

    public function getNameAttribute()
    {
        $middle_name = $this->middle_name ?? ' ';
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get foreign keys
     */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class, 'marital_status_id');
    }

    public function ethnicity()
    {
        return $this->belongsTo(Ethnicity::class, 'ethnicity_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    /**
     * Get the Address for this tenant.
     */

    public function suburb()
    {
        return $this->belongsTo(Suburb::class, 'suburb_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }


    public static function htmlShow(Tenant $tenant)
    {
        return '
            <!-- Name -->
            ' . HtmlForm::generateDisclaimer($tenant->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- ID Number -->
            ' . HtmlForm::generateDisclaimer($tenant->id_number, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Passport Number -->
            ' . HtmlForm::generateDisclaimer($tenant->passport_number, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Date of Birth -->
            ' . HtmlForm::generateDisclaimer($tenant->date_of_birth, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Email -->
            ' . HtmlForm::generateDisclaimer($tenant->email, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Contact Number -->
            ' . HtmlForm::generateDisclaimer($tenant->contact_number, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Gender -->
            ' . HtmlForm::generateDisclaimer($tenant->gender->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Marital Status -->
            ' . HtmlForm::generateDisclaimer($tenant->maritalStatus->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Ethnicity -->
            ' . HtmlForm::generateDisclaimer($tenant->ethnicity->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Country -->
            ' . HtmlForm::generateDisclaimer($tenant->country->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Language -->
            ' . HtmlForm::generateDisclaimer($tenant->language->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Address Line 1 -->
            ' . HtmlForm::generateDisclaimer($tenant->address_line_1, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Address Line 2 -->
            ' . HtmlForm::generateDisclaimer($tenant->address_line_2, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Suburb -->
            ' . HtmlForm::generateDisclaimer($tenant->suburb->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- City -->
            ' . HtmlForm::generateDisclaimer($tenant->city->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Province -->
            ' . HtmlForm::generateDisclaimer($tenant->province->name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Postal Code -->
            ' . HtmlForm::generateDisclaimer($tenant->postal_code, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Occupation -->
            ' . HtmlForm::generateDisclaimer($tenant->occupation, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Employer -->
            ' . HtmlForm::generateDisclaimer($tenant->employer, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Monthly Income -->
            ' . HtmlForm::generateDisclaimer($tenant->monthly_income, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Number of Occupancies -->
            ' . HtmlForm::generateDisclaimer($tenant->number_of_occupancies, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Has Pets -->
            ' . HtmlForm::generateDisclaimer($tenant->has_pets, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Special Requirements -->
            ' . HtmlForm::generateDisclaimer($tenant->special_requirements, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Emergency Name -->
            ' . HtmlForm::generateDisclaimer($tenant->emergency_name, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Emergency Relationship -->
            ' . HtmlForm::generateDisclaimer($tenant->emergency_relationship, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
            <!-- Emergency Number -->
            ' . HtmlForm::generateDisclaimer($tenant->emergency_number, "text-2xl font-semibold") . '
            ' . HtmlForm::generateLineBreaker() . '
        ';
    }

    /**
     *
     */
    public static function htmlForm(Tenant $tenant)
    {
        return '

            <!-- First Name -->
            ' . HtmlForm::generateInput('first_name', 'text', $tenant->first_name, true) . '
            <!-- Middle Name -->
            ' . HtmlForm::generateInput('middle_name', 'text', $tenant->middle_name, false) . '
            <!-- Last Name -->
            ' . HtmlForm::generateInput('last_name', 'text', $tenant->last_name, true) . '

            <!-- ID Number -->
            ' . HtmlForm::generateInput('id_number', 'text', $tenant->id_number, false) . '
            <!-- Passport Number -->
            ' . HtmlForm::generateInput('passport_number', 'text', $tenant->passport_number, false) . '

            <!-- Date of Birth -->
            ' . HtmlForm::generateInput('date_of_birth', 'date', $tenant->date_of_birth, true) . '
            <!-- Email -->
            ' . HtmlForm::generateInput('email', 'email', $tenant->email, true) . '
            <!-- Contact Number -->
            ' . HtmlForm::generateInput('contact_number', 'text', $tenant->contact_number, true) . '
            <!-- Work Number -->
            ' . HtmlForm::generateInput('work_number', 'text', $tenant->work_number, false) . '

            <!-- Gender -->
            ' . HtmlForm::generateSelect('gender_id', LookUp::GENDERS_OPTIONS, $tenant->gender_id, true) . '
            <!-- Marital Status -->
            ' . HtmlForm::generateSelect('marital_status_id', LookUp::MARITAL_STATUS, $tenant->marital_status_id, true) . '
            <!-- Ethnicity -->
            ' . HtmlForm::generateSelect('ethnicity_id', LookUp::ETHNICITY_TYPES, $tenant->ethnicity_id, true) . '
            <!-- Language -->
            ' . HtmlForm::generateSelect('language_id', LookUp::LANGAUGE_TYPES, $tenant->language_id, true) . '
            <!-- Address Line 1 -->
            ' . HtmlForm::generateInput('address_line_1', 'text', $tenant->address_line_1, true) . '
            <!-- Address Line 2 -->
            ' . HtmlForm::generateInput('address_line_2', 'text', $tenant->address_line_2, false) . '
            <!-- Suburb -->
            ' . HtmlForm::generateSelect('suburb_id', LookUp::SUBURB_OPTIONS, $tenant->suburb_id, true) . '
            <!-- City -->
            ' . HtmlForm::generateSelect('city_id', LookUp::CITIES_OPTIONS, $tenant->city_id, true) . '
            <!-- Province -->
            ' . HtmlForm::generateSelect('province_id', LookUp::PROVINCES_OPTIONS, $tenant->province_id, true) . '
            <!-- Country -->
            ' . HtmlForm::generateSelect('country_id', LookUp::COUNTRIES_OPTIONS, $tenant->country_id, true) . '

            <!-- Postal Code -->
            ' . HtmlForm::generateInput('postal_code', 'text', $tenant->postal_code, true) . '
            <!-- Occupation -->
            ' . HtmlForm::generateInput('occupation', 'text', $tenant->occupation, true) . '
            <!-- Employer -->
            ' . HtmlForm::generateInput('employer', 'text', $tenant->employer, true) . '
            <!-- Monthly Income -->
            ' . HtmlForm::generateInput('monthly_income', 'decimal', $tenant->monthly_income, true) . '

            <!-- Number of Occupancies -->
            ' . HtmlForm::generateInput('number_of_occupancies', 'number', $tenant->number_of_occupancies, true) . '

            <!-- Has Pets -->
            ' . HtmlForm::generateSelect('has_pets', Constants::YES_NO, $tenant->has_pets, true) . '
            <!-- Special Requirements -->
            ' . HtmlForm::generateInput('special_requirements', 'text', $tenant->special_requirements, false) . '

            <!-- Emergency -->
            ' . HtmlForm::generateInput('emergency_name', 'text', $tenant->emergency_name, false) . '
            ' . HtmlForm::generateInput('emergency_relationship', 'text', $tenant->emergency_relationship, false) . '
            ' . HtmlForm::generateInput('emergency_number', 'text', $tenant->emergency_number, false) . '

            <!-- Upload Document File -->
            ' . HtmlForm::documentUploadInputs() . '
            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }

}
