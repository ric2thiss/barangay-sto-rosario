<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
 protected $fillable = [
    'name',
    'username',
    'email',
    'password',
    'role_id',
    'status',
    'is_resident',  // add
    'is_of_age',    // add
];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_resident'  => 'boolean',  // add
            'is_of_age'    => 'boolean',  // add
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function hasRole(string ...$roles): bool
{
    return in_array($this->role?->role_name, $roles);
}

public function isAdmin(): bool { return $this->hasRole('Admin'); }
public function isSecretary(): bool { return $this->hasRole('Secretary'); }
public function isResident(): bool { return $this->hasRole('Resident'); }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function blotterRecords()
    {
        return $this->hasMany(BlotterRecord::class, 'recorded_by', 'user_id');
    }

    public function deathRecords()
    {
        return $this->hasMany(DeathRecord::class, 'recorded_by', 'user_id');
    }

    public function issuedCertificates()
    {
        return $this->hasMany(CertificateIssuance::class, 'issued_by', 'user_id');
    }

    public function printLogs()
    {
        return $this->hasMany(PrintLog::class, 'printed_by', 'user_id');
    }
    
    

    public function initials()
    {
        $names = explode(' ', $this->name);
        $initials = '';
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return substr($initials, 0, 2);
    }
}
