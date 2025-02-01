<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scripts
    |--------------------------------------------------------------------------
    |
    | The namespace that scrippy will use to store and find your scripts.
    |
    */

    'script_namespace' => 'App\Scripts',
    'script_path' => app_path('Scripts'),
    'requires_proof' => true,

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | An array of environments that scrippy will run your scripts on.
    |
    */

    'run_script_on' => [
        'local',
        'staging',
        'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    |
    | Wether to cleanup the scripts after they are run and if so, which environment triggers the cleanup.
    | A cleanup means that a Pull Request is created to remove the script from the repository.
    |
    */

    'cleanup' => [
        'run' => env('SCRIPPY_SHOULD_CLEANUP', true),
        'environment' => env('SCRIPPY_CLEANUP_ENVIRONMENT', 'production'),
        'github' => [
            'branch_to_cleanup_against' => env('SCRIPPY_GITHUB_BRANCH_TO_CLEANUP_AGAINST', default: 'main'),
            'reviewers' => env('SCRIPPY_GITHUB_REVIEWERS', []),
        ],
    ],
];
