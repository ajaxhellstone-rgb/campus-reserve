<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\UserNameSearchIndex;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            app(UserNameSearchIndex::class)->sync($user);
        });

        static::updated(function (User $user): void {
            if ($user->wasChanged('name')) {
                app(UserNameSearchIndex::class)->sync($user);
            }
        });
    }

    /**
     * The attributes that can be assigned directly when creating or updating a user.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_picture',
        'two_fa_enabled',
        'two_fa_secret',
        'remember_device_token',
        'remember_device_expires_at',
        'is_admin',
        'is_super_admin',
        'two_fa_secret',
        'email_notifications',
    ];

    protected $attributes = [
        'email_notifications' => false,
    ];

    /**
     * These fields are hidden when returning user data as JSON.
     * Passwords, tokens, and sensitive admin flags should not be exposed.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
        'remember_device_token',
        'is_admin',
        'is_super_admin',
    ];

    /**
     * Define field types for casts (dates, booleans, etc.).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'remember_device_expires_at' => 'datetime',
        'two_fa_enabled' => 'boolean',
        'is_admin' => 'boolean',
        'is_super_admin' => 'boolean',
        'email_notifications' => 'boolean',
    ];

    /**
     * Link to the user's saved credentials.
     */
    public function savedCredentials()
    {
        return $this->hasMany(SavedCredential::class);
    }

    /**
     * Link to the user's OTP records.
     */
    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function storedProfilePicture(): HasOne
    {
        return $this->hasOne(UserProfilePicture::class);
    }

    public function profilePictureUrl(): string
    {
        $picture = $this->storedProfilePicture;

        if ($picture) {
            return route('profile.picture.show', [
                'user' => $this->id,
                'v' => $picture->updated_at->timestamp,
            ]);
        }

        return $this->profile_picture
            ? asset('storage/'.$this->profile_picture)
            : asset('images/guest-avatar.svg');
    }

    /**
     * Encrypts the user name when saving to prevent direct database access.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = Crypt::encryptString($value);
    }

    /**
     * Decrypts the user name when retrieving from database.
     */
    public function getNameAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return unchanged if decryption fails
        }
    }

    /**
     * Encrypts and hashes email on save.
     * Hash is used for lookups while keeping the actual email encrypted.
     */
    public function setEmailAttribute($value)
    {
        // Save the encrypted email in the database
        // Also save a hashed version for lookups without decrypting
        $this->attributes['email'] = Crypt::encryptString($value);
        $this->attributes['email_hash'] = hash('sha256', strtolower($value));
    }

    /**
     * Decrypts email with error handling.
     */
    public function getEmailAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Return unchanged if decryption fails
        }
    }

    /**
     * Encrypts phone number for privacy. Allows null values.
     */
    public function setPhoneAttribute($value)
    {
        // Save encrypted if provided, otherwise null
        $this->attributes['phone'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypts phone number if it exists.
     */
    public function getPhoneAttribute($value)
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Safely encrypt the 2FA secret when it is set.
     */
    public function setTwoFaSecretAttribute($value)
    {
        $this->attributes['two_fa_secret'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
    }

    /**
     * Safely decrypt the 2FA secret. If it was encrypted with an older app key,
     * treat it as absent instead of crashing the login flow.
     */
    public function getTwoFaSecretAttribute($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->getAttribute('is_admin') === true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->getAttribute('is_super_admin') === true;
    }
}
