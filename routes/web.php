<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\PersonalController;
use App\Http\Controllers\v1\ReferralController;
use App\Http\Controllers\v1\DirectChildController;

use App\Http\Controllers\v1\SummaryController;
use App\Http\Controllers\v1\SalesController;
use App\Http\Controllers\v1\OrdersController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\BankController;
use App\Http\Controllers\v1\CommissionController;
use App\Http\Controllers\v1\GroupSalesController;
use App\Http\Controllers\v1\FirstBDSalesController;
use App\Http\Controllers\v1\SecondBDSalesController;
use App\Http\Controllers\v1\VolumeBonusController;
use App\Http\Controllers\v1\TripIncentivesController;
use App\Http\Controllers\v1\CrownController;
use App\Http\Controllers\v1\PayoutController;
use App\Http\Controllers\v1\PayoutDetailsController;

use App\Http\Controllers\v1\LevelUpgradeController;
use App\Http\Controllers\v1\MonthlyTargetController;
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


// Route::get('/commission-test/{ref_code}', [CommissionController::class, 'test'])->name('commission.test');
Route::middleware(['web', 'auth'])->get('/banks', [BankController::class, 'index'])->name('bank.index');

/* Without prefix language */
Route::middleware(['web', 'locale', 'guest'])->get('/', function () {
    return view('guest');
})->name('auth.login');

Route::middleware(['web', 'locale', 'auth'])->get('/personal-sales', function () {
    return view('auth');
})->name('auth.personalSales');

Route::middleware(['web', 'locale', 'auth'])->get('/direct-child-sales', function () {
    return view('auth');
})->name('auth.directChildSales');

Route::middleware(['web', 'locale', 'auth', 'bd'])->get('/tm-sales', function () {
    return view('auth');
})->name('auth.bdSales');

Route::middleware(['web', 'locale', 'auth'])->get('/account-settings', function () {
    return view('auth');
})->name('auth.accountSettings');

Route::middleware(['web', 'locale', 'auth'])->get('/payouts', function () {
    return view('auth');
})->name('auth.payouts');

Route::middleware(['web', 'locale', 'auth'])->get('/payout/{id}', function () {
    return view('auth');
})->name('auth.payoutDetails');


/* With prefix language */
Route::prefix('{language}')->group(function() {
    Route::middleware(['web', 'locale', 'guest'])->get('/', function () {
        return view('guest');
    })->name('auth.loginLocale');

    Route::middleware(['web', 'locale', 'auth'])->get('/personal-sales', function () {
        return view('auth');
    })->name('auth.personalSalesLocale');

    Route::middleware(['web', 'locale', 'auth'])->get('/direct-child-sales', function () {
        return view('auth');
    })->name('auth.directChildSalesLocale');

    Route::middleware(['web', 'locale', 'auth', 'bd'])->get('/tm-sales', function () {
        return view('auth');
    })->name('auth.bdSalesLocale');

    Route::middleware(['web', 'locale', 'auth'])->get('/account-settings', function () {
        return view('auth');
    })->name('auth.accountSettingsLocale');

    Route::middleware(['web', 'locale', 'auth'])->get('/payouts', function () {
        return view('auth');
    })->name('auth.payoutsLocale');

    Route::middleware(['web', 'locale', 'auth'])->get('/payout/{id}', function () {
        return view('auth');
    })->name('auth.payoutDetailsLocale');
});





/*
*
* API Call
*
*/

Route::post('/account/authenticate', [AuthController::class, 'authenticate'])->name('account.authenticate');
Route::post('/account/login', [AuthController::class, 'login'])->name('account.login');
Route::post('/account/logout', [AuthController::class, 'logout'])->name('account.logout');

Route::middleware(['web', 'auth'])->get('/user-personal-sale-chart/{key}', [PersonalController::class, 'salesChart'])->name('personal.salesChart');
Route::middleware(['web', 'auth'])->get('/user-personal-sale/{key}', [PersonalController::class, 'show'])->name('personal.show');
Route::middleware(['web', 'auth'])->get('/user-personal-order-chart/{key}', [PersonalController::class, 'ordersChart'])->name('personal.ordersChart');
Route::middleware(['web', 'auth'])->get('/user-personal-sales/{key}', [PersonalController::class, 'index'])->name('personal.index');

Route::middleware(['web', 'auth'])->get('/user-referral-sale-chart/{key}', [ReferralController::class, 'salesChart'])->name('referral.salesChart');
Route::middleware(['web', 'auth'])->get('/user-referral-sale/{key}', [ReferralController::class, 'show'])->name('referral.show');
Route::middleware(['web', 'auth'])->get('/user-referral-order-chart/{key}', [ReferralController::class, 'ordersChart'])->name('referral.ordersChart');
Route::middleware(['web', 'auth'])->get('/user-referral-sales/{key}', [ReferralController::class, 'index'])->name('referral.index');

Route::middleware(['web', 'auth'])->get('/user-direct-child/{key}', [DirectChildController::class, 'childrenShow'])->name('directChild.childrenShow');
Route::middleware(['web', 'auth'])->get('/user-direct-child-sale-chart/{key}', [DirectChildController::class, 'salesChart'])->name('directChild.salesChart');
Route::middleware(['web', 'auth'])->get('/user-direct-child-sale/{key}', [DirectChildController::class, 'show'])->name('directChild.show');
Route::middleware(['web', 'auth'])->get('/user-direct-child-order-chart/{key}', [DirectChildController::class, 'ordersChart'])->name('directChild.ordersChart');
Route::middleware(['web', 'auth'])->get('/user-direct-child-sales/{key}', [DirectChildController::class, 'index'])->name('directChild.index');


Route::middleware(['web', 'auth'])->get('/summary-personal-statistic/{key}', [SummaryController::class, 'personalStatistic'])->name('summary.personalStatistic');

Route::middleware(['web', 'auth'])->get('/user/{key}', [UserController::class, 'show'])->name('user.show');
Route::middleware(['web', 'auth'])->put('/user-password/{key}', [UserController::class, 'updatePassword'])->name('user.updatePassword');
Route::middleware(['web', 'auth'])->put('/user-bank/{key}', [UserController::class, 'updateBankDetails'])->name('user.updateBankDetails');
Route::middleware(['web', 'auth'])->put('/user-info/{key}', [UserController::class, 'updateInfo'])->name('user.updateInfo');
Route::middleware(['web', 'auth'])->put('/user-avatar/{key}', [UserController::class, 'updateAvatar'])->name('user.updateAvatar');
Route::middleware(['web', 'auth'])->get('/user-rank/{key}', [UserController::class, 'showRank'])->name('user.showRank');

Route::middleware(['web', 'auth'])->post('/file', [FileController::class, 'store'])->name('file.store');

// Route::post('/commission-personal/{key}', [CommissionController::class, 'personalCommission'])->name('commission.personal');
// Route::post('/commission-group-calculation', [CommissionController::class, 'groupCommissionCalculation'])->name('commission.groupCalculation');

Route::middleware(['web', 'auth', 'bd'])->get('/group-sales/{key}', [GroupSalesController::class, 'index'])->name('group.index');
Route::middleware(['web', 'auth', 'bd'])->get('/group-sale/{key}', [GroupSalesController::class, 'show'])->name('group.show');

Route::middleware(['web', 'auth', 'bd'])->get('/first-bd-sales/{key}', [FirstBDSalesController::class, 'index'])->name('firstBD.index');
Route::middleware(['web', 'auth', 'bd'])->get('/first-bd-sale/{key}', [FirstBDSalesController::class, 'show'])->name('firstBD.show');

Route::middleware(['web', 'auth', 'bd'])->get('/second-bd-sales/{key}', [SecondBDSalesController::class, 'index'])->name('secondBD.index');
Route::middleware(['web', 'auth', 'bd'])->get('/second-bd-sale/{key}', [SecondBDSalesController::class, 'show'])->name('secondBD.show');

Route::middleware(['web', 'auth', 'bd'])->get('/volume-incentive/{key}', [VolumeBonusController::class, 'show'])->name('volume.show');
Route::middleware(['web', 'auth', 'bd'])->get('/trip-incentive/{key}', [TripIncentivesController::class, 'show'])->name('trip.show');
Route::middleware(['web', 'auth', 'bd'])->get('/crown-incentive/{key}', [CrownController::class, 'show'])->name('crown.show');

Route::middleware(['web', 'auth'])->get('/user-payout/{key}', [PayoutController::class, 'show'])->name('payout.show');
Route::middleware(['web', 'auth'])->get('/user-payout-details/{key}', [PayoutDetailsController::class, 'show'])->name('payoutDetails.show');


Route::middleware(['web', 'auth'])->get('/monthly-kpi/{key}', [MonthlyTargetController::class, 'show'])->name('monthlyTarget.show');
Route::middleware(['web', 'auth'])->get('/level-upgrade/{key}', [LevelUpgradeController::class, 'show'])->name('levelUpgrade.show');

