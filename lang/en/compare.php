<?php

return [

    'title' => 'Compare',
    'subtitle' => 'An LCIS project and an AMIS contract side by side.',
    'matching_fields' => 'Matching fields',
    'matching_fields_hint' => 'The fields the score is built from, with each rule’s score and reason.',
    'other_fields' => 'Other key fields',
    'other_fields_hint' => 'Rows where both systems record the same thing are marked same or different.',
    'field' => 'Field',
    'weight' => 'weight :percent%',
    'same' => 'Same',
    'differs' => 'Differs',

    'details' => [
        'sector' => 'Sector',
        'administering_province' => 'Administering office',
        'kind' => 'Subsector / contract type',
        'status' => 'Status',
        'period' => 'Contract period',
        'reference' => 'Reference',
        'id' => 'ID :id',
        'updated_at' => 'Last updated in source',
    ],

    'proposal' => [
        'title' => 'Propose this pair',
        'hint' => 'Proposing counts as your side’s confirmation. A user from the other side then confirms or rejects it.',
        'refused' => 'You cannot propose this pair: :reason',
    ],

    'in_other_match' => [
        'lcis' => 'This LCIS project is already in another match:',
        'amis' => 'This AMIS contract is already in another match:',
    ],

    'earlier' => [
        'title' => 'Earlier proposals of this pair',
        'item' => 'Match #:id · :status · :date',
    ],

    'button' => 'Compare',

];
