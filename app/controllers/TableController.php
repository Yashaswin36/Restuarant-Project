<?php

require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Otp.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../core/CustomerSession.php';
require_once __DIR__ . '/../models/RestaurantTable.php';
require_once __DIR__ . '/../models/TableSession.php';
require_once __DIR__ . '/../models/SessionMember.php';
require_once __DIR__ . '/../models/Customer.php';

class TableController
{
    private function tableOrFail(): array
    {
        $code = $_GET['code'] ?? $_POST['code'] ?? '';
        $table = RestaurantTable::findByCode($code);
        if (!$table) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }
        return $table;
    }

    public function join(): void
    {
        $table = $this->tableOrFail();
        $activeSession = TableSession::activeForTable($table['id']);
        $pageTitle = 'Table ' . $table['table_number'];
        require __DIR__ . '/../views/customer/table_join.php';
    }

    public function startForm(): void
    {
        $table = $this->tableOrFail();
        if (TableSession::activeForTable($table['id'])) {
            flash_set('error', 'This table already has an active session — ask your host for the join code.');
            redirect('table/join&code=' . $table['table_code']);
        }
        $pageTitle = 'Start a Table Session';
        require __DIR__ . '/../views/customer/table_start.php';
    }

    public function startSendOtp(): void
    {
        Csrf::requireValid();
        $table = $this->tableOrFail();

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email');
        if ($v->fails() || TableSession::activeForTable($table['id'])) {
            flash_set('error', 'Please fill in your name and a valid email.');
            redirect('table/start&code=' . $table['table_code']);
        }

        $_SESSION['pending_host'] = [
            'table_id'   => $table['id'],
            'table_code' => $table['table_code'],
            'name'       => trim($_POST['name']),
            'email'      => strtolower(trim($_POST['email'])),
        ];

        $code = Otp::generate($_SESSION['pending_host']['email'], 'table_session');
        Mailer::sendOtp($_SESSION['pending_host']['email'], $code, 'start ordering at your table');

        $pageTitle = 'Verify Your Email';
        require __DIR__ . '/../views/customer/table_start_otp.php';
    }

    public function startVerify(): void
    {
        Csrf::requireValid();
        $pending = $_SESSION['pending_host'] ?? null;
        if (!$pending) {
            redirect('');
        }

        $otp = trim($_POST['otp'] ?? '');
        if (!Otp::verify($pending['email'], 'table_session', $otp)) {
            flash_set('error', 'Incorrect or expired code. Please try again.');
            $pageTitle = 'Verify Your Email';
            require __DIR__ . '/../views/customer/table_start_otp.php';
            return;
        }

        // Re-check right before creating — someone else may have started a session while this host was typing their OTP.
        if (TableSession::activeForTable($pending['table_id'])) {
            unset($_SESSION['pending_host']);
            flash_set('error', 'Someone already started a session at this table — ask them for the join code.');
            redirect('table/join&code=' . $pending['table_code']);
        }

        $customer = Customer::findOrCreate($pending['name'], $pending['email']);
        $sessionId = TableSession::create($pending['table_id'], (int)$customer['id']);
        $memberId = SessionMember::create($sessionId, (int)$customer['id'], $customer['name'], true);

        unset($_SESSION['pending_host']);
        CustomerSession::setTableSession($memberId, $sessionId, $customer['name'], true);

        redirect('table/started');
    }

    /** Confirmation screen showing the join code to share, right after starting a session. */
    public function started(): void
    {
        CustomerSession::requireTableSession();
        $session = TableSession::findWithTable(CustomerSession::tableSessionId());
        if (!$session || !CustomerSession::isHost()) {
            redirect('order/menu');
        }
        $pageTitle = 'Session Started';
        require __DIR__ . '/../views/customer/table_started.php';
    }

    public function joinCodeForm(): void
    {
        $table = $this->tableOrFail();
        if (!TableSession::activeForTable($table['id'])) {
            flash_set('error', 'No active session at this table yet — be the first to start one!');
            redirect('table/join&code=' . $table['table_code']);
        }
        $pageTitle = 'Join Your Table';
        require __DIR__ . '/../views/customer/table_joincode.php';
    }

    public function joinCodeSubmit(): void
    {
        Csrf::requireValid();
        $table = $this->tableOrFail();

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->required($_POST, 'session_code', 'Join code');
        if ($v->fails()) {
            flash_set('error', 'Please enter your name and the join code.');
            redirect('table/joincode&code=' . $table['table_code']);
        }

        $session = TableSession::findActiveByTableAndCode($table['id'], trim($_POST['session_code']));

        // Re-submitting this form (browser back, double tap) would otherwise mint a second
        // session_members row for the same person — their earlier orders would stay attached
        // to the abandoned first row and drop out of their split bill.
        if ($session && CustomerSession::tableSessionId() === (int)$session['id']) {
            redirect('order/menu');
        }

        if (!$session) {
            flash_set('error', 'That code doesn\'t match an active session at this table.');
            redirect('table/joincode&code=' . $table['table_code']);
        }

        $email = trim($_POST['email'] ?? '');
        $customerId = null;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $customer = Customer::findOrCreate(trim($_POST['name']), strtolower($email));
            $customerId = (int)$customer['id'];
        }

        $displayName = trim($_POST['name']);
        $memberId = SessionMember::create($session['id'], $customerId, $displayName, false);
        CustomerSession::setTableSession($memberId, (int)$session['id'], $displayName, false);

        redirect('order/menu');
    }
}
