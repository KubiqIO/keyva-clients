<?php

require 'vendor/autoload.php';

use Keyva\KeyvaClient;
use Keyva\Exceptions\KeyvaException;

$apiKey = getenv('KEYVA_API_KEY');

if (!$apiKey) {
    echo "KEYVA_API_KEY environment variable is required\n";
    exit(1);
}

// Ensure error reporting is on for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

$client = new KeyvaClient($apiKey);
$testKey = "k_test_" . substr(md5(uniqid()), 0, 16);

try {
    echo "Creating license...\n";
    $license = $client->createLicense([
        'productId' => 'prod_test',
        'duration' => '14d',
        'featureCodes' => ['feature1'],
    ]);
    
    $createdKey = $license['key'];
    echo "Created license: " . $createdKey . "\n\n";

    echo "Validating license...\n";
    $validation = $client->validate($createdKey);
    echo "Validation successful.\n";
    echo "Valid: " . ($validation['valid'] ? 'true' : 'false') . "\n";
    
    // Test update
    echo "\nUpdating license...\n";
    $client->updateLicense($createdKey, [
        'featureCodes' => ['feature1', 'feature2'],
    ]);
    echo "Updated license.\n";

    // Test revoke
    echo "\nRevoking license...\n";
    $client->revokeLicense($createdKey);
    echo "Revoked license.\n";

    // Re-validate to ensure revoked
    try {
        echo "Validating revoked license (expecting invalid)...\n";
        $revokedValidation = $client->validate($createdKey);
        echo "Valid: " . ($revokedValidation['valid'] ? 'true' : 'false') . "\n";
    } catch (KeyvaException $e) {
        echo "Validation failed as expected: " . $e->getMessage() . "\n";
    }

    // Activate
    echo "\nActivating license...\n";
    $client->activateLicense($createdKey);
    echo "Activated license.\n";

    // Delete
    echo "\nDeleting license...\n";
    $client->deleteLicense($createdKey);
    echo "Deleted license.\n";

} catch (KeyvaException $e) {
    echo "API Error (" . $e->getStatusCode() . "): " . $e->getMessage() . "\n";
    if ($e->getResponse()) {
        try {
            echo "Response Body: " . current(array_filter([$e->getResponse()->getBody()->getContents(), '{}'])) . "\n";
        } catch (\Throwable $err) {
            // Ignore
        }
    }
    exit(1);
} catch (\Throwable $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}
