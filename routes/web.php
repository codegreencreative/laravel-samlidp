<?php

Route::resource('metadata', 'MetadataController')->only('index');
// Route::resource('logout', 'LogoutController')->only('store');
// Route::get('login', 'LoginController@showLoginForm')->name('saml.login');
// Route::post('login', 'LoginController@login');
