<?php

return [
    '403' => 'You do not have permission to access this page.',
    '404' => 'The page you are looking for could not be found.',
    '419' => 'Your session has expired. Please refresh and try again.',
    '500' => 'Something went wrong on our end. Please try again later.',

    'stock' => [
        'insufficient' => 'Insufficient stock for product ID :product (available: :available)',
    ],

    'price' => [
        'out_of_range' => 'Price :price is outside the allowed range for product :product',
    ],

    'customer' => [
        'not_approved' => 'Customer :name is not approved yet. Transaction cannot proceed.',
        'duplicate' => 'A customer with the same name or phone already exists.',
    ],

    'gps' => [
        'invalid' => 'Invalid GPS coordinates (lat: :lat, lng: :lng).',
        'outside_geofence' => 'You are outside the allowed visit radius. :distance meters from the customer.',
    ],

    'document' => [
        'invalid_transition' => 'Cannot transition document from :from to :to.',
        'already_converted' => 'This proforma has already been converted.',
        'proforma_expired' => 'This quotation has expired.',
        'period_closed' => 'Fiscal period is closed. Cannot post a transaction dated :date.',
    ],

    'concurrency' => [
        'stale' => 'This record was modified by another user. Please refresh and try again.',
    ],

    'reversal' => [
        'not_allowed' => 'This transaction cannot be reversed in its current state.',
    ],

    'route' => [
        'lock' => 'You cannot visit a customer outside your assigned route.',
    ],

    'credit_limit' => [
        'exceeded' => 'Credit limit exceeded for customer :name. Limit: :limit, balance: :balance.',
    ],
];
