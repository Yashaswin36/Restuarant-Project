<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/RestaurantTable.php';
require_once __DIR__ . '/../models/TableSession.php';
require_once __DIR__ . '/../models/SessionMember.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Bill.php';

class StaffController
{
    public function __construct()
    {
        Auth::requireRole('staff');
    }

    public function dashboard(): void
    {
        $todayQueue = Reservation::todayQueue(date('Y-m-d'));
        $activeSessions = TableSession::allActiveWithDetails();
        $pageTitle = 'Staff Dashboard';
        require __DIR__ . '/../views/staff/dashboard.php';
    }

    public function reservations(): void
    {
        $reservations = Reservation::allWithDetails();
        $tables = RestaurantTable::allActive();
        $pageTitle = 'Reservation Queue';
        require __DIR__ . '/../views/staff/reservations.php';
    }

    /**
     * Reservation actions are only offered for certain statuses in the view, but the floor
     * runs on several tablets at once — a page loaded a minute ago still shows the old
     * buttons. Re-check the current status server-side so a stale page can't, say, cancel
     * a reservation another staff member has already seated.
     */
    private function reservationInStatusOrReject(int $id, array $allowed, string $message): array
    {
        $reservation = Reservation::find($id);
        if (!$reservation) {
            redirect('staff/reservations');
        }
        if (!in_array($reservation['status'], $allowed, true)) {
            flash_set('error', $message . ' (it is now "' . $reservation['status'] . '").');
            redirect('staff/reservations');
        }
        return $reservation;
    }

    public function reservationConfirm(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $this->reservationInStatusOrReject($id, ['pending'], 'Only a pending reservation can be confirmed');
        Reservation::setStatus($id, 'confirmed');
        flash_set('success', 'Reservation confirmed.');
        redirect('staff/reservations');
    }

    public function reservationCancel(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $this->reservationInStatusOrReject(
            $id,
            ['pending', 'confirmed'],
            'This reservation can no longer be cancelled here'
        );
        Reservation::setStatus($id, 'cancelled');
        flash_set('success', 'Reservation cancelled.');
        redirect('staff/reservations');
    }

    public function reservationSeat(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $tableId = (int)($_POST['table_id'] ?? 0);
        $this->reservationInStatusOrReject($id, ['confirmed'], 'Only a confirmed reservation can be seated');
        $table = RestaurantTable::find($tableId);
        if ($table && $table['status'] !== 'active') {
            flash_set('error', 'That table has been taken out of service — pick another table.');
            redirect('staff/reservations');
        }
        if (Reservation::find($id) && $table) {
            // Guard the same race TableController::startVerify() already guards for QR joins:
            // another walk-in/reservation may have taken this table since the staff view was loaded.
            if (TableSession::activeForTable($tableId) || Reservation::otherSeatedAtTable($tableId, $id)) {
                flash_set('error', 'That table is already occupied — pick another table.');
                redirect('staff/reservations');
            }
            Reservation::seatAtTable($id, $tableId);
            flash_set('success', 'Guest seated. They can now scan the table QR to order.');
        }
        redirect('staff/reservations');
    }

    public function tables(): void
    {
        $tables = RestaurantTable::allActive();
        $activeSessions = TableSession::allActiveWithDetails();
        $sessionsByTable = [];
        foreach ($activeSessions as $s) {
            $sessionsByTable[$s['table_id']] = $s;
        }
        $pageTitle = 'Table Status';
        require __DIR__ . '/../views/staff/tables.php';
    }

    private function sessionOrFail(int $id): array
    {
        $session = TableSession::findWithTable($id);
        if (!$session) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }
        return $session;
    }

    public function sessionDetail(): void
    {
        $session = $this->sessionOrFail((int)($_GET['id'] ?? 0));
        $members = SessionMember::allBySession($session['id']);
        $orders = Order::allBySessionWithMember($session['id']);
        $itemsByOrder = [];
        foreach ($orders as $order) {
            $itemsByOrder[$order['id']] = OrderItem::allByOrder($order['id']);
        }
        $hasBills = Bill::existsForSession($session['id']);
        $pageTitle = 'Table ' . $session['table_number'];
        require __DIR__ . '/../views/staff/session_detail.php';
    }

    public function sessionForceClose(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $session = TableSession::find($id);
        if ($session && $session['status'] === 'active') {
            // Force-close is for abandoned/stale sittings. If a bill has already been
            // printed and is still unpaid, closing here would quietly write the table off
            // as settled — send staff to Billing to record (or collect) the payment first.
            if (Bill::unpaidCountForSession($id) > 0) {
                flash_set('error', 'This table has an unpaid bill — settle it in Billing before closing the session.');
                redirect('staff/sessions/detail&id=' . $id);
            }
            TableSession::close($id);
            flash_set('success', 'Table session closed.');
        }
        redirect('staff/tables');
    }

    public function orderMarkServed(): void
    {
        Csrf::requireValid();
        $orderId = (int)($_POST['order_id'] ?? 0);
        $sessionId = (int)($_POST['session_id'] ?? 0);
        if (!Order::markServed($orderId, $sessionId)) {
            flash_set('error', 'That order isn\'t ready to be served yet — another staff member may have already served it.');
        }
        redirect('staff/sessions/detail&id=' . $sessionId);
    }

    public function billing(): void
    {
        $session = $this->sessionOrFail((int)($_GET['id'] ?? 0));
        $members = SessionMember::allBySession($session['id']);
        $bills = Bill::allBySession($session['id']);
        $pageTitle = 'Billing — Table ' . $session['table_number'];
        require __DIR__ . '/../views/staff/billing.php';
    }

    public function billingGenerate(): void
    {
        Csrf::requireValid();
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $mode = $_POST['mode'] ?? '';

        if (!TableSession::find($sessionId) || !in_array($mode, ['combined', 'split'], true)) {
            redirect('staff/tables');
        }
        if (Bill::existsForSession($sessionId)) {
            flash_set('error', 'A bill has already been generated for this table session.');
            redirect('staff/billing&id=' . $sessionId);
        }

        if ($mode === 'combined') {
            Bill::generateCombined($sessionId, Auth::id());
        } else {
            Bill::generateSplit($sessionId, Auth::id());
        }

        flash_set('success', 'Bill generated.');
        redirect('staff/billing&id=' . $sessionId);
    }

    public function billingMarkPaid(): void
    {
        Csrf::requireValid();
        $billId = (int)($_POST['bill_id'] ?? 0);
        $sessionId = (int)($_POST['session_id'] ?? 0);

        if (!Bill::markPaid($billId, $sessionId)) {
            flash_set('error', 'That bill was already marked paid — no second payment was recorded.');
            redirect('staff/billing&id=' . $sessionId);
        }

        if (Bill::isFullyPaid($sessionId)) {
            $session = TableSession::find($sessionId);
            if ($session && $session['status'] === 'active') {
                TableSession::close($sessionId);
            }
        }

        flash_set('success', 'Payment recorded.');
        redirect('staff/billing&id=' . $sessionId);
    }
}
