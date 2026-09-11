<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Filament\Support\TenantFormFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantFormFields::tenantSelect(),
                Select::make('role_id')
                    ->relationship(
                        'role',
                        'name',
                        fn (Builder $query): Builder => auth()->user()?->isSuperAdmin()
                            ? $query
                            : $query->whereIn('slug', [
                                UserRole::Manager->value,
                                UserRole::Customer->value,
                            ]),
                    )
                    ->required()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
            ]);
    }
}
