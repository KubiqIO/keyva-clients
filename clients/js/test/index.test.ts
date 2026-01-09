import axios from 'axios';
import { KeyvaClient } from '../src/index';

jest.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

jest.mock('jose', () => ({
    jwtVerify: jest.fn(),
    importJWK: jest.fn(),
}));
import { jwtVerify, importJWK } from 'jose';

describe('KeyvaClient', () => {
    let client: KeyvaClient;
    const apiKey = 'test_key';
    const baseUrl = 'https://keyva.dev/api/v1';

    beforeEach(() => {
        // Reset mocks
        mockedAxios.create.mockReturnThis();
        client = new KeyvaClient(apiKey);
    });

    describe('validate', () => {
        it('should call GET /validate with correct params', async () => {
            mockedAxios.get.mockResolvedValueOnce({ data: { valid: true } });
            const options = { key: 'license_key', release: '1.0.0' };

            const result = await client.validate(options);

            expect(mockedAxios.get).toHaveBeenCalledWith('/validate', {
                params: options
            });
            expect(result).toEqual({ valid: true });
        });
    });

    describe('createLicense', () => {
        it('should call POST /licenses with correct payload', async () => {
            mockedAxios.post.mockResolvedValueOnce({ data: { id: 'license_id' } });
            const options = { productId: 'prod_1', duration: '1y' };

            const result = await client.createLicense(options);

            expect(mockedAxios.post).toHaveBeenCalledWith('/licenses', {
                product_id: 'prod_1',
                duration: '1y',
                feature_codes: undefined,
                release_versions: undefined,
                allowed_ips: undefined,
                allowed_networks: undefined
            });
            expect(result).toEqual({ id: 'license_id' });
        });
    });

    describe('updateLicense', () => {
        it('should call PUT /licenses/:key with correct payload', async () => {
            mockedAxios.put.mockResolvedValueOnce({ data: { updated: true } });
            const options = { duration: '1y' };
            const key = 'license_key';

            const result = await client.updateLicense(key, options);

            expect(mockedAxios.put).toHaveBeenCalledWith(`/licenses/${key}`, {
                duration: '1y',
                feature_codes: undefined,
                release_versions: undefined,
                allowed_ips: undefined,
                allowed_networks: undefined
            });
            expect(result).toEqual({ updated: true });
        });
    });

    describe('activateLicense', () => {
        it('should call POST /licenses/:key/activate', async () => {
            mockedAxios.post.mockResolvedValueOnce({ data: { activated: true } });
            const key = 'license_key';

            const result = await client.activateLicense(key, {});

            expect(mockedAxios.post).toHaveBeenCalledWith(`/licenses/${key}/activate`, {});
            expect(result).toEqual({ activated: true });
        });
    });

    describe('revokeLicense', () => {
        it('should call POST /licenses/:key/revoke', async () => {
            mockedAxios.post.mockResolvedValueOnce({ data: { revoked: true } });
            const key = 'license_key';

            const result = await client.revokeLicense(key);

            expect(mockedAxios.post).toHaveBeenCalledWith(`/licenses/${key}/revoke`);
            expect(result).toEqual({ revoked: true });
        });
    });

    describe('deleteLicense', () => {
        it('should call DELETE /licenses/:key', async () => {
            mockedAxios.delete.mockResolvedValueOnce({ data: { deleted: true } });
            const key = 'license_key';

            const result = await client.deleteLicense(key);

            expect(mockedAxios.delete).toHaveBeenCalledWith(`/licenses/${key}`);
            expect(result).toEqual({ deleted: true });
        });
    });

    describe('verifyToken', () => {
        it('should verify token using jose', async () => {
            const publicKey = 'public_key_base64';
            client = new KeyvaClient(apiKey, publicKey);
            const token = 'test_token';
            const expectedPayload = { sub: 'test' };

            (importJWK as jest.Mock).mockResolvedValue('mock_key');
            (jwtVerify as jest.Mock).mockResolvedValue({ payload: expectedPayload });

            const result = await client.verifyToken(token);

            expect(importJWK).toHaveBeenCalledWith(expect.objectContaining({
                kty: 'OKP',
                crv: 'Ed25519',
                x: expect.any(String) // Assuming base64url conversion happened
            }), 'EdDSA');
            expect(jwtVerify).toHaveBeenCalledWith(token, 'mock_key');
            expect(result).toEqual(expectedPayload);
        });

        it('should throw error if public key is not set', async () => {
            client = new KeyvaClient(apiKey);
            await expect(client.verifyToken('token')).rejects.toThrow("Public key is required");
        });
    });
});
