<?php

Route::get('saml/metadata', function () {
    $certification = trim(str_replace([
        '-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'
    ], '', config('samlidp.cert.crt')));
    $entity_id = config('samlidp.issuer_uri');
    $login = url(config('samlidp.login_uri'));
    return view('samlidp::metadata', compact('certificate', 'entity_id', 'login'));
});