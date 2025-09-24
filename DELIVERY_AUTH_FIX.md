# Delivery Authentication Controller Fix

## Issue
The linter was showing errors for undefined methods in the Delivery AuthController:
1. `createToken` method was not recognized
2. `delete` method on `currentAccessToken()` was not recognized

## Root Cause
The linter was not properly recognizing methods from the `HasApiTokens` trait that is used by the User model. This is a common issue with static analysis tools when dealing with traits and dynamic method injection.

## Solution
Added proper type hints to help the linter understand the available methods:

1. **Imported required classes:**
   ```php
   use Laravel\Sanctum\HasApiTokens;
   use Laravel\Sanctum\PersonalAccessToken;
   ```

2. **Added type hints for the User variable:**
   ```php
   /** @var User&HasApiTokens $user */
   $user = Auth::user();
   ```

3. **Separated the token deletion call:**
   ```php
   /** @var PersonalAccessToken $token */
   $token = $user->currentAccessToken();
   $token->delete();
   ```

## Files Modified
- `app/Http/Controllers/Api/Delivery/AuthController.php`

## Verification
- The linter no longer shows errors
- The functionality remains the same
- The implementation follows the same pattern as the existing API AuthController
- All authentication methods work correctly

## Testing
The fix has been tested to ensure:
- Login still works and generates tokens
- Profile retrieval works correctly
- Logout properly revokes tokens
- No runtime errors occur

## Notes
This is a linter issue rather than a functional issue. The original code would have worked correctly at runtime, but the type hints help static analysis tools understand the code better.
