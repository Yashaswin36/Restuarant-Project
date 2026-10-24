<?php
$role = Auth::role();
$navItems = [];
if ($role === 'super_admin') {
    $navItems = [
        'admin/dashboard'    => 'Dashboard',
        'admin/tables'       => 'Tables',
        'admin/menu'         => 'Menu',
        'admin/staff'        => 'Staff & Kitchen',
        'admin/reservations' => 'Reservations',
        'admin/reports'      => 'Reports',
    ];
} elseif ($role === 'staff') {
    $navItems = [
        'staff/dashboard'    => 'Dashboard',
        'staff/reservations' => 'Reservations',
        'staff/tables'       => 'Tables',
    ];
} elseif ($role === 'kitchen') {
    $navItems = [
        'kitchen/queue' => 'Live Queue',
    ];
}
$currentRoute = $_GET['route'] ?? '';
?>
<nav class="bg-white border-b border-amber-100 px-6 py-3 flex items-center gap-8 shadow-sm">
    <?= wordmark() ?>
    <ul class="flex gap-6 flex-1">
        <?php foreach ($navItems as $route => $label): ?>
            <li>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="text-sm font-medium <?= str_starts_with($currentRoute, explode('/', $route)[0] . '/') ? 'text-red-700' : 'text-stone-500 hover:text-red-700' ?>">
                    <?= e($label) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="flex items-center gap-3 text-sm">
        <span class="text-stone-700 font-medium"><?= e(Auth::name()) ?></span>
        <span class="text-xs text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full capitalize"><?= e(str_replace('_', ' ', $role)) ?></span>
        <form method="POST" action="/index.php?route=logout">
            <?= Csrf::field() ?>
            <button type="submit" class="text-stone-500 hover:text-red-700">Logout</button>
        </form>
    </div>
</nav>
