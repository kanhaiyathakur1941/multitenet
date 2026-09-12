<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Customer')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('subtotal')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('tax')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('total')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required(),
                TextInput::make('payment_gateway')
                    ->label('Payment gateway')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('razorpay_order_id')
                    ->label('Razorpay order ID')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('payment_transaction_id')
                    ->label('Payment transaction ID')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
