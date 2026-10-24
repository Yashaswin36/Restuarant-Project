<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $route): void
{
    header('Location: /index.php?route=' . $route);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string
{
    if (empty($_SESSION['flash'][$type])) {
        return null;
    }
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

function money(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

/**
 * The veg / non-veg mark every Indian menu is legally required to carry: a filled circle
 * inside a square outline, green for vegetarian and dark red for non-vegetarian. Styled by
 * .fmark in partials/header.php. Replaces the coloured-circle emoji this project used to
 * print, which rendered differently on every operating system.
 */
function veg_mark(bool $isVeg): string
{
    $label = $isVeg ? 'Vegetarian' : 'Non-vegetarian';
    $class = $isVeg ? 'fmark fmark-veg' : 'fmark fmark-nonveg';
    return '<span class="' . $class . '" role="img" aria-label="' . $label . '" title="' . $label . '"></span>';
}

/**
 * Status pills, coloured by what the status actually means rather than all-amber:
 * waiting = quiet, in progress = amber, done/paid = green, dead = muted, problem = red.
 */
function status_badge(string $status): string
{
    $tones = [
        // order + order_item lifecycle
        'placed'    => 'bg-stone-100 text-stone-600 border-stone-200',
        'preparing' => 'bg-amber-100 text-amber-800 border-amber-200',
        'ready'     => 'bg-green-100 text-green-700 border-green-200',
        'served'    => 'bg-green-50 text-green-600 border-green-100',
        // reservations
        'pending'   => 'bg-stone-100 text-stone-600 border-stone-200',
        'confirmed' => 'bg-amber-100 text-amber-800 border-amber-200',
        'seated'    => 'bg-green-100 text-green-700 border-green-200',
        'completed' => 'bg-stone-100 text-stone-500 border-stone-200',
        'cancelled' => 'bg-red-50 text-red-700 border-red-100',
        // bills + accounts + tables
        'unpaid'    => 'bg-red-50 text-red-700 border-red-100',
        'paid'      => 'bg-green-100 text-green-700 border-green-200',
        'active'    => 'bg-green-100 text-green-700 border-green-200',
        'inactive'  => 'bg-stone-100 text-stone-500 border-stone-200',
        'closed'    => 'bg-stone-100 text-stone-500 border-stone-200',
    ];
    $tone = $tones[$status] ?? 'bg-stone-100 text-stone-600 border-stone-200';

    return '<span class="inline-block px-2 py-0.5 rounded border text-xs font-medium capitalize ' . $tone . '">'
        . e(str_replace('_', ' ', $status)) . '</span>';
}

/** The TableFare wordmark: a serving dome over a plate, drawn inline so it works offline. */
function wordmark(string $size = 'text-lg'): string
{
    return '<span class="font-display font-extrabold ' . $size . ' text-red-700 inline-flex items-center gap-2">'
        . '<svg viewBox="0 0 24 24" class="w-[1.15em] h-[1.15em] text-amber-600" fill="none" aria-hidden="true">'
        . '<path d="M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
        . '<path d="M4.5 15a7.5 7.5 0 0 1 15 0z" fill="currentColor"/>'
        . '<circle cx="12" cy="5.4" r="1.5" fill="currentColor"/>'
        . '</svg>TableFare</span>';
}
