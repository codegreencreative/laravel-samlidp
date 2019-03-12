<?php

Route::resources('metadata', 'MetadataController')->only('index');

// Route::get('login', 'LoginController@showLoginForm')->name('saml.login');
// Route::post('login', 'LoginController@login');
// Route::get('logout', 'LoginController@logout')->name('saml.logout');
