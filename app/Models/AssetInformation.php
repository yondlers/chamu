<?php

namespace App\Models;

use App\Helpers\LookUp;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Constants;
use App\Helpers\HtmlForm;


class AssetInformation extends Model
{
    //

    protected $table = 'asset_informations';

    protected $fillable = [
        'is_estate_or_complex',
        'number_of_units',
        'estate_name',
        'estate_description',
        'gated_community',
        'has_clubhouse',
        'has_gym',
        'has_tennis_court',
        'has_golf_course',
        'has_communal_pool',
        'has_communal_garden',
        'has_communal_park',
        'has_communal_braai',
        'has_communal_area',
        'has_parking',
        'is_complex',
        'complex_name',
        'complex_description',
        'number_of_buildings_in_complex',
        'is_property',
        'number_of_bathrooms',
        'number_of_garages',
        'number_of_bedrooms',
        'number_of_kitchens',
        'number_of_parking',
        'out_buildings',
        'year_built',
        'number_of_floors',
        'has_fireplace',
        'has_study',
        'has_laundry_room',
        'has_storage_room',
        'is_room',
        'room_size_sqm',
        'room_features',
        'number_of_beds_in_room',
        'has_private_bathroom',
        'has_private_kitchen',
        'is_room_sharing',
        'number_of_occupants_in_room',
        'room_sharing_gender_preference',
        'room_sharing_rules',
        'is_furnished',
        'is_pet_friendly',
        'has_disability_access',
        'has_pool',
        'has_garden',
        'has_balcony',
        'security_features',
        'has_air_conditioning',
        'has_heating',
        'has_built_in_cupboards',
        'has_braai_area',
        'has_biometric',
        'has_intercom_system',
        'has_electic_fence',
        'has_security',
        'has_cctv',
        'has_alarm_system',
        'has_armed_response',
        'km_from_hospital',
        'km_from_school',
        'km_from_police',
        'km_from_mall',
        'has_wifi',
        'electricity_meter',
        'water_meter',
        'utility_type',
        'fiber_ready',
        'gas',
        'backup_power',
        'solar_panels',
        'borehole',

        'team_id'
    ];

    public static function htmlListingShow(AssetInformation $asset_information)
    {
        return '
            ' . HtmlForm::generateDisclaimer("erf size: " . $asset_information->erf_size, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '


            ' . HtmlForm::generateDisclaimer("number of bedrooms: " . $asset_information->number_of_bedrooms, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("number of bathrooms: " . $asset_information->number_of_bathrooms, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("number of garages: " . $asset_information->number_of_garages, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("number of kitchens: " . $asset_information->number_of_kitchens, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("number of parking: " . $asset_information->number_of_parking, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("is furnished: " . $asset_information->is_furnished, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("is pet friendly: " . $asset_information->is_pet_friendly, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("has garden: " . $asset_information->has_garden, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("has pool: " . $asset_information->has_pool, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("has balcony: " . $asset_information->has_balcony, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("security features: " . $asset_information->security_features, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("km from hospital: " . $asset_information->km_from_hospital, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("km from school: " . $asset_information->km_from_school, "text-1xl font-bold") . '
            ' . HtmlForm::generateLineBreaker() . '

            ' . HtmlForm::generateDisclaimer("km from mall: " . $asset_information->km_from_mall, "text-1xl font-bold") . '

        ';
    }

    public static function htmlListingForm(AssetInformation $asset_information)
    {
        return '

            ' . HtmlForm::generateInput("erf_size", "number", $asset_information->erf_size, true) . '
            ' . HtmlForm::generateInput("number_of_bedrooms", "number", $asset_information->number_of_bedrooms, true) . '
            ' . HtmlForm::generateInput("number_of_bathrooms", "number", $asset_information->number_of_bathrooms, true) . '
            ' . HtmlForm::generateInput("number_of_garages", "number", $asset_information->number_of_garages, true) . '
            ' . HtmlForm::generateInput("number_of_kitchens", "number", $asset_information->number_of_kitchens, true) . '
            ' . HtmlForm::generateInput("number_of_parking", "number", $asset_information->number_of_parking, true) . '

            ' . HtmlForm::generateSelect("is_furnished", Constants::YES_NO, $asset_information->is_furnished, true) . '
            ' . HtmlForm::generateSelect("is_pet_friendly", Constants::YES_NO, $asset_information->pet_friendly, true) . '
            ' . HtmlForm::generateSelect("has_garden", Constants::YES_NO, $asset_information->garden, true) . '
            ' . HtmlForm::generateSelect("has_pool", Constants::YES_NO, $asset_information->pool, true) . '
            ' . HtmlForm::generateSelect("has_balcony", Constants::YES_NO, $asset_information->balcony, true) . '

            ' . HtmlForm::generateInput("security_features", "text", $asset_information->security_features, false) . '
            ' . HtmlForm::generateInput("km_from_hospital", "number", $asset_information->km_from_hospital, false) . '
            ' . HtmlForm::generateInput("km_from_school", "number", $asset_information->km_from_school, false) . '
            ' . HtmlForm::generateInput("km_from_mall", "number", $asset_information->km_from_mall, false) . '

            ' . HtmlForm::submitButtonInput() . '
        ';
    }


    public static function htmlForm(AssetInformation $asset_information)
    {

        return '

            ' . HtmlForm::generateSelect("property_type", [1 => "Multiple Units (Complex, Estate)", 2 => "Single Unit (House, Apartment)"], null, true) . '

            ' . HtmlForm::generateInput("number_of_units", "number", $asset_information->number_of_units, false) . '
            ' . HtmlForm::generateInput("name", "text", $asset_information->estate_name, false) . '
            ' . HtmlForm::generateInput("estate_description", "text", $asset_information->estate_description, false) . '

            ' . HtmlForm::generateSelect("gated_community", Constants::YES_NO, $asset_information->gated_community, false) . '
            ' . HtmlForm::generateSelect("has_clubhouse", Constants::YES_NO, $asset_information->has_clubhouse, false) . '
            ' . HtmlForm::generateSelect("has_gym", Constants::YES_NO, $asset_information->has_gym, false) . '
            ' . HtmlForm::generateSelect("has_tennis_court", Constants::YES_NO, $asset_information->has_tennis_court, false) . '
            ' . HtmlForm::generateSelect("has_golf_course", Constants::YES_NO, $asset_information->has_golf_course, false) . '
            ' . HtmlForm::generateSelect("has_communal_pool", Constants::YES_NO, $asset_information->has_communal_pool, false) . '
            ' . HtmlForm::generateSelect("has_communal_garden", Constants::YES_NO, $asset_information->has_communal_garden, false) . '
            ' . HtmlForm::generateSelect("has_communal_braai", Constants::YES_NO, $asset_information->has_communal_braai, false) . '
            ' . HtmlForm::generateSelect("has_communal_area", Constants::YES_NO, $asset_information->has_communal_area, false) . '
            ' . HtmlForm::generateSelect("has_parking", Constants::YES_NO, $asset_information->has_parking, false) . '
            ' . HtmlForm::generateInput("complex_description", "text", $asset_information->complex_description, false) . '
            ' . HtmlForm::generateInput("number_of_buildings_in_complex", "number", $asset_information->number_of_buildings_in_complex, false) . '


            ' . HtmlForm::generateInput("number_of_bathrooms", "number", $asset_information->number_of_bathrooms, false) . '
            ' . HtmlForm::generateInput("number_of_garages", "number", $asset_information->number_of_garages, false) . '
            ' . HtmlForm::generateInput("number_of_bedrooms", "number", $asset_information->number_of_bedrooms, false) . '
            ' . HtmlForm::generateInput("number_of_kitchens", "number", $asset_information->number_of_kitchens, false) . '
            ' . HtmlForm::generateInput("number_of_parking", "number", $asset_information->number_of_parking, false) . '

            ' . HtmlForm::generateInput("out_buildings", "text", $asset_information->out_buildings, false) . '
            ' . HtmlForm::generateInput("year_built", "date", $asset_information->year_built, false) . '
            ' . HtmlForm::generateInput("number_of_floors", "number", $asset_information->number_of_floors, false) . '


            ' . HtmlForm::generateSelect("has_fireplace", Constants::YES_NO, $asset_information->has_fireplace, false) . '
            ' . HtmlForm::generateSelect("has_study", Constants::YES_NO, $asset_information->has_study, false) . '
            ' . HtmlForm::generateSelect("has_laundry_room", Constants::YES_NO, $asset_information->has_laundry_room, false) . '
            ' . HtmlForm::generateSelect("has_storage_room", Constants::YES_NO, $asset_information->has_storage_room, false) . '

            ' . HtmlForm::generateInput("room_size_sqm", "number", $asset_information->room_size_sqm, false) . '
            ' . HtmlForm::generateInput("room_features", "text", $asset_information->room_features, false) . '
            ' . HtmlForm::generateInput("number_of_beds_in_room", "number", $asset_information->number_of_beds_in_room, false) . '

            ' . HtmlForm::generateSelect("has_private_bathroom", Constants::YES_NO, $asset_information->has_private_bathroom, false) . '
            ' . HtmlForm::generateSelect("has_private_kitchen", Constants::YES_NO, $asset_information->has_private_kitchen, false) . '
            ' . HtmlForm::generateSelect("is_room_sharing", Constants::YES_NO, $asset_information->is_room_sharing, false) . '

            ' . HtmlForm::generateInput("number_of_occupants_in_room", "number", $asset_information->number_of_occupants_in_room, false) . '

            ' . HtmlForm::generateSelect("room_sharing_gender_preference", LookUp::GENDERS_OPTIONS, $asset_information->room_sharing_gender_preference, false) . '
            ' . HtmlForm::generateInput("room_sharing_rules", "text", $asset_information->room_sharing_rules, false) . '

            ' . HtmlForm::generateSelect("is_furnished", Constants::YES_NO, $asset_information->is_furnished, false) . '
            ' . HtmlForm::generateSelect("is_pet_friendly", Constants::YES_NO, $asset_information->pet_friendly, false) . '
            ' . HtmlForm::generateSelect("has_disability_access", Constants::YES_NO, $asset_information->disability_access, false) . '
            ' . HtmlForm::generateSelect("has_pool", Constants::YES_NO, $asset_information->pool, false) . '
            ' . HtmlForm::generateSelect("has_garden", Constants::YES_NO, $asset_information->garden, false) . '
            ' . HtmlForm::generateSelect("has_balcony", Constants::YES_NO, $asset_information->balcony, false) . '
            ' . HtmlForm::generateInput("security_features", "text", $asset_information->security_features, false) . '
            ' . HtmlForm::generateSelect("has_air_conditioning", Constants::YES_NO, $asset_information->air_conditioning, false) . '
            ' . HtmlForm::generateSelect("has_heating", Constants::YES_NO, $asset_information->heating, false) . '
            ' . HtmlForm::generateSelect("has_built_in_cupboards", Constants::YES_NO, $asset_information->built_in_cupboards, false) . '
            ' . HtmlForm::generateSelect("has_braai_area", Constants::YES_NO, $asset_information->braai_area, false) . '
            ' . HtmlForm::generateSelect("has_biometric", Constants::YES_NO, $asset_information->has_biometric, false) . '
            ' . HtmlForm::generateSelect("has_intercom_system", Constants::YES_NO, $asset_information->has_intercom_system, false) . '
            ' . HtmlForm::generateSelect("has_electic_fence", Constants::YES_NO, $asset_information->has_electic_fence, false) . '
            ' . HtmlForm::generateSelect("has_security", Constants::YES_NO, $asset_information->has_security, false) . '
            ' . HtmlForm::generateSelect("has_cctv", Constants::YES_NO, $asset_information->has_cctv, false) . '
            ' . HtmlForm::generateSelect("has_alarm_system", Constants::YES_NO, $asset_information->has_alarm_system, false) . '
            ' . HtmlForm::generateSelect("has_armed_response", Constants::YES_NO, $asset_information->has_armed_response, false) . '
            ' . HtmlForm::generateInput("km_from_hospital", "number", $asset_information->km_from_hospital, false) . '
            ' . HtmlForm::generateInput("km_from_school", "number", $asset_information->km_from_school, false) . '
            ' . HtmlForm::generateInput("km_from_police", "number", $asset_information->km_from_police, false) . '
            ' . HtmlForm::generateInput("km_from_mall", "number", $asset_information->km_from_mall, false) . '

            ' . HtmlForm::generateSelect("has_wifi", Constants::YES_NO, $asset_information->has_wifi, false) . '
            ' . HtmlForm::generateSelect("electricity_meter", Constants::YES_NO, $asset_information->electricity_meter, false) . '
            ' . HtmlForm::generateSelect("water_meter", Constants::YES_NO, $asset_information->water_meter, false) . '
            ' . HtmlForm::generateSelect("utility_type", Constants::YES_NO, $asset_information->utility_type, false) . '
            ' . HtmlForm::generateSelect("fiber_ready", Constants::YES_NO, $asset_information->fiber_ready, false) . '
            ' . HtmlForm::generateSelect("gas", Constants::YES_NO, $asset_information->gas, false) . '
            ' . HtmlForm::generateSelect("backup_power", Constants::YES_NO, $asset_information->backup_power, false) . '
            ' . HtmlForm::generateSelect("solar_panels", Constants::YES_NO, $asset_information->solar_panels, false) . '
            ' . HtmlForm::generateSelect("borehole", Constants::YES_NO, $asset_information->borehol, false) . '

            <!-- Submit -->
            ' . HtmlForm::submitButtonInput() . '
        ';
    }
}
