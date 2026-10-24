<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-amber-100 p-8">
        <h1 class="text-xl font-bold text-stone-800 mb-1">Book a Table</h1>
        <p class="text-sm text-stone-500 mb-6">We'll email you a code to confirm.</p>

        <?php if ($msg = flash_get('error')): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2"><?= e($msg) ?></div><?php endif; ?>

        <form method="POST" action="/index.php?route=booking/create/otp" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="block text-sm font-medium text-stone-600 mb-1">Your Name</label>
                <input type="text" name="name" required class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-600 mb-1">Email</label>
                <input type="email" name="email" required class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-stone-600 mb-1">Date</label>
                    <input type="date" name="reservation_date" required min="<?= date('Y-m-d') ?>" class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-600 mb-1">Time</label>
                    <input type="time" name="reservation_time" required class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-600 mb-1">Party Size</label>
                <input type="number" name="party_size" min="1" value="2" required class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-stone-600 mb-1">Special Requests <span class="text-stone-400">(optional)</span></label>
                <input type="text" name="special_requests" class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
            <button type="submit" class="w-full py-2.5 rounded-lg bg-amber-600 text-white font-semibold hover:bg-amber-700">Send me a code</button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-500">
            Already booked? <a href="/index.php?route=booking/lookup" class="text-amber-700 hover:underline">Find my booking</a>
        </p>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
