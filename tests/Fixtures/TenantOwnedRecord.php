<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

final class TenantOwnedRecord extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_owned_records';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
    ];
}
