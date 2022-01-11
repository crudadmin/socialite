<?php

Route::group([ 'namespace' => 'Admin\Socialite\Controllers', 'middleware' => ['web'] ], function(){
    Route::get('/socialite/{driver}', 'SocialiteController@redirect')->visible();
    Route::any('/socialite/{driver}/callback', 'SocialiteController@callback')->visible();
});