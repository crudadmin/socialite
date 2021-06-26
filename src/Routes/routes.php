<?php

Route::group([ 'namespace' => 'Admin\Socialite\Controllers', 'middleware' => ['web'] ], function(){
    Route::get('/socialite/{driver}', 'SocialiteController@redirect')->visible();
    Route::get('/socialite/{driver}/callback', 'SocialiteController@callback')->visible();
});