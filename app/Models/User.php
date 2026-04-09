<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password'])]
class User extends Authenticatable
{
    /**
     * @use HasFactory<UserFactory>
     */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_TEACHER = 'guru';

    public const ROLE_STUDENT = 'murid';

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'username',
        'name',
        'id_role',
        'role',
        'password',
    ];

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function redirectPath(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => route('admin.dashboard'),
            self::ROLE_TEACHER => route('teacher.dashboard'),
            self::ROLE_STUDENT => route('student.dashboard'),
            default => route('login'),
        };
    }

    public function hasRole(string ...$roles): bool
    {
        $currentRole = self::normalizeRoleName($this->role);
        $normalizedRoles = array_map([self::class, 'normalizeRoleName'], $roles);

        return in_array($currentRole, $normalizedRoles, true);
    }

    public function scopeRoleNamed(Builder $query, string $role): Builder
    {
        return $query->where('id_role', Role::idForName(self::normalizeRoleName($role)));
    }

    public function getNameAttribute(): string
    {
        return (string) $this->attributes['nama'];
    }

    public function getIdAttribute(): int
    {
        return (int) $this->attributes['id_user'];
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getRoleAttribute(): ?string
    {
        if ($this->relationLoaded('roleRelation')) {
            return self::normalizeRoleName($this->roleRelation?->nama);
        }

        if (! array_key_exists('id_role', $this->attributes) || $this->attributes['id_role'] === null) {
            return null;
        }

        return self::normalizeRoleName(Role::nameForId((int) $this->attributes['id_role']));
    }

    public function setRoleAttribute(string $value): void
    {
        $this->attributes['id_role'] = Role::idForName(self::normalizeRoleName($value));
    }

    public function getUsernameAttribute(): ?string
    {
        return null;
    }

    public function setUsernameAttribute(mixed $value): void
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id_user';
    }

    public static function normalizeRoleName(?string $role): ?string
    {
        return match ($role) {
            'teacher', self::ROLE_TEACHER => self::ROLE_TEACHER,
            'student', self::ROLE_STUDENT => self::ROLE_STUDENT,
            self::ROLE_SUPER_ADMIN => self::ROLE_SUPER_ADMIN,
            default => $role,
        };
    }

    public static function roleMatches(?string $actualRole, string $expectedRole): bool
    {
        return self::normalizeRoleName($actualRole) === self::normalizeRoleName($expectedRole);
    }
}
