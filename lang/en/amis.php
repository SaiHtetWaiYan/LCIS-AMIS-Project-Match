<?php

return [

    'contracts' => 'AMIS contracts',
    'contract' => 'AMIS contract',
    'search_hint' => 'Contract number, name, partner, company or tax ID',
    'empty' => 'No AMIS contracts match these filters.',
    'all_types' => 'All contract types',
    'all_statuses' => 'All statuses',

    'contract_types' => [
        'concession' => 'Concession',
        'rental' => 'Rental',
    ],

    // AMIS spells "normal" as "nomal".
    'statuses' => [
        'nomal' => 'Active',
        'cancel' => 'Cancelled',
        'end' => 'Ended',
    ],

    'fields' => [
        'id' => 'AMIS ID',
        'contract_number' => 'Contract number',
        'contract_name' => 'Contract name',
        'contract_type' => 'Contract type',
        'business_type' => 'Business type',
        'status' => 'Status',
        'partners' => 'Partners',
        'companies' => 'Linked companies',
        'period' => 'Contract period',
        'budget_province' => 'Budget province',
        'location' => 'Location',
        'area' => 'Total area',
        'updated_at' => 'Updated in AMIS',
    ],

    'items' => [
        'title' => 'Land plots',
        'land_use' => 'Land use',
        'province' => 'Province',
        'district' => 'District',
        'village' => 'Village',
        'area' => 'Area (as entered)',
        'area_ha' => 'Area (ha)',
        'unknown_unit' => 'Unit not convertible',
        'empty' => 'No land plots recorded.',
        'count' => ':count plots',
    ],

    'companies' => [
        'name' => 'Company',
        'tax_id' => 'Tax ID',
        'share' => 'Share',
        'none' => 'No company is linked in AMIS (legacy contract); only partner names are available.',
    ],

];
