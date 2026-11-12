<?php

return [

    'title' => 'Users',
    'create' => 'New user',
    'edit' => 'Edit user',

    'sides' => [
        'lcis' => 'LCIS',
        'amis' => 'AMIS',
        'admin' => 'Admin',
    ],

    'statuses' => [
        'active' => 'Active',
        'deactivated' => 'Deactivated',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'side' => 'Side',
        'status' => 'Status',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'created_at' => 'Created',
    ],

    'filters' => [
        'search' => 'Search name or email',
        'all_sides' => 'All sides',
        'all_statuses' => 'All statuses',
        'apply' => 'Filter',
        'reset' => 'Reset',
    ],

    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'deactivate' => 'Deactivate',
        'reactivate' => 'Reactivate',
    ],

    'hints' => [
        'password_keep' => 'Leave blank to keep the current password.',
        'own_side' => 'You cannot change the side of your own account.',
        'you' => 'you',
    ],

    'empty' => 'No users found.',

    'messages' => [
        'created' => 'User :name created.',
        'updated' => 'User :name updated.',
        'deactivated' => 'User :name deactivated. They can no longer log in.',
        'reactivated' => 'User :name reactivated.',
        'cannot_change_own_side' => 'You cannot change the side of your own account.',
    ],

    'profile' => [
        'title' => 'My account',
        'details' => 'Account details',
        'details_hint' => 'Contact an administrator to change your name, email or side.',
    ],

];
