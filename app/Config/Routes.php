<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------------------
// Public routes
// ---------------------------------------------------------------
$routes->get('/', 'Auth::login');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');

// ---------------------------------------------------------------
// Protected routes – require authentication
// ---------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'Dashboard::index');

    // ---- Users (admin only) ----
    $routes->get('users',                   'UsersController::index');
    $routes->get('users/create',            'UsersController::create');
    $routes->post('users/store',            'UsersController::store');
    $routes->get('users/edit/(:num)',       'UsersController::edit/$1');
    $routes->post('users/update/(:num)',    'UsersController::update/$1');
    $routes->get('users/delete/(:num)',     'UsersController::delete/$1');

    // ---- Employees ----
    $routes->get('employees',               'EmployeesController::index');
    $routes->get('employees/create',        'EmployeesController::create');
    $routes->post('employees/store',        'EmployeesController::store');
    $routes->get('employees/view/(:num)',   'EmployeesController::view/$1');
    $routes->get('employees/edit/(:num)',   'EmployeesController::edit/$1');
    $routes->post('employees/update/(:num)','EmployeesController::update/$1');
    $routes->get('employees/delete/(:num)', 'EmployeesController::delete/$1');
    $routes->get('employees/dtr/(:num)',    'EmployeesController::dtr/$1');

    // ---- Attendance ----
    $routes->get('attendance',                  'AttendanceController::dashboard');
    $routes->post('attendance/store',           'AttendanceController::store');
    $routes->get('attendance/records',          'AttendanceController::records');
    $routes->get('attendance/view/(:num)',       'AttendanceController::view/$1');
    $routes->post('attendance/update-field',    'AttendanceController::updateField');
    $routes->post('attendance/delete-by-date',  'AttendanceController::deleteByDate');

    // ---- Payroll ----
    $routes->get('payroll',                     'PayrollController::index');
    $routes->get('payroll/create',              'PayrollController::create');
    $routes->get('payroll/generate',            'PayrollController::create');
    $routes->post('payroll/generate',           'PayrollController::generate');
    $routes->get('payroll/view/(:num)',         'PayrollController::view/$1');
    $routes->get('payroll/print/(:num)',        'PayrollController::print/$1');
    $routes->get('payroll/finalize/(:num)',     'PayrollController::finalize/$1');
    $routes->get('payroll/recalculate/(:num)',  'PayrollController::recalculate/$1');
    $routes->get('payroll/delete/(:num)',       'PayrollController::delete/$1');

    // ---- Payslip ----
    $routes->get('payslip/view/(:num)',    'PayslipController::view/$1');
    $routes->get('payslip/bulk/(:num)',    'PayslipController::bulk/$1');

    // ---- Deductions ----
    $routes->get('deductions',                      'DeductionsController::index');
    $routes->get('deductions/create',               'DeductionsController::create');
    $routes->post('deductions/store',               'DeductionsController::store');
    $routes->get('deductions/summary',               'DeductionsController::summary');
    $routes->get('deductions/view/(:num)',           'DeductionsController::view/$1');
    $routes->get('deductions/edit/(:num)',           'DeductionsController::edit/$1');
    $routes->post('deductions/update/(:num)',        'DeductionsController::update/$1');
    $routes->get('deductions/delete/(:num)',         'DeductionsController::delete/$1');
    $routes->get('deductions/complete/(:num)',       'DeductionsController::markComplete/$1');

    // ---- Benefits ----
    $routes->get('benefits',                            'BenefitsController::index');
    $routes->get('benefits/create',                     'BenefitsController::create');
    $routes->post('benefits/store',                     'BenefitsController::store');
    $routes->get('benefits/edit/(:num)',                'BenefitsController::edit/$1');
    $routes->post('benefits/update/(:num)',             'BenefitsController::update/$1');
    $routes->get('benefits/delete/(:num)',              'BenefitsController::delete/$1');
    $routes->get('benefits/assign/(:num)',              'BenefitsController::assign/$1');
    $routes->post('benefits/assign-store/(:num)',       'BenefitsController::assignStore/$1');
    $routes->get('benefits/assignment-delete/(:num)',   'BenefitsController::assignDelete/$1');
    $routes->get('benefits/summary',                    'BenefitsController::summary');

    // ---- Special Day Payroll Adjustments ----
    $routes->get('special-days',                    'SpecialDaysController::index');
    $routes->get('special-days/create',             'SpecialDaysController::create');
    $routes->post('special-days/store',             'SpecialDaysController::store');
    $routes->get('special-days/edit/(:num)',        'SpecialDaysController::edit/$1');
    $routes->post('special-days/update/(:num)',     'SpecialDaysController::update/$1');
    $routes->get('special-days/delete/(:num)',      'SpecialDaysController::delete/$1');

    // ---- Reports ----
    $routes->get('reports',                'ReportsController::index');
    $routes->get('reports/export-csv',     'ReportsController::exportCsv');

    // ---- Audit Logs (admin only) ----
    $routes->get('logs', 'LogsController::index');

    // ---- Settings / Control Panel (admin only) ----
    $routes->get('settings',                           'SettingsController::index');
    $routes->post('settings/general',                  'SettingsController::saveGeneral');
    $routes->post('settings/logo',                     'SettingsController::uploadLogo');
    $routes->get('settings/logo/remove',               'SettingsController::removeLogo');
    $routes->post('settings/colors',                   'SettingsController::saveColors');
    $routes->post('settings/department/add',           'SettingsController::addDepartment');
    $routes->post('settings/department/edit/(:num)',   'SettingsController::editDepartment/$1');
    $routes->get('settings/department/toggle/(:num)',  'SettingsController::toggleDepartment/$1');
    $routes->get('settings/department/delete/(:num)',  'SettingsController::deleteDepartment/$1');
    $routes->post('settings/branch/add',               'SettingsController::addBranch');
    $routes->post('settings/branch/edit/(:num)',        'SettingsController::editBranch/$1');
    $routes->get('settings/branch/toggle/(:num)',       'SettingsController::toggleBranch/$1');
    $routes->get('settings/branch/delete/(:num)',       'SettingsController::deleteBranch/$1');
    $routes->post('settings/permissions',               'SettingsController::savePermissions');
});
