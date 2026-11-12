<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Language Lines
    |--------------------------------------------------------------------------
    |
    | UI labels shared across the application. Add a matching lang/lo/app.php
    | to translate the interface into Lao.
    |
    */

    'name' => 'LCIS–AMIS Project Match',
    'short_name' => 'LCIS–AMIS Match',

    'nav' => [
        'dashboard' => 'Dashboard',
        'users' => 'Users',
        'lcis_projects' => 'LCIS projects',
        'amis_contracts' => 'AMIS contracts',
        'sync' => 'Sync',
        'account' => 'My account',
        'logout' => 'Log out',
        'toggle' => 'Toggle navigation',
    ],

    'pagination' => [
        'showing' => 'Showing :from to :to of :total',
        'previous' => 'Previous page',
        'next' => 'Next page',
    ],

    'filters' => [
        'title' => 'Filter',
        'search' => 'Search',
        'province' => 'Province',
        'all_provinces' => 'All provinces',
        'source' => 'In source system',
        'all_records' => 'All records',
        'present' => 'Present',
        'missing' => 'Missing from source',
        'match_status' => 'Match status',
        'any_match_status' => 'Any match status',
        'unmatched' => 'Not matched',
        'proposed' => 'Proposed',
        'matched' => 'Matched',
        'apply' => 'Search',
        'reset' => 'Reset',
    ],

    'province_code' => 'Province :code',
    'missing_since' => 'Missing from source since :date',
    'not_available' => '—',
    'hectares' => ':value ha',
    'view' => 'View',
    'back_to_list' => 'Back to list',
    'raw_data' => 'All fields from the source (raw)',
    'synced_at' => 'Last synced :date',

    'dismiss' => 'Dismiss',
    'dashboard_placeholder' => 'Matching screens arrive in the next steps. Admins can follow both syncs on the Sync page.',

];
