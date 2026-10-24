<?php

require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Otp.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../core/CustomerSession.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Reservation.php';

class BookingController
{
    public function createForm(): void
    {
        $pageTitle = 'Book a Table';
        require __DIR__ . '/../views/customer/booking_create.php';
    }

    public function createSendOtp(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'name', 'Name')
          ->required($_POST, 'email', 'Email')
          ->email($_POST, 'email', 'Email')
          ->required($_POST, 'reservation_date', 'Date')
          ->required($_POST, 'reservation_time', 'Time')
          ->required($_POST, 'party_size', 'Party size')
          ->numeric($_POST, 'party_size', 'Party size')
          ->min($_POST, 'party_size', 1, 'Party size');

        if ($v->fails()) {
            flash_set('error', 'Please fill in every field correctly.');
            redirect('booking/create');
        }

        $_SESSION['pending_booking'] = [
            'name'             => trim($_POST['name']),
            'email'            => strtolower(trim($_POST['email'])),
            'reservation_date' => $_POST['reservation_date'],
            'reservation_time' => $_POST['reservation_time'],
            'party_size'       => (int)$_POST['party_size'],
            'special_requests' => trim($_POST['special_requests'] ?? ''),
        ];

        $code = Otp::generate($_SESSION['pending_booking']['email'], 'booking');
        Mailer::sendOtp($_SESSION['pending_booking']['email'], $code, 'confirm your table booking');

        $pageTitle = 'Verify Your Email';
        require __DIR__ . '/../views/customer/booking_otp.php';
    }

    public function createVerify(): void
    {
        Csrf::requireValid();
        $pending = $_SESSION['pending_booking'] ?? null;
        if (!$pending) {
            redirect('booking/create');
        }

        $code = trim($_POST['otp'] ?? '');
        if (!Otp::verify($pending['email'], 'booking', $code)) {
            flash_set('error', 'Incorrect or expired code. Please try again.');
            $pageTitle = 'Verify Your Email';
            require __DIR__ . '/../views/customer/booking_otp.php';
            return;
        }

        $customer = Customer::findOrCreate($pending['name'], $pending['email']);
        $reservationId = Reservation::create(
            $customer['id'],
            $pending['reservation_date'],
            $pending['reservation_time'],
            $pending['party_size'],
            $pending['special_requests'] ?: null
        );
        unset($_SESSION['pending_booking']);
        CustomerSession::setBookingCustomer((int)$customer['id'], $customer['name'], $customer['email']);

        flash_set('success', 'Table booked! We look forward to seeing you.');
        redirect('booking/mine');
    }

    public function lookupForm(): void
    {
        $pageTitle = 'My Booking';
        require __DIR__ . '/../views/customer/booking_lookup.php';
    }

    public function lookupSendOtp(): void
    {
        Csrf::requireValid();
        $email = strtolower(trim($_POST['email'] ?? ''));
        $v = new Validator();
        $v->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email');
        if ($v->fails()) {
            flash_set('error', 'Please enter a valid email.');
            redirect('booking/lookup');
        }

        $_SESSION['pending_lookup_email'] = $email;
        $code = Otp::generate($email, 'booking');
        Mailer::sendOtp($email, $code, 'view your bookings');

        $pageTitle = 'Verify Your Email';
        require __DIR__ . '/../views/customer/booking_lookup_otp.php';
    }

    public function lookupVerify(): void
    {
        Csrf::requireValid();
        $email = $_SESSION['pending_lookup_email'] ?? null;
        if (!$email) {
            redirect('booking/lookup');
        }

        $code = trim($_POST['otp'] ?? '');
        if (!Otp::verify($email, 'booking', $code)) {
            flash_set('error', 'Incorrect or expired code. Please try again.');
            $pageTitle = 'Verify Your Email';
            require __DIR__ . '/../views/customer/booking_lookup_otp.php';
            return;
        }

        $customer = Customer::findByEmail($email);
        unset($_SESSION['pending_lookup_email']);

        if (!$customer) {
            flash_set('error', "We couldn't find any bookings for that email.");
            redirect('booking/lookup');
        }

        CustomerSession::setBookingCustomer((int)$customer['id'], $customer['name'], $customer['email']);
        redirect('booking/mine');
    }

    public function mine(): void
    {
        CustomerSession::requireBookingCustomer();
        $reservations = Reservation::allByCustomer(CustomerSession::bookingCustomerId());
        $pageTitle = 'My Bookings';
        require __DIR__ . '/../views/customer/booking_mine.php';
    }

    public function cancel(): void
    {
        Csrf::requireValid();
        CustomerSession::requireBookingCustomer();
        $id = (int)($_POST['id'] ?? 0);

        if (Reservation::belongsToCustomer($id, CustomerSession::bookingCustomerId())) {
            $reservation = Reservation::find($id);
            if (in_array($reservation['status'], ['pending', 'confirmed'], true)) {
                Reservation::setStatus($id, 'cancelled');
                flash_set('success', 'Booking cancelled.');
            }
        }
        redirect('booking/mine');
    }
}
