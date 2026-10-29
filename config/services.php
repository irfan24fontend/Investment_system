<?php
return [
    // Company bank account shown to investors for bank deposits (Islami Bank only)
    'company' => [
        'legal_name' => env('COMPANY_LEGAL_NAME', 'Inaya Supplies'),
        'address' => env('COMPANY_ADDRESS'),
        'trade_license' => env('COMPANY_TRADE_LICENSE'),
        'phone' => env('COMPANY_PHONE'),
        'email' => env('COMPANY_EMAIL'),
    ],
    'company_bank' => [
        'bank_name' => env('COMPANY_BANK_NAME', 'Islami Bank Bangladesh PLC'),
        'account_name' => env('COMPANY_BANK_ACCOUNT_NAME'),
        'account_number' => env('COMPANY_BANK_ACCOUNT_NUMBER'),
        'branch' => env('COMPANY_BANK_BRANCH'),
    ],
    'bkash' => [
        'receive_number' => env('BKASH_RECEIVE_NUMBER'), // your merchant/personal bKash number shown to investors
        'driver' => env('BKASH_DRIVER', 'fake'), // fake (TEST ONLY) | live
        'base_url' => env('BKASH_BASE_URL'),
        'app_key' => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username' => env('BKASH_USERNAME'),
        'password' => env('BKASH_PASSWORD'),
    ],
];
