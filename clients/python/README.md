# Keyva Python Client

A Python client for interacting with the [Keyva.dev](https://keyva.dev) license management API.

## Installation

```bash
pip install keyva-client
```

## Usage

### 1. Initialize the Client

```python
from keyva import KeyvaClient

client = KeyvaClient("YOUR_API_KEY", public_key="YOUR_EdDSA_PUBLIC_KEY")
```

### 2. Validate a License

```python
result = client.validate("LICENSE_KEY", release="1.0.0")
print("Valid:", result["valid"])

# Verify the signed token
if result.get("valid") and result.get("token"):
    decoded = client.verify_token(result["token"])
    print("Decoded:", decoded)
```

### 3. Create a License

```python
license = client.create_license(
    "PRODUCT_ID",
    duration="1y",  # or expires_at="2027-12-31T23:59:59Z"
    feature_codes=["PRO", "ANALYTICS"],
    allowed_ips=["192.168.1.1"],
    release_versions=["1.0.0", "1.1.0"],
)
print("Created:", license)
```

### 4. Update a License

```python
updated = client.update_license(
    "LICENSE_KEY",
    feature_codes=["PRO", "ANALYTICS", "TEAMS"],
    duration="30d",
)
print("Updated:", updated)
```

### 5. Activate a License

```python
activated = client.activate_license("LICENSE_KEY", duration="14d")
print("Activated:", activated)
```

### 6. Revoke a License

```python
revoked = client.revoke_license("LICENSE_KEY")
print("Revoked:", revoked)
```

### 7. Delete a License

```python
deleted = client.delete_license("LICENSE_KEY")
print("Deleted:", deleted)
```

## Error Handling

All methods raise `KeyvaError` on non-2xx responses:

```python
from keyva import KeyvaClient, KeyvaError

try:
    result = client.validate("BAD_KEY")
except KeyvaError as e:
    print(f"Error {e.status_code}: {e}")
```
