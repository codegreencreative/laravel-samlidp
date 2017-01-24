<?php

Route::get('saml/metadata', function () {
    // !!!!!!!!!!
    // This is not going to work alone, we need to strip some data
    // !!!!!!!!!!
    $certification = $contents = Storage::get('samlidp-public.key');
    $entity_id = config('samlidp.issuer_uri');
    $login = url(config('samlidp.login_uri'));
    return view('samlidp::metadata', compact('certificate', 'entity_id', 'login'));
});