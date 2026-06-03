# Laravel Google Shared Drive

An easy-to-use Laravel package to integrate Google Drive and Shared Drive APIs with minimal setup.

## Features

- Simple upload, download, and delete operations on Google Shared Drive.
- Multi-drive support.
- Clean Laravel Facade integration to minimize boilerplate code.
- Auto-discovery support.

## Installation

Add the package to your Laravel project's `composer.json` repositories block (if installing locally):

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/ikhsant/laravel-google-shared-drive",
        "options": {
            "symlink": true
        }
    }
],
```

Then run:

```bash
composer require ikhsant/laravel-google-shared-drive
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=google-shared-drive-config
```

This will create a `config/google-shared-drive.php` file:

```php
return [
    'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON', 'google/service-account.json'),
    'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),
];
```

Ensure you have your Service Account JSON placed under `storage/app/` (e.g., `storage/app/google/service-account.json`).

And configure your `.env`:

```env
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON=google/service-account.json
GOOGLE_DRIVE_ROOT_FOLDER_ID=your-root-folder-or-shared-drive-id
```

## Usage

Use the `GoogleSharedDrive` facade for easy integration.

### Upload a File

```php
use GoogleSharedDrive; // Or Ikhsant\LaravelGoogleSharedDrive\Facades\GoogleSharedDrive;

$result = GoogleSharedDrive::upload($request->file('file'));

// Response:
// [
//     'file_id' => '...',
//     'file_name' => '...',
//     'mime_type' => '...',
//     'size' => 12345
// ]
```

### Download / Retrieve File Contents

```php
use GoogleSharedDrive;

$contents = GoogleSharedDrive::download($fileId);

return response($contents)
    ->header('Content-Type', $mimeType);
```

### Delete a File

```php
use GoogleSharedDrive;

GoogleSharedDrive::delete($fileId);
```
