"""Tests for the Keyva Python client."""

import pytest
import responses

from keyva import KeyvaClient, KeyvaError


BASE_URL = "https://keyva.dev/api/v1"
VALIDATION_URL = "https://keyva.dev"


@pytest.fixture
def client() -> KeyvaClient:
    return KeyvaClient("test_api_key")


@pytest.fixture
def client_with_key() -> KeyvaClient:
    # A real Ed25519 public key (base64-encoded, 32 bytes) for testing
    return KeyvaClient("test_api_key", public_key="T8rh7KRgKiJUX9dXZgi4T0LDklM8s/8+54PdTTVM8JQ=")


# ------------------------------------------------------------------
# validate
# ------------------------------------------------------------------

class TestValidate:
    @responses.activate
    def test_validate_with_release(self, client: KeyvaClient) -> None:
        responses.add(
            responses.GET,
            f"{VALIDATION_URL}/validate",
            json={"valid": True, "token": "some_token"},
            status=200,
        )

        result = client.validate("LICENSE_KEY", release="1.0.0")

        assert result == {"valid": True, "token": "some_token"}
        assert "key=LICENSE_KEY" in responses.calls[0].request.url
        assert "release=1.0.0" in responses.calls[0].request.url

    @responses.activate
    def test_validate_without_release(self, client: KeyvaClient) -> None:
        responses.add(
            responses.GET,
            f"{VALIDATION_URL}/validate",
            json={"valid": False},
            status=200,
        )

        result = client.validate("LICENSE_KEY")

        assert result == {"valid": False}
        assert "release" not in responses.calls[0].request.url

    @responses.activate
    def test_validate_http_error(self, client: KeyvaClient) -> None:
        responses.add(
            responses.GET,
            f"{VALIDATION_URL}/validate",
            json={"message": "Invalid key"},
            status=400,
        )

        with pytest.raises(KeyvaError) as exc_info:
            client.validate("BAD_KEY")

        assert exc_info.value.status_code == 400
        assert "Invalid key" in str(exc_info.value)


# ------------------------------------------------------------------
# createLicense
# ------------------------------------------------------------------

class TestCreateLicense:
    @responses.activate
    def test_create_with_duration(self, client: KeyvaClient) -> None:
        responses.add(
            responses.POST,
            f"{BASE_URL}/licenses",
            json={"id": "license_id", "key": "KEYVA-XXXX"},
            status=201,
        )

        result = client.create_license("prod_1", duration="1y")

        assert result == {"id": "license_id", "key": "KEYVA-XXXX"}
        body = responses.calls[0].request.body
        assert b'"product_id": "prod_1"' in body or b'"product_id":"prod_1"' in body

    @responses.activate
    def test_create_with_all_options(self, client: KeyvaClient) -> None:
        responses.add(
            responses.POST,
            f"{BASE_URL}/licenses",
            json={"id": "license_id"},
            status=201,
        )

        result = client.create_license(
            "prod_1",
            expires_at="2027-12-31T23:59:59Z",
            feature_codes=["PRO", "ANALYTICS"],
            release_versions=["1.0.0", "1.1.0"],
            allowed_ips=["192.168.1.1"],
            allowed_networks=["10.0.0.0/8"],
        )

        assert result == {"id": "license_id"}
        import json
        body = json.loads(responses.calls[0].request.body)
        assert body["product_id"] == "prod_1"
        assert body["feature_codes"] == ["PRO", "ANALYTICS"]
        assert body["release_versions"] == ["1.0.0", "1.1.0"]
        assert body["allowed_ips"] == ["192.168.1.1"]
        assert body["allowed_networks"] == ["10.0.0.0/8"]
        assert body["expires_at"] == "2027-12-31T23:59:59Z"

    @responses.activate
    def test_create_minimal(self, client: KeyvaClient) -> None:
        """Only product_id is required; optional fields should be omitted."""
        responses.add(
            responses.POST,
            f"{BASE_URL}/licenses",
            json={"id": "license_id"},
            status=201,
        )

        client.create_license("prod_1")

        import json
        body = json.loads(responses.calls[0].request.body)
        assert body == {"product_id": "prod_1"}


# ------------------------------------------------------------------
# updateLicense
# ------------------------------------------------------------------

class TestUpdateLicense:
    @responses.activate
    def test_update_license(self, client: KeyvaClient) -> None:
        responses.add(
            responses.PUT,
            f"{BASE_URL}/licenses/LICENSE_KEY",
            json={"updated": True},
            status=200,
        )

        result = client.update_license(
            "LICENSE_KEY",
            duration="1y",
            feature_codes=["PRO", "TEAMS"],
        )

        assert result == {"updated": True}
        import json
        body = json.loads(responses.calls[0].request.body)
        assert body["duration"] == "1y"
        assert body["feature_codes"] == ["PRO", "TEAMS"]


# ------------------------------------------------------------------
# activateLicense
# ------------------------------------------------------------------

class TestActivateLicense:
    @responses.activate
    def test_activate_license(self, client: KeyvaClient) -> None:
        responses.add(
            responses.POST,
            f"{BASE_URL}/licenses/LICENSE_KEY/activate",
            json={"activated": True},
            status=200,
        )

        result = client.activate_license("LICENSE_KEY")

        assert result == {"activated": True}

    @responses.activate
    def test_activate_with_duration(self, client: KeyvaClient) -> None:
        responses.add(
            responses.POST,
            f"{BASE_URL}/licenses/LICENSE_KEY/activate",
            json={"activated": True},
            status=200,
        )

        result = client.activate_license("LICENSE_KEY", duration="14d")

        assert result == {"activated": True}
        import json
        body = json.loads(responses.calls[0].request.body)
        assert body["duration"] == "14d"


# ------------------------------------------------------------------
# revokeLicense
# ------------------------------------------------------------------

class TestRevokeLicense:
    @responses.activate
    def test_revoke_license(self, client: KeyvaClient) -> None:
        responses.add(
            responses.POST,
            f"{BASE_URL}/licenses/LICENSE_KEY/revoke",
            json={"revoked": True},
            status=200,
        )

        result = client.revoke_license("LICENSE_KEY")

        assert result == {"revoked": True}


# ------------------------------------------------------------------
# deleteLicense
# ------------------------------------------------------------------

class TestDeleteLicense:
    @responses.activate
    def test_delete_license(self, client: KeyvaClient) -> None:
        responses.add(
            responses.DELETE,
            f"{BASE_URL}/licenses/LICENSE_KEY",
            json={"deleted": True},
            status=200,
        )

        result = client.delete_license("LICENSE_KEY")

        assert result == {"deleted": True}


# ------------------------------------------------------------------
# verifyToken
# ------------------------------------------------------------------

class TestVerifyToken:
    def test_verify_token_no_public_key(self, client: KeyvaClient) -> None:
        with pytest.raises(KeyvaError, match="Public key is required"):
            client.verify_token("some_token")
