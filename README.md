# smart-daddy/user-activity

User activity tracking package for Laravel applications.

This package provides:
- `TracksUserActivity` trait for Eloquent models
- `ModelActivity` model
- migration for `model_activities` table

## Requirements

- PHP 8.2+
- Laravel 12+

## Installation

```bash
composer require smart-daddy/user-activity
```

## Migration

```bash
php artisan migrate
```

The package registers its migration through the service provider.

## Usage

Add the trait to any model you want to track:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SmartDaddy\UserActivity\Traits\TracksUserActivity;

class Product extends Model
{
    use TracksUserActivity;
}
```

## What gets tracked

- `created_by` when a tracked model is created by an authenticated user
- `updated_by` when a tracked model is updated by an authenticated user
- activity row deletion when the tracked model is permanently deleted
- no activity is written when there is no authenticated user

## Access activity info

```php
$product->activity;          // ModelActivity record
$product->creator();         // User who created
$product->updater();         // User who last updated
```

## Database schema

`model_activities` columns:
- `id`
- `activityable_type`
- `activityable_id`
- `created_by` (nullable FK to `users.id`)
- `updated_by` (nullable FK to `users.id`)
- timestamps

Unique key:
- `activityable_type + activityable_id`

## Repository

[GitHub - SMART-DADDY/User-Activity](https://github.com/SMART-DADDY/User-Activity)

## Release checklist

1. Push latest changes to `main`.
2. Create and push a version tag:

```bash
git tag v0.1.0
git push origin v0.1.0
```

3. Submit the GitHub repository to Packagist.
4. In Packagist, enable auto-update webhook for new tags.

## License

MIT
