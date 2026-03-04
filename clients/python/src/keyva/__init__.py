"""Keyva Python Client — interact with the Keyva.dev license management API."""

from __future__ import annotations

import base64
from typing import Any, Dict, List, Optional

import jwt
import requests
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PublicKey
from cryptography.hazmat.primitives.serialization import Encoding, PublicFormat

__version__ = "0.1.0"

__all__ = ["KeyvaClient", "KeyvaError", "__version__"]


class KeyvaError(Exception):
    """Raised when a Keyva API call fails."""

    def __init__(self, message: str, status_code: Optional[int] = None, response: Optional[requests.Response] = None):
        super().__init__(message)
        self.status_code = status_code
        self.response = response


class KeyvaClient:
    """Client for the Keyva.dev license management API.

    Args:
        api_key: Your Keyva API key (e.g. ``k_live_...``).
        public_key: Optional base64-encoded Ed25519 public key for JWT
            token verification.
    """

    BASE_URL = "https://keyva.dev/api/v1"
    VALIDATION_URL = "https://keyva.dev"

    def __init__(self, api_key: str, public_key: Optional[str] = None) -> None:
        self.api_key = api_key
        self.public_key = public_key
        self._session = requests.Session()
        self._session.headers.update({
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json",
        })

    # ------------------------------------------------------------------
    # Validation
    # ------------------------------------------------------------------

    def validate(self, key: str, *, release: Optional[str] = None) -> Dict[str, Any]:
        """Validate a license key.

        Args:
            key: The license key to validate.
            release: Optional version string (e.g. ``"1.0.0"``) to check
                release eligibility.

        Returns:
            The validation response as a dictionary.
        """
        params: Dict[str, str] = {"key": key}
        if release is not None:
            params["release"] = release

        resp = requests.get(f"{self.VALIDATION_URL}/validate", params=params)
        self._raise_for_status(resp)
        return resp.json()

    def verify_token(self, token: str) -> Dict[str, Any]:
        """Verify a signed JWT token returned by the validation endpoint.

        Requires a ``public_key`` to have been provided at construction time.

        Args:
            token: The JWT token string to verify.

        Returns:
            The decoded token payload.

        Raises:
            KeyvaError: If no public key was provided.
        """
        if not self.public_key:
            raise KeyvaError("Public key is required to verify token")

        # Decode the base64 public key to raw 32 bytes
        raw_key_bytes = base64.b64decode(self.public_key)
        ed25519_key = Ed25519PublicKey.from_public_bytes(raw_key_bytes)

        # PyJWT expects a PEM-encoded key or a cryptography key object
        pem_key = ed25519_key.public_bytes(Encoding.PEM, PublicFormat.SubjectPublicKeyInfo)

        payload: Dict[str, Any] = jwt.decode(
            token,
            pem_key,
            algorithms=["EdDSA"],
            options={"verify_aud": False},
        )
        return payload

    # ------------------------------------------------------------------
    # License management
    # ------------------------------------------------------------------

    def create_license(
        self,
        product_id: str,
        *,
        expires_at: Optional[str] = None,
        duration: Optional[str] = None,
        feature_codes: Optional[List[str]] = None,
        release_versions: Optional[List[str]] = None,
        allowed_ips: Optional[List[str]] = None,
        allowed_networks: Optional[List[str]] = None,
    ) -> Dict[str, Any]:
        """Create a new license.

        Args:
            product_id: The ID of the product.
            expires_at: Expiration date (ISO 8601). Cannot be used with *duration*.
            duration: Duration string (e.g. ``"14d"``, ``"1mo"``, ``"1y"``).
            feature_codes: List of feature codes to enable.
            release_versions: List of allowed release versions.
            allowed_ips: List of allowed IP addresses.
            allowed_networks: List of allowed network CIDRs.

        Returns:
            The created license as a dictionary.
        """
        payload: Dict[str, Any] = {
            "product_id": product_id,
        }
        if feature_codes is not None:
            payload["feature_codes"] = feature_codes
        if release_versions is not None:
            payload["release_versions"] = release_versions
        if allowed_ips is not None:
            payload["allowed_ips"] = allowed_ips
        if allowed_networks is not None:
            payload["allowed_networks"] = allowed_networks
        if expires_at is not None:
            payload["expires_at"] = expires_at
        if duration is not None:
            payload["duration"] = duration

        resp = self._session.post(f"{self.BASE_URL}/licenses", json=payload)
        self._raise_for_status(resp)
        return resp.json()

    def update_license(
        self,
        key: str,
        *,
        expires_at: Optional[str] = None,
        duration: Optional[str] = None,
        feature_codes: Optional[List[str]] = None,
        release_versions: Optional[List[str]] = None,
        allowed_ips: Optional[List[str]] = None,
        allowed_networks: Optional[List[str]] = None,
    ) -> Dict[str, Any]:
        """Update an existing license.

        Note: list fields (e.g. *feature_codes*) replace the existing list entirely.

        Args:
            key: The license key.
            expires_at: New expiration date (ISO 8601).
            duration: Duration to add (e.g. ``"14d"``).
            feature_codes: List of feature codes.
            release_versions: List of allowed release versions.
            allowed_ips: List of allowed IP addresses.
            allowed_networks: List of allowed network CIDRs.

        Returns:
            The updated license as a dictionary.
        """
        payload: Dict[str, Any] = {}
        if feature_codes is not None:
            payload["feature_codes"] = feature_codes
        if release_versions is not None:
            payload["release_versions"] = release_versions
        if allowed_ips is not None:
            payload["allowed_ips"] = allowed_ips
        if allowed_networks is not None:
            payload["allowed_networks"] = allowed_networks
        if expires_at is not None:
            payload["expires_at"] = expires_at
        if duration is not None:
            payload["duration"] = duration

        resp = self._session.put(f"{self.BASE_URL}/licenses/{key}", json=payload)
        self._raise_for_status(resp)
        return resp.json()

    def activate_license(
        self,
        key: str,
        *,
        expires_at: Optional[str] = None,
        duration: Optional[str] = None,
    ) -> Dict[str, Any]:
        """Activate a license that has been revoked or suspended.

        Args:
            key: The license key.
            expires_at: New expiration date (ISO 8601).
            duration: Duration to add (e.g. ``"14d"``).

        Returns:
            The activation result as a dictionary.
        """
        payload: Dict[str, Any] = {}
        if expires_at is not None:
            payload["expires_at"] = expires_at
        if duration is not None:
            payload["duration"] = duration

        resp = self._session.post(f"{self.BASE_URL}/licenses/{key}/activate", json=payload)
        self._raise_for_status(resp)
        return resp.json()

    def revoke_license(self, key: str) -> Dict[str, Any]:
        """Revoke a license, preventing it from passing validation.

        Args:
            key: The license key.

        Returns:
            The revocation result as a dictionary.
        """
        resp = self._session.post(f"{self.BASE_URL}/licenses/{key}/revoke")
        self._raise_for_status(resp)
        return resp.json()

    def delete_license(self, key: str) -> Dict[str, Any]:
        """Permanently delete a license.

        Args:
            key: The license key.

        Returns:
            The deletion result as a dictionary.
        """
        resp = self._session.delete(f"{self.BASE_URL}/licenses/{key}")
        self._raise_for_status(resp)
        return resp.json()

    # ------------------------------------------------------------------
    # Internals
    # ------------------------------------------------------------------

    @staticmethod
    def _raise_for_status(resp: requests.Response) -> None:
        """Raise :class:`KeyvaError` on non-2xx responses."""
        if not resp.ok:
            try:
                body = resp.json()
                message = body.get("message", resp.reason)
            except Exception:
                message = resp.reason or f"HTTP {resp.status_code}"
            raise KeyvaError(message, status_code=resp.status_code, response=resp)
