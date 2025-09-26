# Device Token Functionality Temporarily Hidden

## Overview
The device token functionality in the delivery application has been temporarily hidden as requested. This affects the login process where the device token is no longer processed or stored.

## Changes Made

### AuthController Modifications
1. Removed `device_token` validation from the login request
2. Commented out the device token update logic in the login method
3. The `fc_token` field is still present in the User model but is not being updated during login

## Files Affected
- `app/Http/Controllers/Api/Delivery/AuthController.php`

## What's Hidden
- Device token parameter in login request validation
- Device token storage/update logic
- Device token functionality is effectively disabled but can be easily re-enabled

## How to Re-enable
To re-enable the device token functionality, uncomment the following sections in the AuthController:

1. Add `'device_token' => 'nullable|string'` back to the validation rules
2. Uncomment the device token update logic:
   ```php
   if ($request->has('device_token')) {
       $user->fc_token = $request->device_token;
       $user->save();
   }
   ```

## Reason for Temporary Disable
As per user request, the device token functionality has been temporarily hidden. This may be due to:
- Testing requirements
- Development phase considerations
- Temporary removal of push notification features

## Impact
- Delivery personnel can still login normally
- No device tokens will be stored or updated
- Push notifications based on device tokens will not work
- Existing device tokens in the database remain unchanged
