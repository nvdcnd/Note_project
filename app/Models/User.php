<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'balance', 'avatar_image_url', 'banner_url', 'theme4_id', 'organizationID', 'provider_id', 'provider_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function notes()
    {
        return $this->hasMany(Note::class, 'creater_id');
    }

    public function sharedNotes()
    {
        return $this->belongsToMany(Note::class, 'pivot_for_note', 'shared_with', 'note_id');
    }

    public function organizations()
    {
        return $this->hasMany(Organization::class, 'hostID');
    }

    public function memberships()
    {
        return $this->hasMany(OrganizationsMember::class, 'userID');
    }

    public function themeWallets()
    {
        return $this->hasMany(Theme4userWallet::class, 'userID');
    }

    /** Chủ đề cá nhân đang được áp dụng (null nếu dùng giao diện mặc định). */
    public function appliedTheme()
    {
        return $this->belongsTo(Theme4user::class, 'theme4_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'balance' => 'decimal:2',
            'password' => 'hashed',
        ];
    }
}
