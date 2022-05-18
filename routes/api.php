<?php 

use Illuminate\Support\Facades\Route;

Route::get("{resource}/nonattached/{field}", "AttachedController@handle");
Route::get("{resource}/nonattachable/{field}", "AttachableController@handle");
Route::get("{resource}/pivot-fields/{relatedResource}", "PivotFieldController@index");
Route::post("{resource}/pivots-validate/{relatedResource}", "PivotValidateController@index");