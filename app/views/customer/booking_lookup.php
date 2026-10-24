<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-amber-100 p-8">
        <h1 class="text-xl font-bold text-stone-800 mb-1">Find My Booking</h1>
        <p class="text-sm text-stone-500 mb-6">Enter the email you booked with.</p>

        <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

        <form method="POST" action="/index.php?route=booking/lookup/otp" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="email" name="email" required autofocus placeholder="you@example.com"
                   class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            <button type="submit" class="w-full py-2.5 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Send me a code</button>
        </form>
        <a href="/index.php" class="mt-6 inline-block text-sm text-stone-500 hover:underline">&larr; Back home</a>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
