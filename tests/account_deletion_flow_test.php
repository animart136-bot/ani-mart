<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Services\AccountDeletionService;

$db = Database::connection();
$service = new AccountDeletionService();
$password = 'DeleteFlow123';
$suffix = bin2hex(random_bytes(6));
$ids = [];

$fail = static function (string $message): void {
    fwrite(STDERR, "Account deletion flow failed: {$message}\n");
    exit(1);
};

try {
    $insert = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role, account_status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $insert->execute(['Flow', 'Customer', "deletion-customer-{$suffix}@ani-mart.invalid", password_hash($password, PASSWORD_DEFAULT), 'customer']);
    $customerId = (int) $db->lastInsertId();
    $ids[] = $customerId;

    $scheduledFor = $service->schedule($customerId, $password);
    $days = (strtotime($scheduledFor) - time()) / 86400;
    if ($days < 29.9 || $days > 30.1) $fail('scheduled deadline is not 30 days away');
    if ($service->handleSuccessfulLogin($customerId) !== 'cancelled') $fail('login did not cancel pending deletion');

    $pending = $db->prepare('SELECT deletion_scheduled_for FROM users WHERE id = ?');
    $pending->execute([$customerId]);
    if ($pending->fetchColumn() !== null) $fail('cancelled deadline was not cleared');

    $service->schedule($customerId, $password);
    $db->prepare('UPDATE users SET deletion_scheduled_for = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = ?')->execute([$customerId]);
    if ($service->handleSuccessfulLogin($customerId) !== 'deleted') $fail('expired request did not complete deletion');

    $state = $db->prepare('SELECT first_name, email, account_status, deleted_at FROM users WHERE id = ?');
    $state->execute([$customerId]);
    $deleted = $state->fetch();
    if (!$deleted || $deleted->account_status !== 'deleted' || $deleted->first_name !== 'Deleted' || empty($deleted->deleted_at)) $fail('deleted account was not disabled and anonymized');

    $insert->execute(['Flow', 'Admin', "deletion-admin-{$suffix}@ani-mart.invalid", password_hash($password, PASSWORD_DEFAULT), 'admin']);
    $adminId = (int) $db->lastInsertId();
    $ids[] = $adminId;
    try {
        $service->schedule($adminId, $password);
        $fail('administrator deletion was accepted');
    } catch (InvalidArgumentException $expected) {
        // Expected: administrators are deliberately excluded.
    }

    echo "Account deletion schedule, cancellation, expiry, and admin exclusion checks passed.\n";
} finally {
    if ($ids !== []) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM users WHERE id IN ({$placeholders})")->execute($ids);
    }
}
