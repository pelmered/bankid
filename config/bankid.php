<?php
namespace LJSystem\BankID;

return [
    'environment' => env('BANKID_ENVIRONMENT', BankID::ENVIRONMENT_TEST),

    'environments' => [
        BankID::ENVIRONMENT_PRODUCTION => [
            'certificate' => env('BANKID_CERTIFICATE', storage_path('/certificates/bankid/prod/prod.pem')),
            'root_certificate' => env('BANKID_ROOT_CERTIFICATE', storage_path('/certificates/bankid/prod/prod_cacert.pem')),
        ],
    ],

    // Test logins for logging in without BankID.
    // For example if you make an app and want to Apple and Google to be able to test the app without BankID.
    'test_logins' => [
        //user_uuid => 'password'

    ]
];
