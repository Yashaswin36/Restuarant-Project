<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-amber-100 p-8 text-center">
        <h1 class="text-xl font-bold text-stone-800 mb-1">Check your email</h1>
        <p class="text-sm text-stone-500 mb-6">We sent a 6-digit code to <strong><?= e($_SESSION['pending_booking']['email'] ?? '') ?></strong>.</p>

        <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

        <form method="POST" action="/index.php?route=booking/create/verify" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="000000"
                   class="w-full text-center text-2xl tracking-[0.5em] rounded-lg border border-stone-300 px-3 py-3 focus:outline-none focus:ring-2 focus:ring-amber-400">
            <button type="submit" class="w-full py-2.5 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Confirm Booking</button>
        </form>
        <a href="/index.php?route=booking/create" class="mt-4 inline-block text-sm text-stone-500 hover:underline">&larr; Start over</a>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
