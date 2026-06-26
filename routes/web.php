<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/api-docs.json', function () {
    return response()->file(storage_path('api-docs/api-docs.json'));
});

Route::get('/docs/api-docs.yaml', function () {
    return response()->file(storage_path('api-docs/api-docs.yaml'));
});

Route::redirect('/swagger-ui', '/api/documentation');
Route::redirect('/swagger-ui/', '/api/documentation');
Route::get('/graphql', function () {
    return redirect('/graphql-playground');
});


