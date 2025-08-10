<?php

namespace App\Models;

use App\Helpers\Constants;
use App\Helpers\HtmlForm;
use App\Helpers\LookUp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'active',
        'property_id',
        'unit_type_id',
        'asset_information_id',
        'monthly_rent',
        'unit_number',
        'team_id',
    ];

    /**
     * Data Manipulation
     * Please don't remove, being used in blade
     */
    public function getNameAttribute()
    {
        return $this->unit_number;
    }

    public function getAddressAttribute()
    {
        return $this->property->address_line_1 . ', ' . $this->property->address_line_2 . ', ' . $this->property->suburb->name . ', ' . $this->property->city->name . ', ' . $this->property->province->name;

    }

    // Relationships

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'unit_id');
    }

    /**
     * Get the Team who this unit belongs to.
     */
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function assetInformation()
    {
        return $this->belongsTo(AssetInformation::class, 'asset_information_id');
    }

    /**
     * Get the property that owns this unit.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the unit type for this unit.
     */
    public function unitType()
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }


    public static function htmlForm(Unit $unit)
    {
        $properties = Property::where('team_id', auth()->user()->current_team_id)->get();
        $properties_filter = [];
        foreach ($properties as $property) {
            if ($property->units[0]->unit_type_id == 1)
            {
                continue;
            }
            array_push($properties_filter, $property);
        }

        $disable = false;
        if($unit->unit_type_id == 1)
        {
            $disable = true;
        }

        return '
            <!-- Unit Name -->
            ' . HtmlForm::generateInput('unit_number', 'text', $unit->unit_number, true) . '
            <!-- Unit Type -->
            ' . HtmlForm::generateSelect('unit_type_id', LookUp::UNIT_TYPES, $unit->unit_type_id, true, $disable) . '

            <!-- *disclaimer -->
            ' . HtmlForm::generateDisclaimer('Plesse select the property the unit belongs to.', "") . '

            <!-- Property -->
            ' . HtmlForm::generateSelect('property_id', $properties_filter, $unit->property_id, true) . '



            <!-- Monthly Rent -->
            ' . HtmlForm::generateInput('monthly_rent', 'decimal', $unit->monthly_rent, false) . '

            <!--Submit -->
            ' . HtmlForm::submitButtonInput() . '


        ';
    }

    public static function validator(Unit $unit)
    {
        if($unit->unit_number)
        {
            return 'Please enter a valid Unit Number';
        }
        if($unit->unit_type_id)
        {
            return 'Please enter a valid Unit Type';
        }
        if($unit->property_id)
        {
            return 'Please enter a valid Property';
        }
        if($unit->monthly_rent)
        {
            return 'Please enter a valid Monthly Rent';
        }

        return true;
    }
}
