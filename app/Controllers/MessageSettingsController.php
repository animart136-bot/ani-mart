<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\MessageSetting;

class MessageSettingsController extends Controller
{
    private $messageSetting;

    public function __construct()
    {
        $this->messageSetting = new MessageSetting();
    }

    public function index()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $settings = $this->messageSetting->getForUser($user['id']);

        if (!$settings) {
            $settings = $this->messageSetting->createDefaultForUser($user['id']);
        }

        $this->view('messages/settings', [
            'title' => 'Messaging Settings',
            'settings' => $settings,
            'bodyClass' => 'message-settings-page'
        ]);
    }

    public function save()
    {
        $user = Session::get('user');

        if (!$user) {
            return $this->jsonResponse(false, 'Login required.');
        }

        csrf_verify();

        $theme = $_POST['theme'] ?? 'default';

        $validThemes = ['default', 'light', 'dark'];

        if (!in_array($theme, $validThemes, true)) {
            return $this->jsonResponse(false, 'Invalid theme selection.');
        }

        $payload = [
            'allow_messages_from' => 'everyone',
            'message_notifications' => isset($_POST['message_notifications']) ? 1 : 0,
            'message_request_notifications' => 0,
            'group_notifications' => isset($_POST['group_notifications']) ? 1 : 0,
            'community_notifications' => isset($_POST['community_notifications']) ? 1 : 0,
            'announcement_notifications' => isset($_POST['announcement_notifications']) ? 1 : 0,
            'order_chat_notifications' => isset($_POST['order_chat_notifications']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'desktop_notifications' => isset($_POST['desktop_notifications']) ? 1 : 0,
            'read_receipts' => isset($_POST['read_receipts']) ? 1 : 0,
            'typing_indicator' => isset($_POST['typing_indicator']) ? 1 : 0,
            'online_status' => isset($_POST['online_status']) ? 1 : 0,
            'auto_download_images' => isset($_POST['auto_download_images']) ? 1 : 0,
            'message_requests' => 0,
            'blocked_users_enabled' => 0,
            'theme' => $theme,
        ];

        $this->messageSetting->saveForUser($user['id'], $payload);

        return $this->jsonResponse(true, 'Messaging preferences saved successfully.');
    }

    private function jsonResponse($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $data));
        exit;
    }
}
