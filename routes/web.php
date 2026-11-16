<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ChikondiCare routes
|--------------------------------------------------------------------------
| Every protected route carries a `permission:` middleware, and controllers/policies
| re-check authorisation and scope the data. Hidden menu items are never the only guard.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active', 'inactivity'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Password change must stay reachable while must_change_password is set.
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.update');

    Route::middleware('password.fresh')->group(function () {
        Route::redirect('/', '/dashboard');
        Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

        // ---------------- Administration ----------------
        Route::middleware('permission:staff.manage')->prefix('admin/staff')->name('staff.')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::get('/create', [StaffController::class, 'create'])->name('create');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{user}', [StaffController::class, 'update'])->name('update');
            Route::post('/{user}/toggle', [StaffController::class, 'toggle'])->name('toggle');
            Route::post('/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('reset');
        });

        Route::middleware('permission:medicines.manage')->prefix('admin/medicines')->name('medicines.')->group(function () {
            Route::get('/', [MedicineController::class, 'index'])->name('index');
            Route::get('/create', [MedicineController::class, 'create'])->name('create');
            Route::post('/', [MedicineController::class, 'store'])->name('store');
            Route::get('/{medicine}/edit', [MedicineController::class, 'edit'])->name('edit');
            Route::put('/{medicine}', [MedicineController::class, 'update'])->name('update');
            Route::post('/{medicine}/toggle', [MedicineController::class, 'toggle'])->name('toggle');
        });

        Route::middleware('permission:fees.manage')->prefix('admin/fees')->name('fees.')->group(function () {
            Route::get('/', [FeeController::class, 'index'])->name('index');
            Route::post('/', [FeeController::class, 'store'])->name('store');
        });

        Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');

        // ---------------- Doctors ----------------
        Route::prefix('doctors')->name('doctors.')->group(function () {
            Route::middleware('permission:doctors.manage')->group(function () {
                Route::get('/create', [DoctorController::class, 'create'])->name('create');
                Route::post('/', [DoctorController::class, 'store'])->name('store');
                Route::get('/{doctor}/edit', [DoctorController::class, 'edit'])->name('edit');
                Route::put('/{doctor}', [DoctorController::class, 'update'])->name('update');
                Route::post('/{doctor}/toggle', [DoctorController::class, 'toggle'])->name('toggle');
                Route::post('/{doctor}/schedules', [DoctorController::class, 'storeSchedule'])->name('schedules.store');
                Route::delete('/{doctor}/schedules/{schedule}', [DoctorController::class, 'destroySchedule'])->name('schedules.destroy')->scopeBindings();
            });
            Route::middleware('permission:doctors.view')->group(function () {
                Route::get('/', [DoctorController::class, 'index'])->name('index');
                Route::get('/{doctor}', [DoctorController::class, 'show'])->name('show')->whereNumber('doctor');
            });
        });

        // ---------------- Patients ----------------
        Route::prefix('patients')->name('patients.')->group(function () {
            Route::middleware('permission:patients.register')->group(function () {
                Route::get('/create', [PatientController::class, 'create'])->name('create');
                Route::post('/', [PatientController::class, 'store'])->name('store');
            });
            Route::middleware('permission:patients.update')->group(function () {
                Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');
                Route::put('/{patient}', [PatientController::class, 'update'])->name('update');
            });
            Route::middleware('permission:patients.view')->group(function () {
                Route::get('/', [PatientController::class, 'index'])->name('index');
                Route::get('/{patient}', [PatientController::class, 'show'])->name('show')->whereNumber('patient');
            });
            Route::get('/{patient}/prescriptions', [ReportController::class, 'patientPrescriptions'])
                ->middleware('permission:prescriptions.view')->name('prescriptions')->whereNumber('patient');
        });

        // ---------------- Appointments ----------------
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::middleware('permission:appointments.book')->group(function () {
                Route::get('/book', [AppointmentController::class, 'book'])->name('book');
                Route::post('/', [AppointmentController::class, 'store'])->name('store');
            });
            Route::middleware('permission:appointments.view')->group(function () {
                Route::get('/', [AppointmentController::class, 'index'])->name('index');
                Route::get('/schedule', [AppointmentController::class, 'schedule'])->name('schedule');
            });
            Route::middleware('permission:appointments.view,checkin.perform')->get('/{appointment}', [AppointmentController::class, 'show'])
                ->name('show')->whereNumber('appointment');
            Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])
                ->middleware('permission:appointments.cancel')->name('cancel')->whereNumber('appointment');
        });

        // ---------------- Check-in ----------------
        Route::middleware('permission:checkin.perform')->prefix('check-in')->name('checkin.')->group(function () {
            Route::get('/', [CheckInController::class, 'index'])->name('index');
            Route::post('/{appointment}', [CheckInController::class, 'store'])->name('store');
            Route::post('/{appointment}/no-show', [CheckInController::class, 'noShow'])->name('noshow');
        });

        // ---------------- Consultations ----------------
        Route::prefix('consultations')->name('consultations.')->group(function () {
            Route::middleware('permission:consultations.perform')->group(function () {
                Route::get('/queue', [ConsultationController::class, 'queue'])->name('queue');
                Route::get('/appointment/{appointment}', [ConsultationController::class, 'create'])->name('create');
                Route::post('/appointment/{appointment}', [ConsultationController::class, 'store'])->name('store');
            });
            Route::middleware('permission:consultations.view')->group(function () {
                Route::get('/', [ConsultationController::class, 'index'])->name('index');
                Route::get('/{consultation}', [ConsultationController::class, 'show'])->name('show')->whereNumber('consultation');
            });
        });

        // ---------------- Payments ----------------
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::middleware('permission:payments.record')->group(function () {
                Route::get('/pending', [PaymentController::class, 'pending'])->name('pending');
                Route::get('/create/{consultation}', [PaymentController::class, 'create'])->name('create');
                Route::post('/{consultation}', [PaymentController::class, 'store'])->name('store')->whereNumber('consultation');
            });
            Route::middleware('permission:payments.view')->group(function () {
                Route::get('/', [PaymentController::class, 'index'])->name('index');
                Route::get('/{payment}', [PaymentController::class, 'show'])->name('show')->whereNumber('payment');
                Route::get('/{payment}/receipt', [PaymentController::class, 'receipt'])->name('receipt')->whereNumber('payment');
            });
        });

        // ---------------- Reports ----------------
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/daily-appointments', [ReportController::class, 'daily'])->middleware('permission:reports.daily')->name('daily');
            Route::get('/doctor-patients', [ReportController::class, 'doctorPatients'])->middleware('permission:reports.doctor_patients')->name('doctor-patients');
            Route::get('/visiting-schedule', [ReportController::class, 'visiting'])->middleware('permission:reports.visiting')->name('visiting');
            Route::middleware('permission:reports.revenue')->group(function () {
                Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
                Route::get('/revenue/download', [ReportController::class, 'revenueDownload'])->name('revenue.download');
            });
        });
    });
});
