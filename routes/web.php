<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
    Route::get('/admin', 'AdminController@index')->name('admin.index');
    Route::get('/admin/users', 'AdminController@listUsers')->name('admin.users');
    Route::get('/admin/user/create', 'AdminController@createUser')->name('admin.user.create');
    Route::get('/admin/user/bulk-create', 'AdminController@createBulkUsers')->name('admin.user.bulk_create');
    Route::get('/admin/user/{user}', 'AdminController@getUser')->name('admin.user.detail');
    Route::get('/admin/user/{user}/raw', 'AdminController@getUserRaw')->name('admin.user.raw');
    Route::post('/admin/user', 'AdminController@storeUser')->name('admin.user.store');
    Route::post('/admin/users', 'AdminController@importUsers')->name('admin.user.import');
    Route::get('/admin/user/{user}/edit', 'AdminController@editUser')->name('admin.user.edit');
    Route::put('/admin/user/{user}', 'AdminController@updateUser')->name('admin.user.update');

    Route::get('/admin/contracts', 'AdminController@listContracts')->name('admin.contracts');
    Route::get('/admin/user/{user}/contract', 'AdminController@listUserContracts')->name('admin.user.contracts');
    Route::get('/admin/contract/bulk-create', 'AdminController@createBulkContracts')->name('admin.contract.bulk_create');
    Route::get('/admin/contract/{contract}/edit', 'AdminController@editContract')->name('admin.contract.edit');
    Route::get('/admin/contract/{contract}', 'AdminController@getContract')->name('admin.contract.detail');
    Route::put('/admin/contract/{contract}', 'AdminController@updateContract')->name('admin.contract.update');
    Route::get('/admin/contract/create', 'AdminController@createContract')->name('admin.contract.create');
    Route::post('/admin/contract', 'AdminController@storeContract')->name('admin.contract.store');
    Route::post('/admin/contracts', 'AdminController@importContracts')->name('admin.contract.import');

    Route::get('/admin/customers', 'AdminController@listCustomers')->name('admin.customers');
    Route::get('/admin/customer/{customer}', 'AdminController@getCustomer')->name('admin.customer.detail');
    Route::get('/admin/customer/{customer}/raw', 'AdminController@getCustomerRaw')->name('admin.customer.raw');
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
    // 
    // 
    // Route::delete('/admin/group/{group}', 'AdminController@destroyFGroup')->name('admin.group.destroy');

});

Route::prefix('api')->group(function () {
    Route::post('/login', 'AgentController@login')->name('api.login');
    Route::post('/require-update-contract', 'AgentController@requireUpdateContract')->name('api.require_update_contract');
    Route::post('/change-password', 'AgentController@changePassword')->name('api.change_password');
    Route::post('/password2', 'AgentController@checkPassword2')->name('api.check_password2');
    Route::post('/change-password2', 'AgentController@changePassword2')->name('api.change_password2');

    Route::get('/profile', 'AgentController@profile')->name('api.profile');
    Route::get('/contracts', 'AgentController@getAgentContracts')->name('api.contracts');
    Route::get('/comissions', 'AgentController@getAgentComissions')->name('api.comissions');
    Route::get('/transactions', 'AgentController@getAgentTransactions')->name('api.transactions');
    Route::get('/news', 'AgentController@getAppNews')->name('api.news');
    Route::get('/potential-customers', 'AgentController@getPotentialCustomers')->name('api.potential_customers');
    Route::get('/customers', 'AgentController@getCustomers')->name('api.customers');
    Route::get('/promotions', 'AgentController@getPromotionProgress')->name('api.promotions');
    Route::get('/team', 'AgentController@getTeam')->name('api.team');
    Route::get('/income', 'AgentController@getIncome')->name('api.income');
    Route::get('/metrics', 'AgentController@getMetrics')->name('api.metrics');
    Route::get('/documents', 'AgentController@getDocuments')->name('api.documents');
    Route::get('/instructions', 'AgentController@getInstructions')->name('api.instructions');
    Route::get('/contract-status-codes', 'AgentController@getContractStatusCodes')->name('api.contract_status_codes');
    Route::get('/contract-search-type-codes', 'AgentController@getContractSearchTypeCodes')->name('api.contract_search_type_codes');
    Route::get('/designation-codes', 'AgentController@getDesignationCodes')->name('api.designation_codes');
    Route::get('/product-codes', 'AgentController@getProductCodes')->name('api.product_codes');
    Route::get('/partners', 'AgentController@getPartners')->name('api.partners');
    Route::post('/vbi-return', 'PartnerController@VBIReturn')->name('api.vbi_return');
    Route::post('/bidv-return', 'PartnerController@BIDVReturn')->name('api.bidv_return');

    Route::get('/calc/{id}', 'ComissionCalculatorController@calc')->name('api.calc');
    Route::get('/test/{id}', 'PartnerController@VBICheckUpdateContract')->name('api.test');
});

Route::prefix('guess')->group(function () {
    Route::get('/users/structure', 'AdminController@exportUsersStructure')->name('admin.user.export_structure');
});