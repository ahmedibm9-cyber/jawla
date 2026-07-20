<?php

// Egyptian Tax Authority (ETA) e-invoicing integration.
//
// This is the go-live gate for real invoicing in Egypt. Everything here is
// DISABLED by default and stays inert until the taxpayer provides real ETA
// credentials and a signing certificate. No values are hard-coded — all come
// from the environment (server-only), never the client bundle.
return [
    // Master switch. Until true AND a taxpayer RIN is set, EtaService refuses
    // to submit and invoicing behaves as demo/UAT (QR only).
    'enabled' => env('ETA_ENABLED', false),

    // 'preprod' (ETA sandbox) or 'production'. Never default to production.
    'environment' => env('ETA_ENVIRONMENT', 'preprod'),

    // OAuth client credentials issued by ETA to the taxpayer.
    'client_id' => env('ETA_CLIENT_ID', ''),
    'client_secret' => env('ETA_CLIENT_SECRET', ''),

    // The taxpayer Registration/Tax number (RIN) that owns the submission.
    'taxpayer_rin' => env('ETA_TAXPAYER_RIN', ''),

    // ETA API + ID-provider base URLs (differ per environment).
    'api_base_url' => env('ETA_API_BASE_URL', ''),
    'id_base_url' => env('ETA_ID_BASE_URL', ''),

    // Document model version the builder targets. Validate the produced
    // document against the official ETA SDK schema before go-live.
    'document_type_version' => env('ETA_DOCUMENT_TYPE_VERSION', '1.0'),
];
