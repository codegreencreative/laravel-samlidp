<?php

Route::resource('metadata', 'MetadataController')->only('index');
Route::resource('logout', 'LogoutController')->only('index');
