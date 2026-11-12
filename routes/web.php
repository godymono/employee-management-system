<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\Pms\MyPlanController;
use App\Http\Controllers\Pms\PmsCycleController;
use App\Http\Controllers\Pms\PmsDashboardController;
use App\Http\Controllers\Pms\PmsPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-dashboard', EmployeeDashboardController::class)->name('employee.dashboard');
    Route::get('/my-profile', [EmployeeController::class, 'profile'])->name('profile.show');

    Route::middleware('role:admin,hr')->group(function () {
        Route::get('/employees/register', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/pms/cycles/{cycle}/edit', [PmsCycleController::class, 'edit'])->name('pms.cycles.edit');
        Route::put('/pms/cycles/{cycle}', [PmsCycleController::class, 'update'])->name('pms.cycles.update');
        Route::get('/pms/plans/create', [PmsPlanController::class, 'create'])->name('pms.plans.create');
        Route::post('/pms/plans', [PmsPlanController::class, 'store'])->name('pms.plans.store');
    });

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');

    Route::get('/pms', [PmsDashboardController::class, 'index'])->name('pms.index');
    Route::get('/pms/my', MyPlanController::class)->name('pms.my');

    Route::get('/pms/plans/{plan}', [PmsPlanController::class, 'show'])->name('pms.plans.show');
    Route::get('/pms/plans/{plan}/targets', [PmsPlanController::class, 'targets'])->name('pms.plans.targets');
    Route::put('/pms/plans/{plan}/targets', [PmsPlanController::class, 'saveTargets'])->name('pms.plans.targets.save');
    Route::put('/pms/plans/{plan}/targets/decision', [PmsPlanController::class, 'decideTargets'])->name('pms.plans.targets.decide');
    Route::get('/pms/plans/{plan}/assess/{period}/{stage}', [PmsPlanController::class, 'assess'])->name('pms.plans.assess');
    Route::put('/pms/plans/{plan}/assess/{period}/{stage}', [PmsPlanController::class, 'saveAssessment'])->name('pms.plans.assess.save');
});
