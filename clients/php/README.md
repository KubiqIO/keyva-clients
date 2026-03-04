# Keyva PHP Client

The official PHP client for the Keyva API.

## Installation

Install via Composer:

```bash
composer require keyva/client
```

## Basic Usage

```php
<?php

require 'vendor/autoload.php';

use Keyva\KeyvaClient;
use Keyva\Exceptions\KeyvaException;

$client = new KeyvaClient('k_live_...');

try {
    // Validate a license
    $validation = $client->validate('L1C3N-S3K3Y', '1.0.0');

    if ($validation['valid']) {
        echo "License is valid!";
    }
} catch (KeyvaException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Creating a License

```php
$license = $client->createLicense([
    'productId' => 'prod_...',
    'duration' => '30d', // or expiresAt => '2024-12-31T23:59:59Z'
    'featureCodes' => ['pro']
]);
echo "New License Key: " . $license['key'];
```

## Updating a License

```php
$client->updateLicense('L1C3N-S3K3Y', [
    'featureCodes' => ['pro', 'enterprise'] // Replaces existing feature codes
]);
```

## Managing Licenses

```php
// Revoke
$client->revokeLicense('L1C3N-S3K3Y');

// Activate (un-revoke)
$client->activateLicense('L1C3N-S3K3Y');

// Delete permanently
$client->deleteLicense('L1C3N-S3K3Y');
```

## Verifying Offline Tokens

```php
// To verify the cryptographic signature of an offline validation token
$client = new KeyvaClient('k_live_...', 'YOUR_BASE64_PUBLIC_KEY');

$payload = $client->verifyToken($validation['token']);
```
