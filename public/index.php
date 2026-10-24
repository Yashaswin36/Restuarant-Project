<?php

declare(strict_types=1);

// Let PHP's built-in dev server (`php -S`) serve real static files directly
// instead of routing every asset request through the front controller.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CustomerSession.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/StaffController.php';
require_once __DIR__ . '/../app/controllers/KitchenController.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';
require_once __DIR__ . '/../app/controllers/TableController.php';
require_once __DIR__ . '/../app/controllers/OrderController.php';

$router = new Router();

// ---- Auth (staff / kitchen / super admin) ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->post('logout', fn() => (new AuthController())->logout());

// ---- Super Admin ----
$router->get('admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('admin/tables', fn() => (new AdminController())->tables());
$router->post('admin/tables/store', fn() => (new AdminController())->tableStore());
$router->post('admin/tables/update', fn() => (new AdminController())->tableUpdate());
$router->post('admin/tables/status', fn() => (new AdminController())->tableToggleStatus());
$router->get('admin/tables/qr', fn() => (new AdminController())->tableQrDownload());
$router->get('admin/menu', fn() => (new AdminController())->menu());
$router->post('admin/menu/categories/store', fn() => (new AdminController())->categoryStore());
$router->post('admin/menu/categories/update', fn() => (new AdminController())->categoryUpdate());
$router->post('admin/menu/categories/delete', fn() => (new AdminController())->categoryDelete());
$router->post('admin/menu/items/store', fn() => (new AdminController())->itemStore());
$router->post('admin/menu/items/update', fn() => (new AdminController())->itemUpdate());
$router->post('admin/menu/items/delete', fn() => (new AdminController())->itemDelete());
$router->get('admin/staff', fn() => (new AdminController())->staff());
$router->post('admin/staff/store', fn() => (new AdminController())->staffStore());
$router->post('admin/staff/status', fn() => (new AdminController())->staffToggleStatus());
$router->get('admin/reservations', fn() => (new AdminController())->reservations());
$router->get('admin/reports', fn() => (new AdminController())->reports());

// ---- Staff ----
$router->get('staff/dashboard', fn() => (new StaffController())->dashboard());
$router->get('staff/reservations', fn() => (new StaffController())->reservations());
$router->post('staff/reservations/confirm', fn() => (new StaffController())->reservationConfirm());
$router->post('staff/reservations/cancel', fn() => (new StaffController())->reservationCancel());
$router->post('staff/reservations/seat', fn() => (new StaffController())->reservationSeat());
$router->get('staff/tables', fn() => (new StaffController())->tables());
$router->get('staff/sessions/detail', fn() => (new StaffController())->sessionDetail());
$router->post('staff/sessions/close', fn() => (new StaffController())->sessionForceClose());
$router->post('staff/orders/serve', fn() => (new StaffController())->orderMarkServed());
$router->get('staff/billing', fn() => (new StaffController())->billing());
$router->post('staff/billing/generate', fn() => (new StaffController())->billingGenerate());
$router->post('staff/billing/markpaid', fn() => (new StaffController())->billingMarkPaid());

// ---- Kitchen ----
$router->get('kitchen/queue', fn() => (new KitchenController())->queue());
$router->get('kitchen/queue/data', fn() => (new KitchenController())->queueData());
$router->post('kitchen/item/advance', fn() => (new KitchenController())->itemAdvance());

// ---- Customer: booking (pre-visit) ----
$router->get('booking/create', fn() => (new BookingController())->createForm());
$router->post('booking/create/otp', fn() => (new BookingController())->createSendOtp());
$router->post('booking/create/verify', fn() => (new BookingController())->createVerify());
$router->get('booking/lookup', fn() => (new BookingController())->lookupForm());
$router->post('booking/lookup/otp', fn() => (new BookingController())->lookupSendOtp());
$router->post('booking/lookup/verify', fn() => (new BookingController())->lookupVerify());
$router->get('booking/mine', fn() => (new BookingController())->mine());
$router->post('booking/cancel', fn() => (new BookingController())->cancel());

// ---- Customer: table QR / session join ----
$router->get('table/join', fn() => (new TableController())->join());
$router->get('table/start', fn() => (new TableController())->startForm());
$router->post('table/start/otp', fn() => (new TableController())->startSendOtp());
$router->post('table/start/verify', fn() => (new TableController())->startVerify());
$router->get('table/started', fn() => (new TableController())->started());
$router->get('table/joincode', fn() => (new TableController())->joinCodeForm());
$router->post('table/joincode/submit', fn() => (new TableController())->joinCodeSubmit());

// ---- Customer: ordering ----
$router->get('order/menu', fn() => (new OrderController())->menu());
$router->get('order/cart', fn() => (new OrderController())->cartData());
$router->post('order/cart/add', fn() => (new OrderController())->cartAdd());
$router->post('order/cart/update', fn() => (new OrderController())->cartUpdate());
$router->post('order/place', fn() => (new OrderController())->place());
$router->get('order/mine', fn() => (new OrderController())->mine());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    $pageTitle = 'Welcome';
    require __DIR__ . '/../app/views/customer/home.php';
    exit;
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
