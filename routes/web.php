<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group(['middleware' => 'auth'], function () {
    // Route::get('/admin/users', 'AdminController@listFUsers')->name('admin.users');
    // Route::get('/admin/user/{user}/group', 'AdminController@listUserGroups')->name('admin.user.group.list');
    // Route::get('/admin/user/{user}/group/create', 'AdminController@createUserGroup')->name('admin.user.group.create');
    // Route::post('/admin/user/{user}/group', 'AdminController@storeUserGroup')->name('admin.user.group.store');
    // Route::get('/admin/user/{user}/group/edit', 'AdminController@editUserGroup')->name('admin.user.group.edit');
    // Route::put('/admin/user/{user}/group/{group}', 'AdminController@updateUserGroup')->name('admin.user.group.update');
    // Route::delete('/admin/user/{user}/group/{group}', 'AdminController@destroyUserGroup')->name('admin.user.group.destroy');

    // Route::get('/admin/user/{user}/history', 'AdminController@listUserHistories')->name('admin.user.histories');
    // Route::put('/admin/user/{user}', 'AdminController@updateFUser')->name('admin.user.update');

    // Route::get('/admin/groups', 'AdminController@listFGroups')->name('admin.groups');
    // Route::get('/admin/group/{group}/user', 'AdminController@listGroupUsers')->name('admin.group.user.list');
    // Route::get('/admin/group/create', 'AdminController@createFGroup')->name('admin.group.create');
    // Route::post('/admin/group', 'AdminController@storeFGroup')->name('admin.group.store');
    // Route::get('/admin/group/{group}/edit', 'AdminController@editFGroup')->name('admin.group.edit');
    // Route::put('/admin/group/{group}', 'AdminController@updateFGroup')->name('admin.group.update');
    // Route::delete('/admin/group/{group}', 'AdminController@destroyFGroup')->name('admin.group.destroy');

});

Route::prefix('api')->group(function () {
    Route::post('/login', 'AgentController@login')->name('api.login');
    Route::get('/profile', 'AgentController@profile')->name('api.profile');
    Route::get('/contracts', 'AgentController@getAgentContracts')->name('api.contracts');
    Route::get('/comissions', 'AgentController@getAgentComissions')->name('api.comissions');
    Route::get('/transactions', 'AgentController@getAgentTransactions')->name('api.transactions');
    Route::get('/news', 'AgentController@getAppNews')->name('api.news');
    Route::get('/potential-customers', 'AgentController@getPotentialCustomers')->name('api.potential_customers');
});