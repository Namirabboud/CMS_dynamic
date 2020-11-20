<?php

Route::group(['middleware' => ['web','auth:admin'], 'prefix' => 'admin', 'as' => 'admin.'], function() {

    Route::resource('general', 'Eve\Dynamic\Controllers\GeneralController');

    //multiple images
    Route::post('general-upload-image', 'Eve\Dynamic\Controllers\GeneralController@imageUpload')->name('generalImage.upload');
    Route::post('general-delete-image', 'Eve\Dynamic\Controllers\GeneralController@deleteUpload')->name('generalImage.delete');
    Route::post('edit-general-delete-image', 'Eve\Dynamic\Controllers\GeneralController@deleteUploadEdit')->name('generalImage.deleteEdit');
    Route::get('general-images/{id}', 'Eve\Dynamic\Controllers\GeneralController@getDefault')->name('generalImage.get');
});


