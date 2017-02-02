<?php

Route::get('metadata', 'MetadataController@index');
Route::get('login', 'LoginController@showLoginForm')->name('saml.login');
Route::post('login', 'LoginController@login');
Route::get('logout', 'LoginController@logout')->name('saml.logout');
