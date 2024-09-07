<?php

use Illuminate\Support\Facades\Route;

Route::resource('metadata', 'MetadataController')->only('index');
Route::resource('logout', 'LogoutController')->only('index');
