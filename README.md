# Laravel Google Shared Drive

An easy-to-use Laravel package to integrate Google Drive and Shared Drive APIs with minimal setup, featuring a Spatie-inspired Media Library helper for Eloquent models.

## Features

- Simple upload, download, and delete operations on Google Shared Drive.
- Spatie-inspired fluent media attachments (`$model->addMedia($file)->toFolder('custom/path')->toMediaCollection()`).
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

    /*
     * The default disk name stored in the media table.
     */
    'disk' => 'google_drive',
];
```

Ensure you have your Service Account JSON placed under `storage/app/` (e.g., `storage/app/google/service-account.json`) and configure your `.env`:

```env
GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON=google/service-account.json
GOOGLE_DRIVE_ROOT_FOLDER_ID=your-root-folder-or-shared-drive-id
```

## Google Cloud & Shared Drive Setup Guide

To use this package, you need a Google Service Account credentials file (`service-account.json`) and a shared folder or Shared Drive.

### 1. How to obtain `service-account.json`
1. Go to the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project or select an existing one.
3. Enable the **Google Drive API**:
   - Navigate to **APIs & Services > Library**.
   - Search for "Google Drive API" and click **Enable**.
4. Create a **Service Account**:
   - Navigate to **APIs & Services > Credentials**.
   - Click **Create Credentials** and choose **Service Account**.
   - Enter service account details and click **Create and Continue**, then **Done**.
5. Create and download the JSON key:
   - Click on the newly created service account from the list.
   - Go to the **Keys** tab.
   - Click **Add Key > Create new key**.
   - Select **JSON** and click **Create**.
   - A JSON file will be downloaded. Rename it (e.g. `service-account.json`) and place it under `storage/app/google/service-account.json` (or use an absolute path).

### 2. How to Setup Google Drive / Shared Drive Access
For the service account to interact with your Google Drive folder, you must share the target folder with the service account:
1. Open the downloaded JSON credentials file.
2. Find and copy the `"client_email"` value (looks like `service-account-name@project-id.iam.gserviceaccount.com`).
3. Open Google Drive in your browser.
4. Right-click the folder or Shared Drive you want to use as the root, and select **Share** (or **Manage members** for Shared Drives).
5. Paste the service account's client email and assign it the **Editor** (or **Content Manager** / **Contributor**) role.
6. Copy the Folder ID from the browser URL (e.g. in `https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ`, the folder ID is `1aBcDeFgHiJkLmNoPqRsTuVwXyZ`).
7. Add this folder ID into your `.env` file as `GOOGLE_DRIVE_ROOT_FOLDER_ID`.

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

You can upload a file and associate it with the model using the Spatie-inspired fluent API. You can specify a custom nested folder path on Google Drive via `toFolder()`:

```php
// Upload a file to a nested folder 'consultations/docs', customize filename, and attach to 'attachments' collection
$media = $consultation->addMedia($request->file('file'))
    ->toFolder('consultations/docs')
    ->usingFileName('custom-name.pdf')
    ->toMediaCollection('attachments');
```

*Note: Folder path lookup is fully optimized using Laravel's cache. If a folder is manually deleted from Drive, the package auto-detects and self-heals by recreating it.*

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

$result = GoogleSharedDrive::upload($request->file('file'), 'custom/folder/path');

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
