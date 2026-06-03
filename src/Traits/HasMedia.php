<?php

namespace Ikhsant\LaravelGoogleSharedDrive\Traits;

use Ikhsant\LaravelGoogleSharedDrive\Helpers\PendingMediaUpload;
use Ikhsant\LaravelGoogleSharedDrive\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

trait HasMedia
{
    /**
     * Get all of the model's media records.
     */
    public function media(): MorphMany
    {
        $mediaModel = config('google-shared-drive.media_model') ?? Media::class;

        return $this->morphMany($mediaModel, 'model');
    }

    /**
     * Get media records matching the specified collection name.
     */
    public function mediaCollection(string $collection): MorphMany
    {
        return $this->media()->where('collection_name', $collection);
    }

    /**
     * Add a file to the media library.
     */
    public function addMedia(UploadedFile|string $file): PendingMediaUpload
    {
        return new PendingMediaUpload($this, $file);
    }
}
