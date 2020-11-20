<?php

Route::group(['middleware' => ['web','auth:admin'], 'prefix' => 'admin', 'as' => 'admin.'], function() {

    Route::resource('general', 'Eve\Cms\Controllers\GeneralController');

    //multiple images
    Route::post('general-upload-image', 'Eve\Cms\Controllers\GeneralController@imageUpload')->name('generalImage.upload');
    Route::post('general-delete-image', 'Eve\Cms\Controllers\GeneralController@deleteUpload')->name('generalImage.delete');
    Route::post('edit-general-delete-image', 'Eve\Cms\Controllers\GeneralController@deleteUploadEdit')->name('generalImage.deleteEdit');
    Route::get('general-images/{id}', 'Eve\Cms\Controllers\GeneralController@getDefault')->name('generalImage.get');
});


