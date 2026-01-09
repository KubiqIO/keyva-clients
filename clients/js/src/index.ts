import axios, { AxiosInstance } from 'axios';
import { jwtVerify, importJWK } from 'jose';

export interface ValidateOptions {
    key: string;
    release?: string;
}

export interface CreateLicenseOptions {
    productId: string;
    expiresAt?: string;
    duration?: string;
    featureCodes?: string[];
    releaseVersions?: string[];
    allowedIps?: string[];
    allowedNetworks?: string[];
}

export interface UpdateLicenseOptions {
    expiresAt?: string;
    duration?: string;
    featureCodes?: string[];
    releaseVersions?: string[];
    allowedIps?: string[];
    allowedNetworks?: string[];
}

export interface ActivateLicenseOptions {
    expiresAt?: string;
    duration?: string;
}

export class KeyvaClient {
    private apiKey: string;
    private client: AxiosInstance;
    private validationClient: AxiosInstance;
    private publicKey?: string;

    constructor(apiKey: string, publicKey?: string) {
        this.apiKey = apiKey;
        this.publicKey = publicKey;
        this.client = axios.create({
            baseURL: "https://keyva.dev/api/v1",
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json',
            },
        });

        // Validation endpoint is at the root, not under /api/v1
        this.validationClient = axios.create({
            baseURL: "https://keyva.dev",
            headers: {
                'Content-Type': 'application/json',
            },
        });
    }

    public async verifyToken(token: string): Promise<any> {
        if (!this.publicKey) {
            throw new Error("Public key is required to verify token");
        }

        const jwk = {
            kty: 'OKP',
            crv: 'Ed25519',
            x: Buffer.from(this.publicKey, 'base64').toString('base64url')
        };

        const key = await importJWK(jwk, 'EdDSA');
        const { payload } = await jwtVerify(token, key);
        return payload;
    }

    public async validate(options: ValidateOptions): Promise<any> {
        const response = await this.validationClient.get('/validate', {
            params: {
                key: options.key,
                release: options.release,
            },
        });
        return response.data;
    }

    public async createLicense(options: CreateLicenseOptions): Promise<any> {
        const payload: any = {
            product_id: options.productId,
            feature_codes: options.featureCodes,
            release_versions: options.releaseVersions,
            allowed_ips: options.allowedIps,
            allowed_networks: options.allowedNetworks,
        };

        if (options.expiresAt) payload.expires_at = options.expiresAt;
        if (options.duration) payload.duration = options.duration;

        const response = await this.client.post('/licenses', payload);
        return response.data;
    }

    public async updateLicense(key: string, options: UpdateLicenseOptions): Promise<any> {
        const payload: any = {
            feature_codes: options.featureCodes,
            release_versions: options.releaseVersions,
            allowed_ips: options.allowedIps,
            allowed_networks: options.allowedNetworks,
        };

        if (options.expiresAt) payload.expires_at = options.expiresAt;
        if (options.duration) payload.duration = options.duration;

        const response = await this.client.put(`/licenses/${key}`, payload);
        return response.data;
    }

    public async activateLicense(key: string, options: ActivateLicenseOptions): Promise<any> {
        const payload: any = {};
        if (options.expiresAt) payload.expires_at = options.expiresAt;
        if (options.duration) payload.duration = options.duration;

        const response = await this.client.post(`/licenses/${key}/activate`, payload);
        return response.data;
    }

    public async revokeLicense(key: string): Promise<any> {
        const response = await this.client.post(`/licenses/${key}/revoke`);
        return response.data;
    }

    public async deleteLicense(key: string): Promise<any> {
        const response = await this.client.delete(`/licenses/${key}`);
        return response.data;
    }
}
