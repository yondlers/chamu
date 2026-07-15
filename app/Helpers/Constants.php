<?php

namespace App\Helpers;

class Constants
{

    public const YES_NO = [
        0 => "No",
        1 => "Yes"
    ];

    public const RENTAL_TYPES = [
        'Whole' => 'Whole',
        'Units' => 'Units',
    ];

    public const UTILITY_TYPES = [
        'Whole' => 'Prep',
        'Units' => 'Units',
    ];

    public const DEBIT_DATES = [
        1 => '1st of every month',
        15 => '15th of every month',
        20 => '20th of every month',
        25 => '25th of every month',
        31 => 'End of every month',
    ];

    public const DEBIT_CREDIT = [
        'Debit' => 'Debit',
        'Credit' => 'Credit'
    ];

    public const ACCOUNT_TYPE = [
        'Payment Received' => 'Payment Received',
        'Rent Charged' => 'Rent Charged',
        'Rent Discount' => 'Rent Discount',
        'Late Fee' => 'Late Fee',
        'Security Deposit Charged' => 'Security Deposit Charged',
        'Security Deposit Refund' => 'Security Deposit Refund',
        'Maintenance Charge' => 'Maintenance Charge',
        'Maintenance Reimbursement' => 'Maintenance Reimbursement',
        'Cleaning Fee' => 'Cleaning Fee',
        'Utilities Charged' => 'Utilities Charged',
        'Utilities Payment' => 'Utilities Payment',
        'Penalty Fee' => 'Penalty Fee',
        'Property Insurance' => 'Property Insurance',
        'Property Tax' => 'Property Tax',
        'Property Management Fee' => 'Property Management Fee',
        'Refund' => 'Refund',
        'Other' => 'Other'
    ];


}


