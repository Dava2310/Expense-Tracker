<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A person who uses Saldo, owner of accounts, currencies and categories.
 */
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithMedia, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The user's initials (up to two letters), shown when there is no photo.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => str($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode(''));
    }

    /**
     * The address of the profile photo, or null when the user has none.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->getFirstMediaUrl('avatar') ?: null);
    }

    /**
     * Get the accounts assigned to the user.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the currencies created from the user.
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    /**
     * Get the categories created by the user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the incomes received into the user's accounts.
     */
    public function incomes(): HasManyThrough
    {
        return $this->hasManyThrough(Income::class, Account::class)->withTrashedParents();
    }

    /**
     * Get the expenses paid from the user's accounts.
     */
    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(Expense::class, Account::class)->withTrashedParents();
    }

    /**
     * The profile photo is a single WebP file: a new photo replaces the old one.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/webp']);
    }

    /**
     * Replaces the profile photo with a centred 256 px WebP square; the upload itself is not kept.
     *
     * @param  UploadedFile  $photo  The image the user uploaded
     */
    public function setProfilePhoto(UploadedFile $photo): void
    {
        $processed = sys_get_temp_dir().'/'.Str::uuid().'.webp';

        Image::useImageDriver(ImageDriver::Gd)
            ->loadFile($photo->getRealPath())
            ->fit(Fit::Crop, 256, 256)
            ->quality(85)
            ->format('webp')
            ->save($processed);

        $this->addMedia($processed)
            ->usingFileName(Str::uuid().'.webp')
            ->usingName('Profile photo')
            ->toMediaCollection('avatar');
    }

    /**
     * Removes the profile photo, so the initials are shown again.
     */
    public function removeProfilePhoto(): void
    {
        $this->clearMediaCollection('avatar');
    }

    /**
     * Creates the starter categories (expenses and incomes) for a newly registered user.
     */
    public function createStarterCategories(): void
    {
        $this->categories()->createMany(config('saldo.starter_categories'));
    }

    /**
     * Creates the starter currencies (dollar and euro) for a newly registered user.
     */
    public function createStarterCurrencies(): void
    {
        $this->currencies()->createMany(config('saldo.starter_currencies'));
    }

    /**
     * Deletes the user and everything they own, all or nothing; the photo is removed after the commit.
     */
    public function deleteWithData(): void
    {
        DB::transaction(function (): void {
            $accountIds = $this->accounts()->withTrashed()->pluck('id');

            Transfer::whereIn('from_account_id', $accountIds)->orWhereIn('to_account_id', $accountIds)->delete();
            Income::whereIn('account_id', $accountIds)->delete();
            Expense::whereIn('account_id', $accountIds)->delete();
            $this->accounts()->withTrashed()->forceDelete();
            $this->categories()->delete();
            $this->currencies()->delete();
            $this->deletePreservingMedia();
        });

        $this->deleteAllMedia();
    }
}
