<?php

Route::get('saml/metadata', function () {
    $certificate = trim(str_replace([
        '-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'
    ], '', Storage::get(config('samlidp.crt'))));
    $entity_id = config('samlidp.issuer_uri');
    $login = url(config('samlidp.login_uri'));
    return view('samlidp::metadata', compact('certificate', 'entity_id', 'login'));
});