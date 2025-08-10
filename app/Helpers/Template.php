<?php

namespace App\Helpers;

use App\Models\Lease;

class Template
{

    public static function generateContract(Lease $lease)
    {
        $template = $lease->leaseTemplate->template_html;

        if ($lease->signatureAuthorization->name) {
            $landlord_name = $lease->signatureAuthorization->name;
        }else{
            $landlord_name = $lease->signatureAuthorization->user->name;
        }

        $tenant_address = $lease->tenant->address_line_1 . ', ' . $lease->tenant->address_line_2 . ', ' . $lease->tenant->suburb . ', ' . $lease->tenant->city . ', ' . $lease->tenant->province;

        $property = $lease->property;
        $property_unit = $property;
        $property_name = $property?->name;

        if ($lease->unit) {
            $property_name = $lease->unit->unit_number;
            $property_unit = $lease->unit;
            $property = $lease->unit->property;
        }

        $property_address = $property->address_line_1 . ', ' . $property->address_line_2 . ', ' . $property->suburb . ', ' . $property->city . ', ' . $property->province;

        $tenant_signature = '<img width="100px" src="' . $lease->tenant_signature . '" />';
        $landlord_signature = '<img width="100px" src="' . $lease->signatureAuthorization->signature_image . '" />';

        $data = [

            '[[TODAY_DATE]]' => date('Y-m-d'),
            '[[LEASE_START_DATE]]' => $lease->start_date,
            '[[LEASE_END_DATE]]' => $lease->end_date,

            '[[LEASE_NAME]]' => $lease->name,
            '[[PROPERTY_NAME]]' => $property_name,

            '[[LANDLORD_NAME]]' => $landlord_name,

            '[[TENANT_NAME]]' => $lease->tenant->name,
            '[[TENANT_ADDRESS]]' => $tenant_address,

            '[[OCCUPATION]]' => $lease->tenant->occupation,
            '[[PROPERTY_ADDRESS]]' => $property_address,
            '[[RENT_AMOUNT]]' => $lease->rent_amount,
            '[[RENT_DUE_DATE]]' => $lease->debit_date,

            '[[DEPOSIT_AMOUNT]]' => $property_unit->deposit_amount,
            '[[KEY_DEPOSIT_AMOUNT]]' => $property_unit->key_deposit_amount,
            '[[UTILITY_DEPOSIT_AMOUNT]]' => $property_unit->utility_deposit_amount,
            '[[DAMAGE_DEPOSIT_AMOUNT]]' => $property_unit->damage_deposit_amount,
            '[[FURNITURE_DEPOSIT_AMOUNT]]' => $property_unit->furniture_deposit_amount,

            '[[TENANT_SIGNATURE]]' => $tenant_signature,
            '[[LANDLORD_SIGNATURE]]' => $landlord_signature,

            '[[NOTICE_PERIOD]]' => $lease->notice_period,
            '[[UTILITY_PAYER]]' => $lease->utility_payer,
            '[[LATE_FEE]]' => $lease->late_fee,
            '[[LATE_FEE_DAYS]]' => $lease->late_fee_days,
            '[[NUMBER_OF_OCCUPANTS]]' => $lease->tenant->number_of_occupants,

            '[[PAGE_BREAKER]]' => '<div style="page-break-after: always;"></div>',


        ];

        foreach ($data as $placeholder => $value) {
            $template = str_replace($placeholder, $value, $template);
        }

        return $template;


    }

}
