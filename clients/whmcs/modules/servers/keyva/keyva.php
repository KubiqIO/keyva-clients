<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

function keyva_MetaData()
{
    return [
        'DisplayName' => 'Keyva',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
    ];
}

function keyva_ConfigOptions()
{
    return [
        'API Key' => [
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Your Keyva API Key (starts with k_live_)',
        ],
        'Product ID' => [
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'The Keyva Product ID to link this service to',
        ],
        'Feature Codes' => [
            'Type' => 'textarea',
            'Rows' => '3',
            'Description' => 'Comma-separated list of feature codes (e.g., PRO, TEAMS)',
        ],
        'Release Versions' => [
            'Type' => 'textarea',
            'Rows' => '3',
            'Description' => 'Comma-separated list of allowed versions (e.g., 1.0.0, 1.1.0)',
        ],
    ];
}

function keyva_CreateAccount(array $params)
{
    try {
        $apiKey = $params['configoption1']; // API Key
        $productId = $params['configoption2']; // Product ID
        $featureCodes = $params['configoption3'];
        $releaseVersions = $params['configoption4'];

        $payload = [
            'product_id' => $productId,
        ];

        // Process Feature Codes
        if (!empty($featureCodes)) {
            $payload['feature_codes'] = array_map('trim', explode(',', $featureCodes));
        }

        // Process Release Versions
        if (!empty($releaseVersions)) {
            $payload['release_versions'] = array_map('trim', explode(',', $releaseVersions));
        }

        // Add IP if available? 
        // WHMCS usually has the dedicated IP in $params['serverip'] or service custom fields. 
        // For verify, we might not want to lock to the provisioning IP unless strictly required. 
        // We'll skip allowed_ips for now unless explicitly needed.

        $response = keyva_callApi('POST', '/licenses', $payload, $apiKey);

        if (isset($response['key'])) {
            // Save the License Key to the database
            // In WHMCS, 'domain' is often used for the primary identifier of a service if not a hosting account.
            // Alternatively, 'username' can be used.
            // We update the table specifically to enable the key to be shown in the client area.
            
            $serviceid = $params['serviceid'];
            
            // Validate if we can update the domain field
            $model = Capsule::table('tblhosting')->where('id', $serviceid)->first();
            if ($model) {
                 Capsule::table('tblhosting')->where('id', $serviceid)->update([
                    'domain' => $response['key'],
                ]);
            }

            return 'success';
        }

        return 'Failed to retrieve license key from response';

    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function keyva_SuspendAccount(array $params)
{
    try {
        $apiKey = $params['configoption1'];
        $licenseKey = $params['domain']; // We stored it in domain field

        if (empty($licenseKey)) {
            return 'License key not found';
        }

        // Call Revoke
        keyva_callApi('POST', "/licenses/{$licenseKey}/revoke", [], $apiKey);

        return 'success';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function keyva_UnsuspendAccount(array $params)
{
    try {
        $apiKey = $params['configoption1'];
        $licenseKey = $params['domain'];

        if (empty($licenseKey)) {
            return 'License key not found';
        }

        // Call Activate
        keyva_callApi('POST', "/licenses/{$licenseKey}/activate", [], $apiKey);

        return 'success';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function keyva_TerminateAccount(array $params)
{
    try {
        $apiKey = $params['configoption1'];
        $licenseKey = $params['domain'];

        if (empty($licenseKey)) {
            return 'License key not found';
        }

        keyva_callApi('DELETE', "/licenses/{$licenseKey}", [], $apiKey);

        return 'success';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

function keyva_ClientArea(array $params) {
    if ($params['status'] !== 'Active') {
        return '';
    }

    $licenseKey = $params['domain'];
    if (empty($licenseKey)) {
        return '';
    }

    // You could fetch details here to show validity, but simply showing the key is often enough.
    // Return HTML to be displayed in the client area product details page.
    $htmlOutput = '
        <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px;">
            <h4 style="margin-top: 0;">License Key</h4>
            <div style="background: #fff; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 1.2em;">' . $licenseKey . '</div>
        </div>
    ';

    return $htmlOutput;
}


/**
 * Helper to make API calls
 */
function keyva_callApi($method, $endpoint, $data, $apiKey)
{
    $url = 'https://keyva.dev/api/v1' . $endpoint;

    $curl = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($curl, $opts);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($err) {
        throw new Exception('cURL Error: ' . $err);
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
        $msg = isset($decoded['message']) ? $decoded['message'] : 'Unknown API Error';
        throw new Exception("API Request Failed ($httpCode): $msg");
    }

    return $decoded;
}
