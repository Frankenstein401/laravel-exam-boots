<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default CLI Values
    |--------------------------------------------------------------------------
    |
    | Define default answers for interactive prompts to speed up setup.
    | Options:
    |   auth_method: 'jwt' or 'sanctum'
    |   test_framework: 'pest' or 'phpunit'
    |   install_docs: true or false
    |   crud_type: 'eloquent' or 'blank'
    |
    */
    'defaults' => [
        'auth_method' => env('FORGE_BOOTS_AUTH', 'jwt'),
        'test_framework' => 'pest',
        'install_docs' => true,
        'crud_type' => 'eloquent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Naming Conventions
    |--------------------------------------------------------------------------
    |
    | Suffixes appended to class names during generation.
    |
    */
    'naming' => [
        'controller_suffix' => 'Controller',
        'service_suffix' => 'Service',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Paths
    |--------------------------------------------------------------------------
    |
    | If your workspace uses custom directory layouts.
    |
    */
    'paths' => [
        'models' => app_path('Models'),
        'services' => app_path('Services'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Auto-cleanup parameters for package state backups.
    |
    */
    'backup' => [
        'enabled' => true,
        'retention_days' => 3,
    ],
];
