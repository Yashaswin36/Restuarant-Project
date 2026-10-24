<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Qr.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/RestaurantTable.php';
require_once __DIR__ . '/../models/MenuCategory.php';
require_once __DIR__ . '/../models/MenuItem.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/TableSession.php';
require_once __DIR__ . '/../models/Bill.php';

class AdminController
{
    public function __construct()
    {
        Auth::requireRole('super_admin');
    }

    public function dashboard(): void
    {
        $stats = [
            'tables'        => count(RestaurantTable::all()),
            'staff'         => User::countByRole('staff'),
            'kitchen'       => User::countByRole('kitchen'),
            'pending_res'   => Reservation::countByStatus('pending'),
            'confirmed_res' => Reservation::countByStatus('confirmed'),
            'active_sessions' => count(TableSession::allActiveWithDetails()),
        ];
        $revenue = Bill::revenueStats();
        $todayQueue = Reservation::todayQueue(date('Y-m-d'));

        $pageTitle = 'Admin Dashboard';
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function tables(): void
    {
        $tables = RestaurantTable::all();
        foreach ($tables as &$table) {
            $table['join_url'] = Qr::joinUrl($table['table_code']);
            $table['qr_data_uri'] = Qr::dataUri($table['join_url']);
            $table['in_session'] = TableSession::activeForTable((int)$table['id']) !== null;
        }
        unset($table);

        $editing = isset($_GET['edit']) ? RestaurantTable::find((int)$_GET['edit']) : null;
        $pageTitle = 'Manage Tables';
        require __DIR__ . '/../views/admin/tables.php';
    }

    public function tableStore(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'table_number', 'Table number')
          ->required($_POST, 'capacity', 'Capacity')
          ->numeric($_POST, 'capacity', 'Capacity')
          ->min($_POST, 'capacity', 1, 'Capacity')
          ->required($_POST, 'zone', 'Zone');

        $number = trim($_POST['table_number'] ?? '');
        if (!$v->fails() && RestaurantTable::numberExists($number)) {
            flash_set('error', 'That table number already exists.');
            redirect('admin/tables');
        }
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/tables');
        }

        RestaurantTable::create($number, (int)$_POST['capacity'], trim($_POST['zone']));
        flash_set('success', 'Table added.');
        redirect('admin/tables');
    }

    public function tableUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $v = new Validator();
        $v->required($_POST, 'table_number', 'Table number')
          ->required($_POST, 'capacity', 'Capacity')
          ->numeric($_POST, 'capacity', 'Capacity')
          ->min($_POST, 'capacity', 1, 'Capacity')
          ->required($_POST, 'zone', 'Zone');

        $number = trim($_POST['table_number'] ?? '');
        if (!RestaurantTable::find($id) || $v->fails() || RestaurantTable::numberExists($number, $id)) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/tables');
        }

        RestaurantTable::update($id, $number, (int)$_POST['capacity'], trim($_POST['zone']));
        flash_set('success', 'Table updated.');
        redirect('admin/tables');
    }

    public function tableToggleStatus(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $table = RestaurantTable::find($id);
        if ($table) {
            // Taking a table out of service while guests are mid-meal on it orphans the
            // sitting: the staff Table Status board lists active tables only, so the live
            // session would vanish from it and could no longer be opened, billed or closed.
            if ($table['status'] === 'active' && TableSession::activeForTable($id)) {
                flash_set('error', 'Table ' . $table['table_number'] . ' has guests dining on it right now — close its session from the staff Table Status board before taking it out of service.');
                redirect('admin/tables');
            }
            RestaurantTable::setStatus($id, $table['status'] === 'active' ? 'inactive' : 'active');
            flash_set('success', 'Table status updated.');
        }
        redirect('admin/tables');
    }

    public function tableQrDownload(): void
    {
        $table = RestaurantTable::find((int)($_GET['id'] ?? 0));
        if (!$table) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $png = Qr::pngBinary(Qr::joinUrl($table['table_code']));
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="table-' . $table['table_number'] . '-qr.png"');
        echo $png;
    }

    public function menu(): void
    {
        $categories = MenuCategory::all();
        $items = MenuItem::allWithCategory();
        $editingCategory = isset($_GET['edit_category']) ? MenuCategory::find((int)$_GET['edit_category']) : null;
        $editingItem = isset($_GET['edit_item']) ? MenuItem::find((int)$_GET['edit_item']) : null;
        $pageTitle = 'Manage Menu';
        require __DIR__ . '/../views/admin/menu.php';
    }

    public function categoryStore(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'name', 'Category name');
        if ($v->fails()) {
            flash_set('error', 'Please provide a category name.');
            redirect('admin/menu');
        }
        MenuCategory::create(trim($_POST['name']), (int)($_POST['display_order'] ?? 0));
        flash_set('success', 'Category created.');
        redirect('admin/menu');
    }

    public function categoryUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $v = new Validator();
        $v->required($_POST, 'name', 'Category name');
        if ($v->fails() || !MenuCategory::find($id)) {
            flash_set('error', 'Invalid category.');
            redirect('admin/menu');
        }
        MenuCategory::update($id, trim($_POST['name']), (int)($_POST['display_order'] ?? 0));
        flash_set('success', 'Category updated.');
        redirect('admin/menu');
    }

    public function categoryDelete(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (MenuCategory::find($id)) {
            if (MenuCategory::hasOrderHistory($id)) {
                flash_set('error', 'Can\'t delete this category — it has items that have already been ordered.');
                redirect('admin/menu');
            }
            MenuCategory::delete($id);
            flash_set('success', 'Category deleted.');
        }
        redirect('admin/menu');
    }

    private function validateItemInput(array $data): Validator
    {
        $v = new Validator();
        $v->required($data, 'name', 'Name')
          ->required($data, 'category_id', 'Category')
          ->required($data, 'price', 'Price')
          ->numeric($data, 'price', 'Price')
          ->min($data, 'price', 0.01, 'Price');
        return $v;
    }

    public function itemStore(): void
    {
        Csrf::requireValid();
        $v = $this->validateItemInput($_POST);
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/menu');
        }
        MenuItem::create([
            'category_id'  => (int)$_POST['category_id'],
            'name'         => trim($_POST['name']),
            'description'  => trim($_POST['description'] ?? ''),
            'price'        => (float)$_POST['price'],
            'is_veg'       => isset($_POST['is_veg']) ? 1 : 0,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ]);
        flash_set('success', 'Menu item added.');
        redirect('admin/menu');
    }

    public function itemUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $v = $this->validateItemInput($_POST);
        if ($v->fails() || !MenuItem::find($id)) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/menu');
        }
        MenuItem::update($id, [
            'category_id'  => (int)$_POST['category_id'],
            'name'         => trim($_POST['name']),
            'description'  => trim($_POST['description'] ?? ''),
            'price'        => (float)$_POST['price'],
            'is_veg'       => isset($_POST['is_veg']) ? 1 : 0,
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ]);
        flash_set('success', 'Menu item updated.');
        redirect('admin/menu');
    }

    public function itemDelete(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (MenuItem::find($id)) {
            if (MenuItem::hasOrderHistory($id)) {
                flash_set('error', 'Can\'t delete this item — it has already been ordered.');
                redirect('admin/menu');
            }
            MenuItem::delete($id);
            flash_set('success', 'Menu item deleted.');
        }
        redirect('admin/menu');
    }

    public function staff(): void
    {
        $accounts = User::allStaffAndKitchen();
        $pageTitle = 'Staff & Kitchen Accounts';
        require __DIR__ . '/../views/admin/staff.php';
    }

    public function staffStore(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'name', 'Name')
          ->required($_POST, 'email', 'Email')
          ->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password')
          ->minLength($_POST, 'password', 8, 'Password')
          ->required($_POST, 'role', 'Role')
          ->inArray($_POST, 'role', ['staff', 'kitchen'], 'Role');

        $email = trim($_POST['email'] ?? '');
        if (!$v->fails() && User::emailExists($email)) {
            flash_set('error', 'That email is already registered.');
            redirect('admin/staff');
        }
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/staff');
        }
        User::create(trim($_POST['name']), $email, $_POST['password'], $_POST['role']);
        flash_set('success', 'Account created.');
        redirect('admin/staff');
    }

    public function staffToggleStatus(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $user = User::find($id);
        if ($user && in_array($user['role'], ['staff', 'kitchen'], true)) {
            User::setStatus($id, $user['status'] === 'active' ? 'inactive' : 'active');
            flash_set('success', 'Account status updated.');
        }
        redirect('admin/staff');
    }

    public function reservations(): void
    {
        $reservations = Reservation::allWithDetails();
        $pageTitle = 'All Reservations';
        require __DIR__ . '/../views/admin/reservations.php';
    }

    public function reports(): void
    {
        $revenue = Bill::revenueStats();
        $popularItems = Bill::popularItems();
        $pageTitle = 'Reports';
        require __DIR__ . '/../views/admin/reports.php';
    }
}
