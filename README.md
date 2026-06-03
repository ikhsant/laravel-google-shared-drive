# Laravel Google Shared Drive

An easy-to-use Laravel package to integrate Google Drive and Shared Drive APIs with minimal setup, featuring a Spatie-inspired Media Library helper for Eloquent models.

## Features

- Simple upload, download, and delete operations on Google Shared Drive.
- Spatie-inspired fluent media attachments (`$model->addMedia($file)->toMediaCollection()`).
- Automatic Google Drive file deletion via Eloquent model event listeners.
- Custom media model configuration.
- Clean Laravel Facade integration to minimize boilerplate code.
- Auto-discovery support.

## Installation

Install the package via Composer:

```bash
composer require ikhsant/laravel-google-shared-drive
```

### Run Migrations

The package includes a migration for the `media` table. Run your migrations to create it:

```bash
php artisan migrate
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=google-shared-drive-config
```

This will create a `config/google-shared-drive.php` file:

```php
use Ikhsant\LaravelGoogleSharedDrive\Models\Media;

return [
    'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON', 'google/service-account.json'),
    'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),

    /*
     * The model that should be used for storing media records.
     */
    'media_model' => Media::class,
];
```

Ensure you have your Service Account JSON placed under `storage/app/` (e.g., `storage/app/google/service-account.json`) and configure your `.env`:

```env
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON=google/service-account.json
GOOGLE_DRIVE_ROOT_FOLDER_ID=your-root-folder-or-shared-drive-id
```

---

## Media Library Usage

### 1. Prepare Your Model

Add the `HasMedia` trait to your Eloquent model:

```php
use Ikhsant\LaravelGoogleSharedDrive\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasMedia;
}
```

### 2. Upload / Associate Media Fluently

You can upload a file and associate it with the model using the Spatie-inspired fluent API:

```php
// Upload a file and attach to a specific collection (defaults to 'default')
$media = $consultation->addMedia($request->file('file'))
    ->toMediaCollection('attachments');
```

### 3. Retrieve / Download Media Contents

Use the relation to get media and retrieve file contents directly:

```php
$media = $consultation->media()->first();

// Get the raw file content from Google Drive
$contents = $media->contents();

return response($contents)
    ->header('Content-Type', $media->mime_type)
    ->header('Content-Disposition', 'attachment; filename="'.$media->file_name.'"');
```

### 4. Delete Media

Deleting a media model automatically triggers the model event that deletes the corresponding file from Google Shared Drive:

```php
$media->delete(); // This automatically calls GoogleSharedDrive::delete()
```

---

## Low-Level API Usage (Facade)

For direct interactions with Google Shared Drive without database models, use the `GoogleSharedDrive` facade.

### Upload a File

```php
use Ikhsant\LaravelGoogleSharedDrive\Facades\GoogleSharedDrive;

$result = GoogleSharedDrive::upload($request->file('file'));

// Response:
// [
//     'file_id' => '...',
//     'file_name' => '...',
//     'mime_type' => '...',
//     'size' => 12345
// ]
```

### Download File Contents

```php
use Ikhsant\LaravelGoogleSharedDrive\Facades\GoogleSharedDrive;

$contents = GoogleSharedDrive::download($fileId);
```

### Delete a File

```php
use Ikhsant\LaravelGoogleSharedDrive\Facades\GoogleSharedDrive;

GoogleSharedDrive::delete($fileId);
```

