<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use App\Helpers\LookUp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nette\Utils\Html;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'unit_number',

        'address_line_1',
        'address_line_2',

        'postal_code',

        'suburb_id',
        'city_id',
        'province_id',
        'country_id',

        'latitude',
        'longitude',

        'property_type_id',
        'asset_information_id',

        'property_policies',
        'team_id'
    ];

    public function getAddressAttribute()
    {
        return $this->address_line_1 . ', ' . $this->address_line_2 . ', ' . $this->suburb->name . ', ' . $this->city->name . ', ' . $this->province->name;
    }

    public function getNameAttribute()
    {
        $unit_number = '';
        if ($this->unit_number)
        {
            $unit_number = $this->unit_number . ', ';
        }

        if ($this->address_line_2)
        {
            $address_line_1 = ', ' . $this->address_line_2;
        } else{
            $address_line_1 = '';
        }

        $address_lines = $this->address_line_1 . $address_line_1;

        $address = $unit_number . $address_lines . ', ' . $this->suburb->name . ', ' . $this->city->name. ', ' . $this->province->name;

        $display_name = $this->propertyType->name . ' - ' . $address;

        return $display_name;
    }

    // Relationships

    /**
     * Get the units associated with this property.
     */
    public function units()
    {
        return $this->hasMany(Unit::class, 'property_id');
    }

    /**
     * Get the Asset Information for this property.
     */
    public function assetInformation()
    {
        return $this->belongsTo(AssetInformation::class, 'asset_information_id');
    }

    /**
     * Get the Address for this property.
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



    /**
     * Get the property type for this property.
     */
    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /**
     * Get the team of the property.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }



    public static function htmlShow(Property $property)
    {
        $whole = isset($property->units[0]) && $property->units[0]->unitType->name === 'Whole';

        $html = '
        <!-- unit number -->
        Unit Number
        ' . HtmlForm::generateDisclaimer($property->unit_number, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- address line 1 -->
        Address Line 1
        ' . HtmlForm::generateDisclaimer($property->address_line_1, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- address line 2 -->
        Address Line 2
        ' . HtmlForm::generateDisclaimer($property->address_line_2, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- postal code -->
        Postal Code
        ' . HtmlForm::generateDisclaimer($property->postal_code, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- suburb -->
        Suburb
        ' . HtmlForm::generateDisclaimer($property->suburb->name, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- city -->
        City
        ' . HtmlForm::generateDisclaimer($property->city->name, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- province -->
        Province
        ' . HtmlForm::generateDisclaimer($property->province->name, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- country -->
        Country
        ' . HtmlForm::generateDisclaimer($property->country->name, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- property type -->
        Property Type
        ' . HtmlForm::generateDisclaimer($property->propertyType->name, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
        <!-- property policies -->
        Property Policies
        ' . HtmlForm::generateDisclaimer($property->property_policies, "text-2xl font-semibold") . '
        ' . HtmlForm::generateLineBreaker() . '
    ';

        if ($whole) {

            $html .= '
                <!-- Rental -->
                Rental
                ' . HtmlForm::generateDisclaimer("R ".$property->units[0]->monthly_rent, "text-2xl font-semibold") . '
                ' . HtmlForm::generateLineBreaker() . '
                ';
        }

        return $html;
    }


    public static function htmlForm(Property $property)
    {
        $whole = isset($property->units[0]) && $property->units[0]->unitType->name === 'Whole';

        $html = '
            <!-- Unit Number -->
            ' . HtmlForm::generateInput('unit_number', 'text', $property->unit_number, false) . '

            <!-- Address Line1 -->
            ' . HtmlForm::generateInput('address_line_1', 'text', $property->address_line_1, true) . '
            <!-- Address Line2 -->
            ' . HtmlForm::generateInput('address_line_2', 'text', $property->addressLine2, false) . '
            <!-- Suburb -->
            ' . HtmlForm::generateSelect('suburb_id', LookUp::SUBURB_OPTIONS, $property->suburb_id, true) . '
            <!-- City -->
            ' . HtmlForm::generateSelect('city_id', LookUp::CITIES_OPTIONS, $property->city_id, true) . '
            <!-- Province -->
            ' . HtmlForm::generateSelect('province_id', LookUp::PROVINCES_OPTIONS, $property->province_id, true) . '
            <!-- Country -->
            ' . HtmlForm::generateSelect('country_id', LookUp::COUNTRIES_OPTIONS, $property->country_id, true) . '

            <!-- Postal Code -->
            ' . HtmlForm::generateInput('postal_code', 'text', $property->postal_code, true) . '

            <!-- Property Type -->
            ' . HtmlForm::generateSelect('property_type_id', LookUp::PROPERTY_TYPES, $property->property_type_id, false) . '


            <!-- Property Policies -->
            ' . HtmlForm::generateInput('property_policies', 'text', $property->property_policies, false) . '

        ';

        if ($whole) {

            $html .= '
                <!-- Rental -->
                ' . HtmlForm::generateInput('monthly_rent', 'number', $property->units[0]->monthly_rent, true) . '
                ' . HtmlForm::generateLineBreaker() . '
                ';
        } else {
            $html .= '
                    <!-- Rental Type -->
                    ' . HtmlForm::generateSelect('rental_type', Constants::RENTAL_TYPES, $property->rental_type, false) . '

                    <div id="whole_display">

                        <!-- Monthly Rent -->
                        ' . HtmlForm::generateInput('monthly_rent', 'text', $property->unit?->monthly_rent, true) . '

                    </div>
                ';
        }

        $html .= '
           <!--Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';

        return $html;
    }

}
