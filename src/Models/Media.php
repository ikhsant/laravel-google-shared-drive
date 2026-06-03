<?php

namespace Ikhsant\LaravelGoogleSharedDrive\Models;

use Ikhsant\LaravelGoogleSharedDrive\Facades\GoogleSharedDrive;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'collection_name',
    'file_name',
    'mime_type',
    'size',
    'disk',
    'google_drive_file_id',
    'uploaded_by',
])]
class Media extends Model
{
    /**
     * Get the parent model that owns the media.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the media.
     */
    public function uploader(): BelongsTo
    {
        $userClass = config('auth.providers.users.model') ?? 'App\\Models\\User';

        return $this->belongsTo($userClass, 'uploaded_by');
    }

    /**
     * Get the contents of the file from Google Drive.
     */
    public function contents(): string
    {
        return GoogleSharedDrive::download($this->google_drive_file_id);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $media) {
            if ($media->google_drive_file_id) {
                GoogleSharedDrive::delete($media->google_drive_file_id);
            }
        });
    }
}
