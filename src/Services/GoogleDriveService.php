<?php

namespace Ikhsant\LaravelGoogleSharedDrive\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class GoogleDriveService
{
    protected ?Drive $drive = null;

    /**
     * Get the Google Drive API client service instance.
     */
    protected function getDrive(): Drive
    {
        if ($this->drive === null) {
            $client = new Client;

            $jsonPath = config('google-shared-drive.service_account_json')
                ?? config('services.google_drive.service_account_json');

            $filePath = file_exists($jsonPath) ? $jsonPath : storage_path('app/'.$jsonPath);
            $client->setAuthConfig($filePath);

            $client->addScope(Drive::DRIVE);

            $this->drive = new Drive($client);
        }

        return $this->drive;
    }

    /**
     * Upload a file to Google Drive.
     */
    public function upload(UploadedFile $file, ?string $folderPath = null, ?string $customFileName = null): array
    {
        $rootFolderId = config('google-shared-drive.root_folder_id')
            ?? config('services.google_drive.root_folder_id');

        if (empty($rootFolderId) || $rootFolderId === 'ISI_FOLDER_ID_ROOT_GOOGLE_DRIVE') {
            throw new \InvalidArgumentException(
                'Google Drive Root Folder ID is not configured. Service accounts do not have storage quota of their own. You must configure GOOGLE_DRIVE_ROOT_FOLDER_ID with a folder or Shared Drive ID that is shared with the service account client email.'
            );
        }

        try {
            $targetFolderId = $this->resolveFolderPath($folderPath, $rootFolderId);

            return $this->uploadToFolder($file, $targetFolderId, $customFileName);
        } catch (\Exception $e) {
            // Self-healing: If upload failed and a folderPath was used, the folder might
            // have been deleted manually on Google Drive. Clear the cache and retry once.
            if ($folderPath) {
                $cleanPath = trim(str_replace('\\', '/', $folderPath), '/');
                $cacheKey = 'gdrive_folder:'.md5(($rootFolderId ?? 'root').':'.$cleanPath);
                Cache::forget($cacheKey);

                $targetFolderId = $this->resolveFolderPath($folderPath, $rootFolderId);

                return $this->uploadToFolder($file, $targetFolderId, $customFileName);
            }

            throw $e;
        }
    }

    /**
     * Upload the file directly to the given target parent folder ID.
     */
    protected function uploadToFolder(UploadedFile $file, ?string $targetFolderId, ?string $customFileName = null): array
    {
        $parents = [];
        if ($targetFolderId && $targetFolderId !== 'ISI_FOLDER_ID_ROOT_GOOGLE_DRIVE') {
            $parents = [$targetFolderId];
        }

        $fileName = $customFileName ?? $file->getClientOriginalName();
        $metadataParams = [
            'name' => $fileName,
        ];

        if (! empty($parents)) {
            $metadataParams['parents'] = $parents;
        }

        $metadata = new DriveFile($metadataParams);

        $uploaded = $this->getDrive()->files->create($metadata, [
            'data' => file_get_contents($file->getRealPath()),
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size',
            'supportsAllDrives' => true,
        ]);

        return [
            'file_id' => $uploaded->id,
            'file_name' => $uploaded->name,
            'mime_type' => $uploaded->mimeType,
            'size' => $uploaded->size ?? $file->getSize(),
        ];
    }

    /**
     * Resolve a nested folder path into a Google Drive folder ID, with caching.
     */
    public function resolveFolderPath(?string $folderPath, ?string $rootFolderId): ?string
    {
        if (empty($folderPath)) {
            return $rootFolderId;
        }

        $cleanPath = trim(str_replace('\\', '/', $folderPath), '/');
        if ($cleanPath === '') {
            return $rootFolderId;
        }

        $cacheKey = 'gdrive_folder:'.md5(($rootFolderId ?? 'root').':'.$cleanPath);

        return Cache::rememberForever($cacheKey, function () use ($cleanPath, $rootFolderId) {
            $segments = explode('/', $cleanPath);
            $currentParentId = $rootFolderId;

            foreach ($segments as $segment) {
                $segment = trim($segment);
                if ($segment === '') {
                    continue;
                }

                $currentParentId = $this->findOrCreateFolder($segment, $currentParentId);
            }

            return $currentParentId;
        });
    }

    /**
     * Find a folder by name and parent ID, or create it if it doesn't exist.
     */
    protected function findOrCreateFolder(string $name, ?string $parentId): string
    {
        $drive = $this->getDrive();

        $query = "name = '".str_replace("'", "\\'", $name)."' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        if ($parentId) {
            $query .= " and '".$parentId."' in parents";
        }

        $response = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
            'pageSize' => 1,
        ]);

        $files = $response->getFiles();

        if (count($files) > 0) {
            return $files[0]->id;
        }

        $folderMetadataParams = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];
        if ($parentId) {
            $folderMetadataParams['parents'] = [$parentId];
        }

        $folderMetadata = new DriveFile($folderMetadataParams);
        $folder = $drive->files->create($folderMetadata, [
            'fields' => 'id',
            'supportsAllDrives' => true,
        ]);

        return $folder->id;
    }

    /**
     * Download / retrieve file content from Google Drive.
     */
    public function download(string $fileId): string
    {
        $response = $this->getDrive()->files->get($fileId, [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);

        return $response->getBody()->getContents();
    }

    /**
     * Delete a file from Google Drive.
     */
    public function delete(string $fileId): bool
    {
        $this->getDrive()->files->delete($fileId, [
            'supportsAllDrives' => true,
        ]);

        return true;
    }
}
