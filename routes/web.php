<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DoctorAdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ServiceAdminController;
use App\Http\Controllers\Admin\TeamAdminController;
use App\Http\Controllers\Patient\PatientProfileController;
use App\Http\Controllers\Patient\PatientRecordController;
use App\Http\Controllers\Patient\PatientDocumentController;
use App\Http\Controllers\Patient\PatientBookingController;
use App\Http\Controllers\Patient\PatientAppointmentsController;
use App\Http\Controllers\Doctor\DoctorProfileController;
use App\Http\Controllers\Doctor\DoctorScheduleController;
use App\Http\Controllers\Doctor\DoctorWorkController;

Route::view('/', 'landing');

Route::get('/login', function () {
    $frontend = config('app.frontend_url');
    return redirect($frontend ? rtrim($frontend, '/') . '/login' : '/app/#/login');
})->name('login');
Route::get('/angular-web-login/{handoff}', [AuthController::class, 'angularWebLogin'])
    ->where('handoff', '[A-Za-z0-9]+')
    ->name('angular.web.login');
Route::get('/angular-web-logout', [AuthController::class, 'angularWebLogout'])
    ->name('angular.web.logout');

Route::get('/register', function () {
    $frontend = config('app.frontend_url');
    return redirect($frontend ? rtrim($frontend, '/') . '/register' : '/app/#/register');
})->name('register.page');

Route::get('/app/{path?}', fn () => response()->file(public_path('app/index.html')))
    ->where('path', '.*')
    ->name('angular.app');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::post('/register/health', [AuthController::class, 'saveHealth'])->middleware('auth')->name('register.health.save');
Route::post('/register/health/skip', [AuthController::class, 'skipHealth'])->middleware('auth')->name('register.health.skip');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');


Route::get('/dashboard', function () {
    $role = Auth::user()->Role ?? 'pacijent';

    return match ($role) {
        'admin'   => redirect()->route('admin.dashboard'),
        'doktor'  => redirect()->route('doctor.profile'),
        default   => redirect()->route('patient.profile'),
    };
})->middleware('auth')->name('dashboard');


Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/doktori', [DoctorAdminController::class, 'index'])->name('admin.doctors.index');
    Route::post('/doktori', [DoctorAdminController::class, 'store'])->name('admin.doctors.store');

    Route::get('/doktori/{email}', [DoctorAdminController::class, 'show'])->name('admin.doctors.show');
    Route::post('/doktori/{email}', [DoctorAdminController::class, 'update'])->name('admin.doctors.update');

    Route::post('/doktori/{email}/delete', [DoctorAdminController::class, 'destroy'])->name('admin.doctors.destroy');

    Route::post('/doktori/{email}/raspolozivost', [DoctorAdminController::class, 'saveAvailability'])->name('admin.doctors.availability');
    Route::post('/doktori/{email}/neradni', [DoctorAdminController::class, 'addOffDay'])->name('admin.doctors.offdays.add');
    Route::post('/doktori/{email}/neradni/{datum}/delete', [DoctorAdminController::class, 'deleteOffDay'])->name('admin.doctors.offdays.delete');
    Route::post('/doktori/{email}/usluge', [DoctorAdminController::class, 'saveServices'])->name('admin.doctors.services');

    Route::get('/usluge', [ServiceAdminController::class, 'index'])->name('admin.services.index');
    Route::post('/usluge', [ServiceAdminController::class, 'store'])->name('admin.services.store');

    Route::get('/usluge/{naziv}', [ServiceAdminController::class, 'show'])->name('admin.services.show');
    Route::post('/usluge/{naziv}', [ServiceAdminController::class, 'update'])->name('admin.services.update');

    Route::post('/usluge/{naziv}/delete', [ServiceAdminController::class, 'destroy'])->name('admin.services.destroy');

    Route::get('/timovi', [TeamAdminController::class, 'index'])->name('admin.teams.index');
    Route::post('/timovi', [TeamAdminController::class, 'store'])->name('admin.teams.store');

    Route::get('/timovi/{id}', [TeamAdminController::class, 'show'])->whereNumber('id')->name('admin.teams.show');
    Route::post('/timovi/{id}', [TeamAdminController::class, 'update'])->whereNumber('id')->name('admin.teams.update');

    Route::post('/timovi/{id}/delete', [TeamAdminController::class, 'destroy'])->whereNumber('id')->name('admin.teams.destroy');
});


Route::prefix('pacijent')->middleware(['auth', 'role:pacijent'])->group(function () {

    Route::get('/profil', [PatientProfileController::class, 'show'])->name('patient.profile');
    Route::post('/profil', [PatientProfileController::class, 'updateProfile'])->name('patient.profile.update');
    Route::post('/zdravlje', [PatientProfileController::class, 'updateHealth'])->name('patient.health.update');

    Route::get('/karton', [PatientRecordController::class, 'show'])->name('patient.record');

    Route::post('/dokumenti', [PatientDocumentController::class, 'store'])->name('patient.docs.store');
    Route::get('/dokumenti/view', [PatientDocumentController::class, 'view'])->name('patient.docs.view');
    Route::get('/dokumenti/download', [PatientDocumentController::class, 'download'])->name('patient.docs.download');
    Route::post('/dokumenti/delete', [PatientDocumentController::class, 'destroy'])->name('patient.docs.delete');

    Route::get('/zakazivanje', [PatientBookingController::class, 'index'])->name('patient.booking');
    Route::get('/zakazivanje/days', [PatientBookingController::class, 'days'])->name('patient.booking.days');
    Route::get('/zakazivanje/slots', [PatientBookingController::class, 'slots'])->name('patient.booking.slots');
    Route::post('/zakazivanje', [PatientBookingController::class, 'store'])->name('patient.booking.store');

    Route::get('/termini', [PatientAppointmentsController::class, 'index'])->name('patient.appointments');
    Route::post('/termini/{id}/otkazi', [PatientAppointmentsController::class, 'cancel'])
        ->whereNumber('id')
        ->name('patient.appointments.cancel');
});

Route::prefix('doctor')->middleware(['auth', 'role:doktor'])->group(function () {
    Route::get('/dashboard', fn() => redirect()->route('doctor.schedule'))->name('doctor.dashboard');

    Route::get('/profil', [DoctorProfileController::class, 'show'])->name('doctor.profile');
    Route::post('/neradni', [DoctorProfileController::class, 'addOffDay'])->name('doctor.offdays.add');
    Route::post('/neradni/{datum}/delete', [DoctorProfileController::class, 'deleteOffDay'])
        ->where('datum', '\d{4}-\d{2}-\d{2}')
        ->name('doctor.offdays.delete');

    Route::get('/raspored', [DoctorScheduleController::class, 'index'])->name('doctor.schedule');
    Route::get('/raspored/days', [DoctorScheduleController::class, 'days'])->name('doctor.schedule.days');
    Route::get('/raspored/day', [DoctorScheduleController::class, 'day'])->name('doctor.schedule.day');
    Route::post('/termini/{id}/otkazi', [DoctorScheduleController::class, 'cancel'])
        ->whereNumber('id')
        ->name('doctor.terms.cancel');

    Route::get('/rad', [DoctorWorkController::class, 'index'])->name('doctor.work');
    Route::post('/rad/{id}/finish', [DoctorWorkController::class, 'finish'])->whereNumber('id')->name('doctor.work.finish');
    Route::get('/rad/doc/view', [DoctorWorkController::class, 'docView'])->name('doctor.work.doc.view');
    Route::get('/rad/doc/download', [DoctorWorkController::class, 'docDownload'])->name('doctor.work.doc.download');
});
