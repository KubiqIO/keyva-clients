import { KeyvaClient } from './dist/index.js';

const client = new KeyvaClient('sk_live_7459226fd94c86a6b78b43dce185323bc9e65f1ad1eaa454', 'T8rh7KRgKiJUX9dXZgi4T0LDklM8s/8+54PdTTVM8JQ=');

// Validate a license
const validationResult = await client.validate({ key: 'KEYVA-DW4ZE7' });
console.log(validationResult);

// Check the token
const token = validationResult.token;
const decodedToken = await client.verifyToken(token);
console.log(decodedToken);

// Create a new license
const newLicense = await client.createLicense({
    productId: 'edce2aab-599e-4c22-8174-bfd2d490e6ea',
    duration: '1y',
});
console.log(newLicense);