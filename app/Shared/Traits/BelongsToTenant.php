<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Models\Tenant;
use App\Shared\CurrentTenant;
use App\Shared\Exceptions\TenantContextMissingException;
use App\Shared\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $tenantId = app(CurrentTenant::class)->id();

            if ($tenantId === null) {
                throw new TenantContextMissingException('Cannot create a tenant-owned record without a tenant.');
            }

            $model->setAttribute('tenant_id', $tenantId);
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
