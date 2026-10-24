<?php

require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/CustomerSession.php';
require_once __DIR__ . '/../models/MenuItem.php';
require_once __DIR__ . '/../models/TableSession.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Bill.php';
require_once __DIR__ . '/../models/SessionMember.php';

class OrderController
{
    /** Table session must exist, belong to this browser, and still be active. */
    private function activeSessionOrRedirect(): array
    {
        CustomerSession::requireTableSession();
        $session = TableSession::find(CustomerSession::tableSessionId());
        if (!$session || $session['status'] !== 'active') {
            CustomerSession::clearTableSession();
            flash_set('error', 'Your table session has ended. Please scan the QR code again if you\'re still dining.');
            redirect('');
        }
        return $session;
    }

    /**
     * Once staff have generated the bill for this table, its total is a frozen snapshot —
     * anything ordered after that point would reach the kitchen but never appear on any
     * bill. So the kitchen is closed for this session as soon as a bill exists.
     */
    private function orderingOpen(int $tableSessionId): bool
    {
        return !Bill::existsForSession($tableSessionId);
    }

    private function requireOrderingOpen(array $session): void
    {
        if (!$this->orderingOpen((int)$session['id'])) {
            unset($_SESSION['cart']);
            flash_set('error', 'The bill for this table has already been generated, so no more orders can be added. Ask your server if you\'d like something else.');
            redirect('order/mine');
        }
    }

    private function cart(): array
    {
        return $_SESSION['cart'] ?? [];
    }

    /**
     * Drop cart entries whose menu item went off the menu since it was added, and return
     * their ids, so the customer gets told rather than quietly receiving a smaller order.
     */
    private function pruneUnavailable(): array
    {
        $removed = [];
        foreach (array_keys($this->cart()) as $itemId) {
            if (!MenuItem::findAvailable((int)$itemId)) {
                $removed[] = MenuItem::find((int)$itemId)['name'] ?? 'An item';
                unset($_SESSION['cart'][$itemId]);
            }
        }
        return $removed;
    }

    private function cartLines(): array
    {
        $lines = [];
        foreach ($this->cart() as $itemId => $entry) {
            $item = MenuItem::findAvailable((int)$itemId);
            if (!$item) {
                continue;
            }
            $lines[] = [
                'menu_item_id' => $item['id'],
                'category_id'  => $item['category_id'],
                'name'         => $item['name'],
                'is_veg'       => $item['is_veg'],
                'price'        => (float)$item['price'],
                'quantity'     => $entry['quantity'],
                'notes'        => $entry['notes'] ?? null,
                'line_total'   => (float)$item['price'] * $entry['quantity'],
            ];
        }
        return $lines;
    }

    public function menu(): void
    {
        $session = $this->activeSessionOrRedirect();
        $this->requireOrderingOpen($session);
        $menu = MenuItem::availableGroupedByCategory();
        $pageTitle = 'Menu';
        require __DIR__ . '/../views/customer/order_menu.php';
    }

    public function cartData(): void
    {
        $this->activeSessionOrRedirect();
        header('Content-Type: application/json');
        $this->pruneUnavailable();
        $lines = $this->cartLines();
        echo json_encode([
            'lines' => $lines,
            'count' => array_sum(array_column($lines, 'quantity')),
            'total' => array_sum(array_column($lines, 'line_total')),
        ]);
    }

    public function cartAdd(): void
    {
        $session = $this->activeSessionOrRedirect();
        header('Content-Type: application/json');
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false]);
            return;
        }
        if (!$this->orderingOpen((int)$session['id'])) {
            echo json_encode(['ok' => false, 'closed' => true]);
            return;
        }

        $itemId = (int)($_POST['menu_item_id'] ?? 0);
        if (!MenuItem::findAvailable($itemId)) {
            echo json_encode(['ok' => false]);
            return;
        }

        $_SESSION['cart'][$itemId]['quantity'] = ($_SESSION['cart'][$itemId]['quantity'] ?? 0) + 1;
        echo json_encode(['ok' => true]);
    }

    public function cartUpdate(): void
    {
        $this->activeSessionOrRedirect();
        header('Content-Type: application/json');
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false]);
            return;
        }

        $itemId = (int)($_POST['menu_item_id'] ?? 0);
        $quantity = max(0, (int)($_POST['quantity'] ?? 0));

        if ($quantity === 0) {
            unset($_SESSION['cart'][$itemId]);
        } else {
            $_SESSION['cart'][$itemId]['quantity'] = $quantity;
        }
        if (isset($_POST['notes'])) {
            $_SESSION['cart'][$itemId]['notes'] = trim((string)$_POST['notes']) ?: null;
        }
        echo json_encode(['ok' => true]);
    }

    public function place(): void
    {
        $session = $this->activeSessionOrRedirect();
        Csrf::requireValid();
        $this->requireOrderingOpen($session);

        $removed = $this->pruneUnavailable();
        if (!empty($removed)) {
            flash_set('error', implode(', ', $removed) . ' just went off the menu, so ' .
                (count($removed) === 1 ? 'it was' : 'they were') .
                ' removed from your cart. Please check your order and place it again.');
            redirect('order/menu');
        }

        $lines = $this->cartLines();
        if (empty($lines)) {
            flash_set('error', 'Your cart is empty.');
            redirect('order/menu');
        }

        $orderId = Order::create($session['id'], CustomerSession::sessionMemberId());
        OrderItem::createMany($orderId, $lines);
        unset($_SESSION['cart']);

        flash_set('success', 'Order placed! The kitchen has it now.');
        redirect('order/mine');
    }

    public function mine(): void
    {
        $session = $this->activeSessionOrRedirect();
        $orders = Order::allBySessionMember(CustomerSession::sessionMemberId());
        $itemsByOrder = [];
        foreach ($orders as $order) {
            $itemsByOrder[$order['id']] = OrderItem::allByOrder($order['id']);
        }
        $bills = Bill::allBySession($session['id']);
        $myMemberId = CustomerSession::sessionMemberId();
        $myBills = array_filter($bills, fn($b) => $b['bill_type'] === 'combined' || (int)$b['session_member_id'] === $myMemberId);
        $orderingClosed = !$this->orderingOpen((int)$session['id']);

        $pageTitle = 'My Orders';
        require __DIR__ . '/../views/customer/order_mine.php';
    }
}
