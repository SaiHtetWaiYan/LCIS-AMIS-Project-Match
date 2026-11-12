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
        'matches' => 'Matches',
        'queue' => 'My queue',
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

    'dashboard' => [
        'welcome' => 'Signed in as :name (:side).',
        'unmatched_lcis' => 'LCIS projects not matched',
        'unmatched_amis' => 'AMIS contracts not matched',
        'of_total' => 'of :total in the source',
        'awaiting_me' => 'Waiting for your side',
        'awaiting_me_hint' => 'Proposals to confirm or reject',
        'needs_review' => 'Needs review',
        'needs_review_hint' => 'Active matches flagged after a sync',
        'matches' => 'Matches by status',
        'sync' => 'Data from the source systems',
        'never_synced' => 'Never synced',
        'last_sync' => 'Last sync',
        'last_success' => 'Last successful sync :ago',
        'open_queue' => 'Open my queue',
        'all_matches' => 'All matches',
    ],

];
