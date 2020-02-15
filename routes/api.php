<?php 

Route::get("{resource}/attached/{field}", "AttachedController@handle");
Route::get("{resource}/attachable/{field}", "AttachableController@handle");
Route::get("{resource}/pivot-fields/{relatedResource}", "PivotFieldController@index");
Route::post("{resource}/pivots-validate/{relatedResource}", "PivotValidateController@index");