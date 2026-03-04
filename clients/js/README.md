# Keyva Node.js Client

A Node.js client for interacting with the Keyva API.

## Installation

```bash
npm install keyva-client
```

## Usage

### 1. Initialize the Client

Initialize the client with your API key. You can optionally provide your public key if you intend to verify signed tokens.

```javascript
import { KeyvaClient } from 'keyva-client';

const client = new KeyvaClient('YOUR_API_KEY', 'YOUR_EdDSA_PUBLIC_KEY');
```

### 2. Validate a License

Validate a license key against the Keyva API. You can check the validation result and verify the signature of the returned token.

```javascript
try {
  const result = await client.validate({ 
    key: 'LICENSE_KEY',
    release: '1.0.0' // optional
  });
  console.log('Validation result:', result);

  // You can verify the signed token returned in the validation response
  if (result.valid && result.token) {
    const decoded = await client.verifyToken(result.token);
    console.log('Decoded token payload:', decoded);
  }
} catch (error) {
  console.error('Validation failed:', error.message);
}
```

### 3. Create a License

Create a new license with specific parameters like duration, features, and allowed IPs.

```javascript
const newLicense = await client.createLicense({
  productId: 'PRODUCT_ID',
  duration: '1y', // or expiresAt: '2025-12-31T23:59:59Z'
  featureCodes: ['PRO', 'ANALYTICS'],
  allowedIps: ['192.168.1.1'],
  releaseVersions: ['1.0.0', '1.1.0']
});
console.log('Created license:', newLicense);
```

### 4. Update a License

Update an existing license. Note that lists (like feature codes) are replaced, not appended.

```javascript
const updatedLicense = await client.updateLicense('LICENSE_KEY', {
  featureCodes: ['PRO', 'ANALYTICS', 'TEAMS'], // This replaces the list
  duration: '30d' // Extends by 30 days
});
console.log('Updated license:', updatedLicense);
```

### 5. Activate a License

Activate a license that may be in a revoked or expired state, optionally setting a new duration.

```javascript
const activated = await client.activateLicense('LICENSE_KEY', {
  duration: '14d' // Add 14 days upon activation
});
console.log('Activated:', activated);
```

### 6. Revoke a License

Revoke a valid license, preventing it from passing further validation checks.

```javascript
const revoked = await client.revokeLicense('LICENSE_KEY');
console.log('Revoked:', revoked);
```

### 7. Delete a License

Permanently remove a license.

```javascript
const deleted = await client.deleteLicense('LICENSE_KEY');
console.log('Deleted:', deleted);
```