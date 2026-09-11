<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Shared\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToTenant
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isSuperAdmin()) {
            return $query->withoutGlobalScopes([TenantScope::class]);
        }

        return $query;
    }
}
