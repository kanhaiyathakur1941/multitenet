<?php

use App\Models\Order;
use App\Models\User;
use App\Modules\Notifications\Channels\SafeMailNotificationChannel;
use App\Modules\Notifications\OrderCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('includes the safe mail channel when mail notifications are enabled', function () {
    config(['eventflow.notifications.mail_enabled' => true]);

    $order = Order::factory()->create();
    $user = User::factory()->create();
    $notification = new OrderCreatedNotification($order->id);

    expect($notification->via($user))->toContain(SafeMailNotificationChannel::class);
});

it('excludes mail channel when mail notifications are disabled', function () {
    config(['eventflow.notifications.mail_enabled' => false]);

    $order = Order::factory()->create();
    $user = User::factory()->create();
    $notification = new OrderCreatedNotification($order->id);

    expect($notification->via($user))->not->toContain(SafeMailNotificationChannel::class);
});

it('builds an order confirmation mail message', function () {
    $order = Order::factory()->create([
        'payment_transaction_id' => 'order_test_123',
    ]);
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $notification = new OrderCreatedNotification($order->id);

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('Order confirmed — EventFlow');
});

it('loads the order without tenant context for queued workers', function () {
    $order = Order::factory()->create(['total' => 42.00]);
    $user = User::factory()->create();
    $notification = new OrderCreatedNotification($order->id);

    $payload = $notification->toArray($user);

    expect($payload['order_id'])->toBe($order->id)
        ->and($payload['total'])->toBe('42.00');
});
