<?php

/*
|--------------------------------------------------------------------------
| Legal pages (Privacy Policy and Terms of Use)
|--------------------------------------------------------------------------
|
| Values used by resources/views/legal/*.blade.php. Facts that could not be
| verified from the codebase (the registered legal name of the operator, its
| postal address, the governing law and any data-protection registration)
| are left empty on purpose. While a value is empty the page shows a clearly
| marked placeholder. The owner must set these in .env before publishing.
|
*/

return [

    // Registered legal name of the organisation that operates ElevateHer360.
    'organisation_name' => env('LEGAL_ORGANISATION_NAME'),

    // Postal / physical address for legal and privacy correspondence.
    'address' => env('LEGAL_ADDRESS'),

    // Country / legal system whose law governs the Terms (e.g. "the Republic of ...").
    'jurisdiction' => env('LEGAL_JURISDICTION'),

    // Data-protection authority registration number, if the operator has one.
    'data_protection_registration' => env('LEGAL_DATA_PROTECTION_REGISTRATION'),

    // Contact for privacy requests, including account and data deletion.
    'support_email' => env('LEGAL_SUPPORT_EMAIL', 'support@elevateher360.org'),

    // Minimum age to create an account without guardian consent. Policy
    // decision for the owner to confirm with legal counsel.
    'minimum_age' => (int) env('LEGAL_MINIMUM_AGE', 18),

    // Target number of days to complete an account/data deletion request.
    // Operational commitment for the owner to confirm.
    'deletion_response_days' => (int) env('LEGAL_DELETION_RESPONSE_DAYS', 30),

    // Shown as "Last updated" on both pages.
    'last_updated' => env('LEGAL_LAST_UPDATED', '30 September 2026'),

];
