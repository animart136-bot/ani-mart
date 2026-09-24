<?php

namespace App\Services;

final class MessageFormatter
{
    public static function orderReference($text, $orderNumber = null): string
    {
        return (string) preg_replace(
            '/\bOrder(?:\s*ID\s*:|\s*:?\s*#)\s*(\d+)/i',
            'Order ID: ' . ($orderNumber ?: '$1'),
            (string) $text
        );
    }

    public static function conversationSubject(object $conversation): string
    {
        if (!empty($conversation->product_name)) {
            return 'Product: ' . $conversation->product_name;
        }

        if (!empty($conversation->order_id)) {
            return 'Order ID: ' . ($conversation->order_number ?: $conversation->order_id);
        }

        return 'General conversation';
    }
}
