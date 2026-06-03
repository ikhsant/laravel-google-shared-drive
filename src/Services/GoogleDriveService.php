<?php

namespace Ikhsant\LaravelGoogleSharedDrive\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;

class GoogleDriveService
{
    protected ?Drive $drive = null;

    protected function getDrive(): Drive
    {
        if ($this->drive === null) {
            $client = new Client;

            $jsonPath = config('google-shared-drive.service_account_json')
                ?? config('services.google_drive.service_account_json');

            $client->setAuthConfig(
                storage_path('app/'.$jsonPath)
            );

            $client->addScope(Drive::DRIVE);

            $this->drive = new Drive($client);
        }

        return $this->drive;
    }

    public function upload(UploadedFile $file): array
    {
        $parents = [];
        $rootFolderId = config('google-shared-drive.root_folder_id')
            ?? config('services.google_drive.root_folder_id');

        if ($rootFolderId && $rootFolderId !== 'ISI_FOLDER_ID_ROOT_GOOGLE_DRIVE') {
            $parents = [$rootFolderId];
        }

        $metadataParams = [
            'name' => $file->getClientOriginalName(),
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

    public function download(string $fileId): string
    {
        $response = $this->getDrive()->files->get($fileId, [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);

        return $response->getBody()->getContents();
    }

    public function delete(string $fileId): bool
    {
        $this->getDrive()->files->delete($fileId, [
            'supportsAllDrives' => true,
        ]);

        return true;
    }
}
