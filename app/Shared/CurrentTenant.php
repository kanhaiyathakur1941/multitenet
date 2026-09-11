<?php

declare(strict_types=1);

namespace App\Shared;

use App\Models\Tenant;
use App\Shared\Exceptions\TenantContextMissingException;

final class CurrentTenant
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function isSet(): bool
    {
        return $this->tenant !== null;
    }

    public function require(): Tenant
    {
        if ($this->tenant === null) {
            throw new TenantContextMissingException('Current tenant is not set.');
        }

        return $this->tenant;
    }
}
