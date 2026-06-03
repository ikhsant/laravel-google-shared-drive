<?php

namespace Ikhsant\LaravelGoogleSharedDrive\Helpers;

use Ikhsant\LaravelGoogleSharedDrive\Facades\GoogleSharedDrive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class PendingMediaUpload
{
    /**
     * Create a new pending media upload instance.
     */
    public function __construct(
        protected Model $model,
        protected UploadedFile|string $file
    ) {}

    /**
     * Upload the file to Google Shared Drive and save the media record in the collection.
     */
    public function toMediaCollection(string $collectionName = 'default'): Model
    {
        $uploaded = GoogleSharedDrive::upload($this->file);

        return $this->model->media()->create([
            'collection_name' => $collectionName,
            'file_name' => $uploaded['file_name'],
            'mime_type' => $uploaded['mime_type'],
            'size' => $uploaded['size'],
            'disk' => 'google_drive',
            'google_drive_file_id' => $uploaded['file_id'],
            'uploaded_by' => auth()->id(),
        ]);
    }
}
