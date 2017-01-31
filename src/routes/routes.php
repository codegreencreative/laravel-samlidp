<?php

Route::get('saml/metadata', 'MetadataController@index');
Route::get('saml/login', 'LoginController@showLoginForm')->name('saml.login');
Route::get('saml/logout', 'LoginController@logout')->name('saml.logout');
