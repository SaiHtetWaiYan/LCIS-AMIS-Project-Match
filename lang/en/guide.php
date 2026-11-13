<?php

return [

    'title' => 'User guide',
    'intro' => 'How to pair LCIS projects with AMIS contracts in this system.',
    'contents' => 'Contents',
    'you' => 'You',

    'sections' => [
        'overview' => 'What this system does',
        'roles' => 'Who does what',
        'find' => 'Find and propose a match',
        'score' => 'Reading the score',
        'confirm' => 'Confirm or reject a proposal',
        'statuses' => 'Match statuses',
        'review' => 'Matches that need review',
        'list' => 'Matches list and export',
        'admin' => 'Admin tasks',
        'tips' => 'Questions and tips',
    ],

    'overview' => [
        'p1' => 'LCIS (investment projects) and AMIS (rent-land contracts) record the same projects but share no IDs. This system lets staff from both sides pair an LCIS project with the AMIS contract for the same project.',
        'p2' => 'A pair counts as matched only after one LCIS user and one AMIS user have both confirmed it. Each project and each contract can be in only one active pair at a time.',
        'p3' => 'Data is copied from both systems every night (the sync). Nothing you do here changes LCIS or AMIS.',
    ],

    'roles' => [
        'headings' => ['Role', 'Can do'],
        'lcis' => 'Propose pairs. Confirm or reject pairs proposed by AMIS users.',
        'amis' => 'Propose pairs. Confirm or reject pairs proposed by LCIS users.',
        'admin' => 'Manage user accounts, run and watch the sync, unmatch a confirmed pair, clear review flags. Admins do not propose or confirm.',
    ],

    'find' => [
        'steps' => [
            ['title' => 'Open a record', 'text' => 'Go to LCIS projects or AMIS contracts. Search, or filter by province, type and match status (choose “Not matched” to see what is left). Click a record.'],
            ['title' => 'Check the suggestions', 'text' => 'The record page lists the :limit best-scoring records from the other system. Records already in a proposed or matched pair, or missing from their source, are not suggested.'],
            ['title' => 'Search if needed', 'text' => 'The right record is not always suggested, e.g. when the company name is spelt very differently. Use the search box below the suggestions; results are scored the same way.'],
            ['title' => 'Compare', 'text' => 'Click Compare to see both records side by side: each matching field with its score and reason, then other key fields. Rows marked “Differs” are worth a closer look.'],
            ['title' => 'Propose', 'text' => 'If they are the same project, click Propose match. This counts as your side’s confirmation; the proposal now waits for the other side.'],
        ],
        'withdraw' => 'Changed your mind? Open the match and click Withdraw proposal. Only the person who proposed can withdraw, and only while it waits for the other side.',
    ],

    'score' => [
        'p1' => 'The score suggests; people decide. Each rule compares one field and gives 0–100%. The total is the weighted average of the rules that had data on both sides.',
        'headings' => ['Rule', 'Weight', 'How it scores'],
        'how' => [
            'CompanyNameRule' => 'How similar the company names are. LCIS company (Lao and English) against AMIS linked companies and partner names. Words like ບໍລິສັດ, ຈຳກັດ, “Co., Ltd” and sector words like ເຂື່ອນ, ໄຟຟ້າ, ນ້ຳ, ພາວເວີ, ໂຮງງານ, ປຸງແຕ່ງ are ignored. Different project numbers (ນ້ຳກົງ 1 vs ນ້ຳກົງ 3) lower the score sharply.',
            'ActivityRule' => 'How similar the LCIS activity is to the AMIS contract name or a plot’s land use, ignoring the same words. If the activities have almost nothing in common, the total is capped, even when the company is the same.',
            'LocationRule' => 'Same province :province%, plus same district :district%, plus similar village :village%. The best LCIS location × AMIS plot counts.',
            'AreaRule' => 'Areas in hectares: 100% when within :full% of each other, falling to 0% at :zero% apart.',
        ],
        'other_rule' => 'See the reason shown with the score.',
        'colors' => 'Colours: green 75% and above (strong), amber 50–74% (check carefully), grey below 50%.',
        'rules_with_data' => '“3 of 4 rules had data” tells you how much the score rests on. A high score from one rule is weak evidence.',
        'caps' => 'Some disagreements outweigh the average: completely different company names, completely different activities, or different provinces (when LCIS has the land location). The total is then capped at :cap% and the score says “Capped”.',
        'pending' => 'Until LCIS sends land area and land locations, the area rule has no data and location uses the LCIS responsible office.',
        'example_title' => 'Example',
        'example' => 'Company 100%, activity 100%, location 50%, area no data → (100×35 + 100×25 + 50×20) ÷ (35+25+20) = 87%.',
    ],

    'confirm' => [
        'p1' => 'Open My queue. It lists proposals waiting for your side, oldest first. Click Review to open one.',
        'confirm' => 'Confirm match: the records are the same project. The pair becomes Matched.',
        'reject' => 'Reject: they are not. A reason is required and is visible to both sides in the history.',
        'p2' => 'You cannot confirm a proposal from your own side; a user from the other side must do it.',
    ],

    'statuses' => [
        'proposed' => 'Proposed by one side, waiting for the other. Both records are held.',
        'matched' => 'Confirmed by both sides. This is the result.',
        'rejected' => 'The other side said no. Both records are free again.',
        'withdrawn' => 'The proposer took it back. Both records are free again.',
        'unmatched' => 'An admin removed a confirmed match. Both records are free again.',
    ],

    'review' => [
        'p1' => 'After each sync, a proposed or matched pair is flagged Needs review when one record disappeared from its system, or when key fields (name, company, location, area…) changed after it was confirmed.',
        'p2' => 'An admin checks the records, then either marks the match as reviewed or unmatches it.',
    ],

    'list' => [
        'p1' => 'Matches lists every pair with its status. Filter by status, “Only needs review”, or search either record.',
        'p2' => 'Export CSV downloads the list with the current filters, including who proposed and confirmed each pair. It opens in Excel with Lao text intact.',
        'p3' => 'Every match page has a History with each step: who, which side, when and why.',
    ],

    'admin' => [
        'users' => 'Users: create accounts and set each user’s side (LCIS, AMIS or Admin). Deactivate users who leave; they can no longer log in. There is no self-registration.',
        'sync' => 'Sync: shows the last run of each source and any error (e.g. an expired AMIS key). Run now starts a sync immediately; it also runs every night.',
        'unmatch' => 'Unmatch: on a matched pair, if it was wrong. A reason is required.',
        'review' => 'Review: My queue for admins lists matches flagged Needs review.',
    ],

    'tips' => [
        ['q' => 'Why can’t I propose this pair?', 'a' => 'The Compare page says why. Usually one record is already in another proposed or matched pair (follow the link to it), or a record is missing from its source.'],
        ['q' => 'The right record is not suggested.', 'a' => 'Use the search under the suggestions. Try the contract number, project code, tax ID or part of the company name.'],
        ['q' => 'One LCIS project seems to have two AMIS contracts.', 'a' => 'For now a project can be matched to one contract only. Match the main one and tell the admin; this rule may change.'],
        ['q' => 'A pair was confirmed by mistake.', 'a' => 'Ask an admin to unmatch it with a reason.'],
        ['q' => 'A record looks out of date.', 'a' => 'Data is copied every night. Fix it in LCIS or AMIS; it updates here after the next sync.'],
    ],

];
