<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'string' => 'The :attribute field must be a string.',
    'min' => ['string' => 'The :attribute field must be at least :min characters.'],
    'max' => ['string' => 'The :attribute field must not be greater than :max characters.'],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute field confirmation does not match.',

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
    ],

    'custom' => [],
];
