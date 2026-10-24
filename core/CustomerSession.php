<?php

/**
 * Lightweight session helpers for the Customer persona, which never has a
 * password. Two independent bits of state live here:
 *  - "booking" identity: set after OTP-verifying an email for the
 *    pre-visit reservation flow (Book a Table / My Booking lookup).
 *  - "table session" identity: set after starting/joining a table session
 *    at the restaurant (QR scan flow) — this is what drives ordering.
 */
class CustomerSession
{
    public static function setBookingCustomer(int $customerId, string $name, string $email): void
    {
        $_SESSION['booking_customer_id'] = $customerId;
        $_SESSION['booking_customer_name'] = $name;
        $_SESSION['booking_customer_email'] = $email;
    }

    public static function bookingCustomerId(): ?int
    {
        return $_SESSION['booking_customer_id'] ?? null;
    }

    public static function requireBookingCustomer(): void
    {
        if (self::bookingCustomerId() === null) {
            redirect('booking/lookup');
        }
    }

    public static function setTableSession(int $sessionMemberId, int $tableSessionId, string $displayName, bool $isHost): void
    {
        $_SESSION['session_member_id'] = $sessionMemberId;
        $_SESSION['table_session_id'] = $tableSessionId;
        $_SESSION['session_member_name'] = $displayName;
        $_SESSION['session_member_is_host'] = $isHost;
    }

    public static function clearTableSession(): void
    {
        unset(
            $_SESSION['session_member_id'],
            $_SESSION['table_session_id'],
            $_SESSION['session_member_name'],
            $_SESSION['session_member_is_host']
        );
    }

    public static function sessionMemberId(): ?int
    {
        return $_SESSION['session_member_id'] ?? null;
    }

    public static function tableSessionId(): ?int
    {
        return $_SESSION['table_session_id'] ?? null;
    }

    public static function memberName(): ?string
    {
        return $_SESSION['session_member_name'] ?? null;
    }

    public static function isHost(): bool
    {
        return $_SESSION['session_member_is_host'] ?? false;
    }

    public static function requireTableSession(): void
    {
        if (self::sessionMemberId() === null) {
            redirect('');
        }
    }
}
