<?php

return [

    'rules' => [
        'company_name' => 'Company name',
        'activity' => 'Activity',
        'location' => 'Project location',
        'area' => 'Area (ha)',
    ],

    'sources' => [
        'lcis_company' => 'LCIS company',
        'lcis_company_eng' => 'LCIS company (English)',
        'lcis_activity' => 'LCIS activity',
        'amis_company_lao' => 'AMIS company',
        'amis_company_eng' => 'AMIS company (English)',
        'amis_partner' => 'AMIS partner',
        'amis_contract_name' => 'AMIS contract name',
        'amis_land_use' => 'AMIS land use',
    ],

    // Short notes beside a value on the compare screen; a source without a note shows none.
    'value_notes' => [
        'lcis_company_eng' => 'English',
        'amis_company_lao' => 'Linked company',
        'amis_company_eng' => 'Linked company, English',
        'amis_partner' => 'Partner',
        'amis_contract_name' => 'Contract name',
        'amis_land_use' => 'Land use',
        'location_fallback' => 'Responsible office',
    ],

    'reasons' => [
        'text' => ':lcis_source “:lcis_text” ↔ :amis_source “:amis_text”',
        'area' => 'LCIS :lcis ha vs AMIS :amis ha (:percent% apart)',
        'location_province' => 'Same province (:province)',
        'location_district' => 'same district (:district)',
        'location_other_district' => 'different district (LCIS :lcis, AMIS :amis)',
        'location_district_unknown' => 'district not recorded on one side',
        'location_village' => 'similar village (:village)',
        'location_none' => 'Different provinces (LCIS :lcis, AMIS :amis)',
        'location_fallback' => '[LCIS responsible office, not land location]',
        'compared' => '· compared: “:lcis” ↔ “:amis”',
        'numbers_differ' => '(different project numbers)',
    ],

    'no_data' => 'No data on one side',
    'rules_with_data' => ':count of :total rules had data',
    'weak_basis' => 'Based on few rules — check carefully',
    'capped' => 'Capped at :percent%: :rules too different',
    'score' => 'Score',
    'best_score' => 'Best score',
    'best_score_pair' => 'This pair',
    'best_score_suggestion' => 'Top suggestion',
    'best_score_pending' => 'Not scored yet',
    'best_score_as_of' => 'Scored :date',

    'candidates' => [
        'amis_title' => 'Suggested AMIS contracts',
        'lcis_title' => 'Suggested LCIS projects',
        'subtitle' => 'The :count best-scoring records from the other system. Records missing from their source are not suggested.',
        'none' => 'No suggestions. Try the search below.',
    ],

    'search' => [
        'amis_title' => 'Search AMIS contracts',
        'lcis_title' => 'Search LCIS projects',
        'hint' => 'The right record is not always suggested. Search the other system; results are scored the same way.',
        'button' => 'Search',
        'no_results' => 'Nothing found for “:term”.',
        'results' => 'Results for “:term”',
    ],

    'lao_trigram_broken' => 'The database cannot compare Lao text (its locale treats Lao as non-letters), so name and activity scores will be 0. Create the database with an UTF-8 locale such as en_US.UTF-8 or C.UTF-8.',

];
