<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\Select;

final class TenantFormFields
{
    public static function tenantSelect(): Select
    {
        return Select::make('tenant_id')
            ->relationship('tenant', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false);
    }
}
