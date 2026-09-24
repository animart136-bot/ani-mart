<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Models\Conversation;
use App\Models\Message;

$db = Database::connection();
$users = $db->query(
    "SELECT id, role, TRIM(CONCAT(first_name, ' ', last_name)) AS name
     FROM users
     WHERE COALESCE(account_status, 'active') = 'active'
     ORDER BY id
     LIMIT 2"
)->fetchAll();

if (count($users) < 2) {
    echo "Account-to-account messaging check skipped: two active users are required.\n";
    exit(0);
}

$first = $users[0];
$second = $users[1];
$model = new Conversation();
$messageModel = new Message();

$db->beginTransaction();
try {
    $conversationId = (int) $model->create([
        'customer_id' => (int) $first->id,
        'farmer_id' => (int) $second->id,
        'last_message' => 'Account-to-account messaging test',
        'last_message_at' => date('Y-m-d H:i:s'),
    ]);

    $reverseLookup = $model->findExisting((int) $second->id, (int) $first->id);
    if ((int) ($reverseLookup->id ?? 0) !== $conversationId) {
        throw new RuntimeException('Conversation lookup is not symmetric between accounts.');
    }

    // Empty conversation records are intentionally hidden until one participant
    // sends the first message; only then has the conversation actually started.
    $messageModel->create([
        'conversation_id' => $conversationId,
        'sender_id' => (int) $first->id,
        'message' => 'Account-to-account messaging test',
        'is_read' => 0,
    ]);

    foreach ([[$first, $second], [$second, $first]] as [$viewer, $partner]) {
        $conversations = $model->listForUser((int) $viewer->id, (string) $viewer->role);
        $match = null;
        foreach ($conversations as $conversation) {
            if ((int) $conversation->id === $conversationId) {
                $match = $conversation;
                break;
            }
        }

        if (!$match) {
            throw new RuntimeException('A participant cannot see the direct conversation.');
        }
        if ((string) $match->partner_name !== (string) $partner->name) {
            throw new RuntimeException('The inbox resolved the wrong conversation partner.');
        }
        if ((string) $match->partner_role !== (string) $partner->role) {
            throw new RuntimeException('The inbox resolved the wrong partner role.');
        }
    }

    $settings = $db->prepare(
        'INSERT INTO conversation_settings (conversation_id) VALUES (?)'
    );
    $settings->execute([$conversationId]);
    $request = $db->prepare(
        "INSERT INTO message_requests (conversation_id, sender_id, recipient_id, message, status)
         VALUES (?, ?, ?, ?, 'pending')"
    );
    $request->execute([$conversationId, (int) $first->id, (int) $second->id, 'Delete this request']);

    if ($model->deleteForParticipant($conversationId, -1)) {
        throw new RuntimeException('A non-participant deleted the conversation.');
    }
    if (!$model->findById($conversationId)) {
        throw new RuntimeException('An unauthorized delete removed the conversation.');
    }
    if (!$model->deleteForParticipant($conversationId, (int) $first->id)) {
        throw new RuntimeException('A participant could not delete the entire conversation.');
    }
    if ($model->findById($conversationId)) {
        throw new RuntimeException('The deleted conversation still exists in the database.');
    }
    foreach (['messages', 'conversation_settings', 'message_requests'] as $dependentTable) {
        $dependent = $db->prepare("SELECT COUNT(*) FROM {$dependentTable} WHERE conversation_id = ?");
        $dependent->execute([$conversationId]);
        if ((int) $dependent->fetchColumn() !== 0) {
            throw new RuntimeException("Conversation deletion left records in {$dependentTable}.");
        }
    }

    echo "Account-to-account messaging checks passed.\n";
} finally {
    $db->rollBack();
}
