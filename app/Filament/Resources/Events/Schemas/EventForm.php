<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Filament\Support\TenantFormFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantFormFields::tenantSelect(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('start_date')
                    ->required(),
                DateTimePicker::make('end_date')
                    ->required()
                    ->afterOrEqual('start_date'),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Select::make('status')
                    ->options(EventStatus::class)
                    ->default(EventStatus::Draft)
                    ->required(),
            ]);
    }
}
