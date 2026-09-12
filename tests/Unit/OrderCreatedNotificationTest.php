<?php

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);
use App\Models\User;
use App\Modules\Notifications\OrderCreatedNotification;
use Illuminate\Notifications\Messages\MailMessage;

it('includes mail channel when mail notifications are enabled', function () {
    config(['eventflow.notifications.mail_enabled' => true]);

    $order = Order::factory()->create();
    $user = User::factory()->create();
    $notification = new OrderCreatedNotification($order);

    expect($notification->via($user))->toContain('mail');
});

it('excludes mail channel when mail notifications are disabled', function () {
    config(['eventflow.notifications.mail_enabled' => false]);

    $order = Order::factory()->create();
    $user = User::factory()->create();
    $notification = new OrderCreatedNotification($order);

    expect($notification->via($user))->not->toContain('mail');
});

it('builds an order confirmation mail message', function () {
    $order = Order::factory()->create([
        'payment_transaction_id' => 'order_test_123',
    ]);
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $notification = new OrderCreatedNotification($order);

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe('Order confirmed — EventFlow');
});
