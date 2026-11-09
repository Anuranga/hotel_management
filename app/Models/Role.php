<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const ADMIN = 'admin';

    public const MANAGER = 'manager';

    public const STAFF = 'staff';

    public const CASHIER = 'cashier';

    public const KITCHEN = 'kitchen';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'label',
    ];

    /**
     * Users assigned to this role.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
