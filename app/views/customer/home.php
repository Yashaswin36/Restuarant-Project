<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/customer_topbar.php'; ?>
<main class="flex-1 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <p class="eyebrow">Table booking &amp; dine-in ordering</p>
        <h1 class="mt-2 text-4xl sm:text-5xl leading-[1.02] text-stone-900">
            Your table,<br>on your phone.
        </h1>
        <p class="text-stone-500 mt-4 text-lg leading-relaxed">
            Reserve a table before you arrive, or scan the code on the table and order the moment
            you sit down — everyone at the table, on one bill or separate ones.
        </p>

        <div class="mt-8 space-y-3">
            <a href="/index.php?route=booking/create" class="block text-center py-3.5 rounded-xl bg-amber-600 text-white font-semibold hover:bg-amber-700 transition-colors">Book a table</a>
            <a href="/index.php?route=booking/lookup" class="block text-center py-3.5 rounded-xl border border-amber-300 text-amber-700 font-semibold hover:bg-amber-50 transition-colors">Find my booking</a>
        </div>

        <div class="mt-10 pt-6 border-t border-amber-200/70 flex gap-3 items-start">
            <svg viewBox="0 0 24 24" class="w-5 h-5 mt-0.5 text-amber-600 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/>
            </svg>
            <p class="text-sm text-stone-500 leading-relaxed">
                Already seated? Scan the code printed on your table — no booking or account needed.
            </p>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
