<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Models\Appeal;
use App\Models\Complaint;
use App\Models\ComplaintAction;
use App\Models\ComplaintReply;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Models\ReportAction;
use App\Models\SupportMessage;
use App\Models\SupportNote;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SensitiveDataCipher;

$db = Database::connection();
$userIds = $db->query('SELECT id FROM users ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
if (count($userIds) < 2) {
    echo "Sensitive write-path integration check skipped: two users are required.\n";
    exit(0);
}

$cipher = new SensitiveDataCipher();
$assertEncrypted = static function (string $table, string $column, int $id) use ($db, $cipher): void {
    $statement = $db->prepare("SELECT `{$column}` FROM `{$table}` WHERE id = ?");
    $statement->execute([$id]);
    $value = $statement->fetchColumn();
    if (!$cipher->isEncrypted(is_string($value) ? $value : null)) {
        throw new RuntimeException("{$table}.{$column} was not encrypted by its write path.");
    }
};

$db->beginTransaction();
try {
    $testEmail = 'encryption-write-' . bin2hex(random_bytes(6)) . '@example.test';
    (new User())->create([
        'first_name' => 'Encryption',
        'last_name' => 'Test',
        'email' => $testEmail,
        'password' => password_hash('TestPassword123', PASSWORD_DEFAULT),
        'role' => 'customer',
    ]);
    $testUserId = (int) $db->lastInsertId();
    $assertEncrypted('users', 'first_name_encrypted', $testUserId);
    $assertEncrypted('users', 'last_name_encrypted', $testUserId);
    $assertEncrypted('users', 'email_encrypted', $testUserId);

    $conversationId = (int) (new Conversation())->create([
        'customer_id' => (int) $userIds[0],
        'farmer_id' => (int) $userIds[1],
        'last_message' => 'Encryption write-path test',
        'last_message_at' => date('Y-m-d H:i:s'),
    ]);
    $assertEncrypted('conversations', 'last_message_encrypted', $conversationId);

    $messageModel = new Message();
    $messageId = (int) $messageModel->create([
        'conversation_id' => $conversationId,
        'sender_id' => (int) $userIds[0],
        'message' => 'Private test message',
        'image' => '/private/test-image.webp',
    ]);
    $assertEncrypted('messages', 'message_encrypted', $messageId);
    $assertEncrypted('messages', 'image_encrypted', $messageId);
    $messageModel->update($messageId, (int) $userIds[0], 'Edited private test message');
    if (($messageModel->findById($messageId)->message ?? null) !== 'Edited private test message') {
        throw new RuntimeException('Encrypted message did not round-trip through the model.');
    }

    $reportId = (int) (new Report())->create([
        'reporter_id' => (int) $userIds[0],
        'target_type' => 'user',
        'target_id' => (int) $userIds[1],
        'reason' => 'Other',
        'description' => 'Confidential report integration test',
    ]);
    $assertEncrypted('reports', 'description_encrypted', $reportId);
    (new ReportAction())->create($reportId, (int) $userIds[0], 'assign', 'Confidential action note');
    $actionId = (int) $db->lastInsertId();
    $assertEncrypted('report_actions', 'notes_encrypted', $actionId);

    $appealId = (new Appeal())->create([
        'report_id' => $reportId,
        'user_id' => (int) $userIds[1],
        'reason' => 'Confidential appeal reason',
    ]);
    $assertEncrypted('appeals', 'reason_encrypted', $appealId);

    $complaintSource = $db->query("SELECT o.id AS order_id, o.user_id AS customer_id, oi.seller_profile_id
        FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id
        WHERE oi.seller_profile_id IS NOT NULL LIMIT 1")->fetch();
    if ($complaintSource) {
        (new Complaint())->create([
            'order_id' => (int) $complaintSource->order_id,
            'customer_id' => (int) $complaintSource->customer_id,
            'seller_profile_id' => (int) $complaintSource->seller_profile_id,
            'category' => 'Other',
            'description' => 'Confidential complaint integration test',
        ]);
        $complaintId = (int) $db->lastInsertId();
        $assertEncrypted('complaints', 'description_encrypted', $complaintId);
        (new ComplaintReply())->create($complaintId, (int) $userIds[0], 'Confidential complaint reply');
        $assertEncrypted('complaint_replies', 'message_encrypted', (int) $db->lastInsertId());
        (new ComplaintAction())->create($complaintId, (int) $userIds[0], 'request_more_evidence', 'Confidential complaint action');
        $assertEncrypted('complaint_actions', 'notes_encrypted', (int) $db->lastInsertId());
    }

    $subject = 'Confidential support subject';
    $statement = $db->prepare("INSERT INTO support_conversations
        (conversation_type, subject, subject_encrypted, customer_id, priority, status, created_by)
        VALUES ('customer_support', ?, ?, ?, 'medium', 'open', ?)");
    $statement->execute([$subject, $cipher->encrypt($subject, 'support_conversations.subject'), $userIds[0], $userIds[0]]);
    $supportConversationId = (int) $db->lastInsertId();
    $supportMessageId = (int) (new SupportMessage())->create([
        'conversation_id' => $supportConversationId,
        'sender_id' => (int) $userIds[0],
        'sender_role' => 'customer',
        'message_type' => 'text',
        'message' => 'Confidential support message',
        'attachment_url' => '/private/support-file.webp',
    ]);
    $assertEncrypted('support_messages', 'message_encrypted', $supportMessageId);
    $assertEncrypted('support_messages', 'attachment_url_encrypted', $supportMessageId);
    $supportNoteId = (int) (new SupportNote())->create([
        'conversation_id' => $supportConversationId,
        'admin_id' => (int) $userIds[0],
        'note' => 'Confidential support note',
    ]);
    $assertEncrypted('support_notes', 'note_encrypted', $supportNoteId);

    AuditLogService::log(
        'ENCRYPTION_WRITE_PATH_TEST',
        'user',
        (int) $userIds[1],
        ['description' => 'Confidential audit description'],
        (int) $userIds[0]
    );
    $auditId = (int) $db->lastInsertId();
    $assertEncrypted('audit_logs', 'description_encrypted', $auditId);
    $assertEncrypted('audit_logs', 'ip_address_encrypted', $auditId);

    echo "Sensitive write-path integration checks passed.\n";
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}
