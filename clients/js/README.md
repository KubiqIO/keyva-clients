# Keyva Node.js Client

A Node.js client for interacting with the Keyva API.

## Installation

```bash
npm install keyva-client
```

## Usage

```javascript
import { KeyvaClient } from 'keyva-client';

// Initialize the client
// You can optionally pass your public key for token verification
const client = new KeyvaClient('YOUR_API_KEY', 'YOUR_EdDSA_PUBLIC_KEY');

// 1. Validate a license
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

// 2. Create a new license
const newLicense = await client.createLicense({
  productId: 'PRODUCT_ID',
  duration: '1y', // or expiresAt: '2025-12-31T23:59:59Z'
  featureCodes: ['PRO', 'ANALYTICS'],
  allowedIps: ['192.168.1.1'],
  releaseVersions: ['1.0.0', '1.1.0']
});
console.log('Created license:', newLicense);

// 3. Update an existing license
const updatedLicense = await client.updateLicense('LICENSE_KEY', {
  featureCodes: ['PRO', 'ANALYTICS', 'TEAMS'], // This replaces the list
  duration: '30d' // Extends by 30 days
});
console.log('Updated license:', updatedLicense);

// 4. Activate a license (if previously suspended/revoked)
const activated = await client.activateLicense('LICENSE_KEY', {
  duration: '14d' // Add 14 days upon activation
});
console.log('Activated:', activated);

// 5. Revoke a license
const revoked = await client.revokeLicense('LICENSE_KEY');
console.log('Revoked:', revoked);

// 6. Delete a license
const deleted = await client.deleteLicense('LICENSE_KEY');
console.log('Deleted:', deleted);
```