<?php

return [

    'match' => 'Match',
    'title' => 'Match #:id',
    'history' => 'History',

    'statuses' => [
        'proposed' => 'Proposed',
        'matched' => 'Matched',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        'unmatched' => 'Unmatched',
    ],

    'actions' => [
        'proposed' => 'Proposed',
        'confirmed' => 'Confirmed',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        'unmatched' => 'Unmatched',
        'flagged_for_review' => 'Flagged for review',
        'reviewed' => 'Reviewed',
    ],

    'buttons' => [
        'propose' => 'Propose match',
        'confirm' => 'Confirm match',
        'reject' => 'Reject',
        'withdraw' => 'Withdraw proposal',
        'unmatch' => 'Unmatch',
        'review' => 'Mark as reviewed',
        'view' => 'View match',
    ],

    'fields' => [
        'status' => 'Status',
        'proposed_by' => 'Proposed by',
        'lcis_confirmation' => 'LCIS confirmation',
        'amis_confirmation' => 'AMIS confirmation',
        'matched_at' => 'Matched',
        'rejection_reason' => 'Rejection reason',
        'unmatch_reason' => 'Unmatch reason',
        'reason' => 'Reason',
        'review_note' => 'Note (optional)',
        'waiting' => 'Waiting',
        'by_at' => ':name, :date',
        'system' => 'System',
    ],

    'flash' => [
        'proposed' => 'Match proposed. It now waits for confirmation from the :side side.',
        'confirmed' => 'Match confirmed by both sides.',
        'rejected' => 'Proposal rejected.',
        'withdrawn' => 'Proposal withdrawn.',
        'unmatched' => 'Match removed. Both records are available again.',
        'reviewed' => 'Match marked as reviewed.',
    ],

    'banner' => [
        'proposed' => 'This record is in a proposed match, waiting for confirmation.',
        'matched' => 'This record is matched.',
        'needs_review' => 'Needs review',
    ],

    'review_reasons' => [
        'lcis_missing' => 'the LCIS project is missing from LCIS',
        'amis_missing' => 'the AMIS contract is missing from AMIS',
        'lcis_changed' => 'key fields of the LCIS project changed after confirmation',
        'amis_changed' => 'key fields of the AMIS contract changed after confirmation',
    ],

    'review_hint' => 'Flagged because :reasons. Check the records, then mark as reviewed or unmatch.',
    'reason_placeholder' => 'Why? Visible to both sides in the history.',

    'errors' => [
        'side_only' => 'Only active LCIS or AMIS users can propose, confirm or reject matches.',
        'admin_only' => 'Only an admin can do this.',
        'lcis_missing' => 'This LCIS project is missing from LCIS and cannot be matched.',
        'amis_missing' => 'This AMIS contract is missing from AMIS and cannot be matched.',
        'lcis_taken' => 'This LCIS project is already in match #:match (:status).',
        'amis_taken' => 'This AMIS contract is already in match #:match (:status).',
        'taken_meanwhile' => 'Someone else proposed a match for one of these records a moment ago. Reload the page to see it.',
        'not_proposed' => 'This match is :status, so it can no longer be confirmed, rejected or withdrawn.',
        'not_matched' => 'Only a confirmed match can be unmatched; this one is :status.',
        'own_side_confirmed' => 'Your side has already confirmed this match; a user from the other side must confirm or reject it.',
        'not_proposer' => 'Only the user who proposed this match can withdraw it.',
        'not_flagged' => 'This match is not flagged for review.',
        'not_active' => 'This match is no longer active.',
    ],

];
