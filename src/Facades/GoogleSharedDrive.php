<?php

namespace Ikhsant\LaravelGoogleSharedDrive\Facades;

use Ikhsant\LaravelGoogleSharedDrive\Services\GoogleDriveService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array upload(\Illuminate\Http\UploadedFile $file, ?string $folderPath = null, ?string $customFileName = null)
 * @method static string download(string $fileId)
 * @method static bool delete(string $fileId)
 *
 * @see GoogleDriveService
 */
class GoogleSharedDrive extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return GoogleDriveService::class;
    }
}
