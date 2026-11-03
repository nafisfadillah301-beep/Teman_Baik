<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $vapid = config('services.vapid');

        if (empty($vapid['public_key']) || empty($vapid['private_key'])) {
            return;
        }

        $langganan = $notifiable->pushSubscriptions()->get();

        if ($langganan->isEmpty()) {
            return;
        }

        try {
            $payload = json_encode($notification->toWebPush($notifiable));

            $webPush = new WebPush(['VAPID' => [
                'subject' => $vapid['subject'],
                'publicKey' => $vapid['public_key'],
                'privateKey' => $vapid['private_key'],
            ]]);

            foreach ($langganan as $l) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $l->endpoint,
                        'keys' => ['p256dh' => $l->p256dh_key, 'auth' => $l->auth_key],
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    $notifiable->pushSubscriptions()
                        ->where('endpoint', $report->getRequest()->getUri()->__toString())
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}