<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\BookController;
use App\Controllers\CatalogController;
use App\Controllers\DashboardController;
use App\Controllers\LoanController;
use App\Controllers\PatronController;
use App\Controllers\SettingsController;
use App\Core\Auth;
use App\Core\Env;
use App\Core\Session;
use App\Core\View;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
header('Cache-Control: no-store, private');

require __DIR__ . '/app/Core/Env.php';
Env::load(__DIR__ . '/.env');

require __DIR__ . '/config/Database.php';
require __DIR__ . '/app/Core/Session.php';
require __DIR__ . '/app/Core/Security.php';
require __DIR__ . '/app/Core/View.php';
require __DIR__ . '/app/Core/Controller.php';
require __DIR__ . '/app/Core/Auth.php';
require __DIR__ . '/app/Core/ExcelExport.php';
require __DIR__ . '/app/Models/Institution.php';
require __DIR__ . '/app/Models/Staff.php';
require __DIR__ . '/app/Models/Book.php';
require __DIR__ . '/app/Models/Patron.php';
require __DIR__ . '/app/Models/Loan.php';
require __DIR__ . '/app/Models/Settings.php';
require __DIR__ . '/app/Controllers/AuthController.php';
require __DIR__ . '/app/Controllers/AccountController.php';
require __DIR__ . '/app/Controllers/DashboardController.php';
require __DIR__ . '/app/Controllers/BookController.php';
require __DIR__ . '/app/Controllers/PatronController.php';
require __DIR__ . '/app/Controllers/LoanController.php';
require __DIR__ . '/app/Controllers/SettingsController.php';
require __DIR__ . '/app/Models/CatalogEntry.php';
require __DIR__ . '/app/Controllers/CatalogController.php';

set_exception_handler(static function (\Throwable $exception): void {
    error_log('Unhandled application error: ' . $exception->getMessage());
    http_response_code(500);
    if (class_exists(View::class)) {
        View::render('errors/500', ['title' => 'Error interno']);
        return;
    }
    echo 'Ocurrió un error interno. Revisa el registro del servidor.';
});

Session::start();

$route = (string)($_GET['r'] ?? 'dashboard');
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($route === 'login') {
    (new AuthController())->login($method);
    exit;
}

if ($route === 'setup') {
    (new AuthController())->setup($method);
    exit;
}

if ($route === 'logout') {
    (new AuthController())->logout();
    exit;
}

if (Auth::user() === null) {
    View::redirect('login');
}

switch ($route) {
    case 'dashboard':
        (new DashboardController())->index();
        break;
    case 'account/password':
        (new AccountController())->password($method);
        break;
    case 'books':
        (new BookController())->index();
        break;
    case 'books/export':
        (new BookController())->export();
        break;
    case 'books/create':
        (new BookController())->create($method);
        break;
    case 'books/deactivate':
        (new BookController())->deactivate($method);
        break;
    case 'books/copy':
        (new BookController())->copy();
        break;
    case 'catalogs':
        (new CatalogController())->index($method);
        break;
    case 'patrons':
        (new PatronController())->index();
        break;
    case 'patrons/create':
        (new PatronController())->create($method);
        break;
    case 'patrons/deactivate':
        (new PatronController())->deactivate();
        break;
    case 'loans':
        (new LoanController())->index();
        break;
    case 'loans/create':
        (new LoanController())->create($method);
        break;
    case 'loans/return':
        (new LoanController())->returnCopy();
        break;
    case 'settings':
        (new SettingsController())->index($method);
        break;
    case 'about':
        View::render('about', ['title' => 'Acerca de']);
        break;
    default:
        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada']);
}
