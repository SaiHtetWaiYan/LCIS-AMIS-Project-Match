<?php

return [

    'title' => 'Sync status',

    'sources' => [
        'lcis' => 'LCIS',
        'amis' => 'AMIS',
    ],

    'source_descriptions' => [
        'lcis' => 'Investment projects and companies',
        'amis' => 'Rent-land contracts and linked companies',
    ],

    'statuses' => [
        'running' => 'Running',
        'succeeded' => 'Succeeded',
        'failed' => 'Failed',
    ],

    'fields' => [
        'source' => 'Source',
        'status' => 'Status',
        'started_at' => 'Started',
        'finished_at' => 'Finished',
        'duration' => 'Duration',
        'triggered_by' => 'Triggered by',
        'expected' => 'Expected',
        'fetched' => 'Fetched',
        'created' => 'New',
        'updated' => 'Changed',
        'unchanged' => 'Unchanged',
        'missing' => 'Missing',
        'restored' => 'Back again',
        'error' => 'Error',
        'records' => 'Records',
    ],

    'scheduler' => 'Scheduler / command line',
    'never_synced' => 'Not synced yet.',
    'last_sync' => 'Last sync',
    'last_success' => 'Last successful sync',
    'run_now' => 'Sync now',
    'queued' => ':source sync queued. It runs on the queue worker; refresh this page to follow it.',
    'already_running' => 'A :source sync is already running.',
    'recent_runs' => 'Recent runs',
    'no_runs' => 'No sync has run yet.',
    'expected_mismatch' => 'The source reported :expected records but :fetched were fetched.',
    'company_counts' => 'Companies: :fetched fetched, :created new, :updated changed, :missing missing.',
    'company_links' => ':count contract ↔ company links.',
    'skipped' => ':count records could not be read and were skipped.',

    'unknown_units' => [
        'title' => 'Area units that could not be converted to hectares',
        'hint' => 'Contracts using these units have no area for matching. Add the unit to area_units in config/matching.php.',
        'items' => ':count items',
    ],

    'auth_help' => [
        'lcis' => 'LCIS rejected the token. Check LCIS_API_TOKEN in .env, or ask the LCIS team to issue a new one (php artisan api-client:create on LCIS).',
        'amis' => 'AMIS rejected the API key. It may be invalid, inactive, expired or not allowed from this server\'s domain. Check AMIS_API_KEY in .env or ask the AMIS admin for a new key.',
    ],

    'error_codes' => [
        'API_KEY_INVALID' => 'API key invalid (or not allowed from this domain)',
        'API_KEY_INACTIVE' => 'API key inactive',
        'API_KEY_EXPIRED' => 'API key expired',
        'CREDENTIAL_MISSING' => 'No API credential configured',
        'CONNECTION_FAILED' => 'Could not connect',
        'SUSPICIOUS_SNAPSHOT' => 'Source list looks incomplete',
        'STALE' => 'Stopped without finishing',
        'JOB_FAILED' => 'Job failed',
        'INTERNAL_ERROR' => 'Internal error',
    ],

    'districts' => [
        'title' => 'District code check',
        'hint' => 'LCIS and AMIS should use the same national district codes. A code whose names differ between the systems is listed here.',
        'compared' => ':count district codes appear in both systems.',
        'all_match' => 'All shared district codes have matching names.',
        'mismatches' => ':count codes have different names — check these before relying on district scores.',
        'spelling' => ':count codes name the same district with a different spelling (no action needed):',
        'code' => 'Code',
        'lcis_names' => 'LCIS name',
        'amis_names' => 'AMIS name',
        'no_data' => 'Run both syncs to compare district codes.',
    ],

];
