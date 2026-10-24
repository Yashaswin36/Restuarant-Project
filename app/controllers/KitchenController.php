<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../models/OrderItem.php';

class KitchenController
{
    public function __construct()
    {
        Auth::requireRole('kitchen');
    }

    public function queue(): void
    {
        $categories = OrderItem::activeQueueByCategory();
        $recentlyReady = OrderItem::recentlyReady();
        $pageTitle = 'Live Order Queue';
        require __DIR__ . '/../views/kitchen/queue.php';
    }

    public function queueData(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'categories' => array_values(OrderItem::activeQueueByCategory()),
            'recentlyReady' => OrderItem::recentlyReady(),
        ]);
    }

    public function itemAdvance(): void
    {
        header('Content-Type: application/json');
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false]);
            return;
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        $ok = OrderItem::advanceStatus($itemId);
        echo json_encode(['ok' => $ok]);
    }
}
