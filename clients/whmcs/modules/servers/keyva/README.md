# Keyva WHMCS Module

A WHMCS Server Provisioning Module for Keyva.dev.

## Installation

1.  Upload the `keyva` directory to your WHMCS installation at:
    `/path/to/whmcs/modules/servers/keyva`

2.  The final path to the PHP file should be:
    `/path/to/whmcs/modules/servers/keyva/keyva.php`

## Configuration

1.  Log in to your WHMCS Admin Area.
2.  Go to **System Settings > Products/Services > Products/Services**.
3.  Create a new Product or edit an existing one.
4.  Click on the **Module Settings** tab.
5.  Select **Keyva** from the **Module Name** dropdown.
6.  Enter your **API Key** (from Keyva Dashboard).
7.  Enter the **Product ID** (from Keyva Dashboard) that you want to issue licenses for.
8.  (Optional) Enter default **Feature Codes** (comma-separated).
9.  (Optional) Enter allowed **Release Versions**.
10. Save Changes.

## Usage

-   **Create**: When a service is activated (after payment or manually), a new license is created in Keyva. The license key is stored in the service's **Domain** field.
-   **Suspend**: When a service is suspended (overdue payment), the license is **Revoked** in Keyva.
-   **Unsuspend**: When unsuspended, the license is **Activated** (re-enabled).
-   **Terminate**: When terminated, the license is **Deleted** from Keyva.

## Client Area

The client area service details page will display the assigned License Key.
