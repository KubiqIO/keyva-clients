<?php

namespace Keyva;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Keyva\Exceptions\KeyvaException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class KeyvaClient
{
    private string $apiKey;
    private ?string $publicKey;
    private GuzzleClient $client;
    private GuzzleClient $validationClient;

    public const BASE_URL = "https://keyva.dev/api/v1";
    public const VALIDATION_URL = "https://keyva.dev";

    /**
     * @param string $apiKey Your Keyva API key (e.g. k_live_...)
     * @param string|null $publicKey Optional base64-encoded Ed25519 public key for JWT token verification.
     */
    public function __construct(string $apiKey, ?string $publicKey = null)
    {
        $this->apiKey = $apiKey;
        $this->publicKey = $publicKey;

        $this->client = new GuzzleClient([
            'base_uri' => self::BASE_URL . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            // We want to handle exceptions manually to wrap them in KeyvaException
            'http_errors' => false,
        ]);

        $this->validationClient = new GuzzleClient([
            'base_uri' => self::VALIDATION_URL . '/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Validate a license key.
     *
     * @param string $key The license key to validate.
     * @param string|null $release Optional version string to check release eligibility.
     * @return array
     * @throws KeyvaException
     */
    public function validate(string $key, ?string $release = null): array
    {
        $query = ['key' => $key];
        if ($release !== null) {
            $query['release'] = $release;
        }

        $response = $this->validationClient->get('validate', ['query' => $query]);

        return $this->handleResponse($response);
    }

    /**
     * Verify a signed JWT token returned by the validation endpoint.
     *
     * @param string $token
     * @return mixed The decoded token payload.
     * @throws KeyvaException
     */
    public function verifyToken(string $token)
    {
        if (empty($this->publicKey)) {
            throw new KeyvaException("Public key is required to verify token");
        }

        $rawKeyBytes = base64_decode($this->publicKey);
        if ($rawKeyBytes === false) {
            throw new KeyvaException("Failed to decode public key from base64");
        }

        return (array) JWT::decode($token, new Key($rawKeyBytes, 'EdDSA'));
    }

    /**
     * Create a new license.
     *
     * @param array $options License creation options. Minimum required: 'productId'
     * @return array
     * @throws KeyvaException
     */
    public function createLicense(array $options): array
    {
        if (!isset($options['productId']) && !isset($options['product_id'])) {
             throw new KeyvaException("product_id is required");
        }

        $payload = [
            'product_id' => $options['productId'] ?? $options['product_id'],
        ];

        $this->populateOptions($payload, $options);

        $response = $this->client->post('licenses', ['json' => $payload]);

        return $this->handleResponse($response);
    }

    /**
     * Update an existing license.
     *
     * @param string $key The license key
     * @param array $options Update options.
     * @return array
     * @throws KeyvaException
     */
    public function updateLicense(string $key, array $options): array
    {
        $payload = [];
        $this->populateOptions($payload, $options);

        $response = $this->client->put('licenses/' . urlencode($key), ['json' => $payload]);

        return $this->handleResponse($response);
    }

    /**
     * Activate a license that has been revoked or suspended.
     *
     * @param string $key The license key
     * @param array $options Activation options (expires_at, duration)
     * @return array
     * @throws KeyvaException
     */
    public function activateLicense(string $key, array $options = []): array
    {
        $payload = [];
        if (isset($options['expiresAt'])) {
            $payload['expires_at'] = $options['expiresAt'];
        } elseif (isset($options['expires_at'])) {
            $payload['expires_at'] = $options['expires_at'];
        }

        if (isset($options['duration'])) {
            $payload['duration'] = $options['duration'];
        }

        $response = $this->client->post('licenses/' . urlencode($key) . '/activate', ['json' => $payload]);

        return $this->handleResponse($response);
    }

    /**
     * Revoke a license, preventing it from passing validation.
     *
     * @param string $key The license key
     * @return array
     * @throws KeyvaException
     */
    public function revokeLicense(string $key): array
    {
        $response = $this->client->post('licenses/' . urlencode($key) . '/revoke');
        return $this->handleResponse($response);
    }

    /**
     * Permanently delete a license.
     *
     * @param string $key The license key
     * @return array
     * @throws KeyvaException
     */
    public function deleteLicense(string $key): array
    {
        $response = $this->client->delete('licenses/' . urlencode($key));
        return $this->handleResponse($response);
    }

    /**
     * Helper to populate optional array fields for license creation and updating.
     */
    private function populateOptions(array &$payload, array $options): void
    {
        if (isset($options['featureCodes']) || isset($options['feature_codes'])) {
            $payload['feature_codes'] = $options['featureCodes'] ?? $options['feature_codes'];
        }
        if (isset($options['releaseVersions']) || isset($options['release_versions'])) {
            $payload['release_versions'] = $options['releaseVersions'] ?? $options['release_versions'];
        }
        if (isset($options['allowedIps']) || isset($options['allowed_ips'])) {
            $payload['allowed_ips'] = $options['allowedIps'] ?? $options['allowed_ips'];
        }
        if (isset($options['allowedNetworks']) || isset($options['allowed_networks'])) {
            $payload['allowed_networks'] = $options['allowedNetworks'] ?? $options['allowed_networks'];
        }
        if (isset($options['expiresAt']) || isset($options['expires_at'])) {
            $payload['expires_at'] = $options['expiresAt'] ?? $options['expires_at'];
        }
        if (isset($options['duration'])) {
            $payload['duration'] = $options['duration'];
        }
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     * @throws KeyvaException
     */
    private function handleResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = current(array_filter([$response->getBody()->getContents(), '{}']));
        $data = json_decode($body, true) ?? [];

        if ($statusCode >= 200 && $statusCode < 300) {
            return $data;
        }

        $message = $data['message'] ?? $response->getReasonPhrase();
        throw new KeyvaException($message, $statusCode, $response);
    }
}
