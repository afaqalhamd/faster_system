# Delivery Login API Instructions

## Endpoint
**POST** `/api/delivery/login`

## URL
```
http://192.168.0.238/api/delivery/login
```

## Request Format
- **Method**: POST
- **Content-Type**: application/json
- **Body**: JSON object with email and password

## Request Body
```json
{
  "email": "dhl@gmail.com",
  "password": "12345678"
}
```

## Response Format
### Success Response (200)
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "username",
      "email": "dhl@gmail.com",
      "phone": "phone_number",
      "avatar": "avatar_url",
      "carrier_id": 1,
      "carrier_name": "Carrier Name"
    },
    "token": "access_token"
  }
}
```

### Error Response (401)
```json
{
  "status": false,
  "message": "Invalid credentials"
}
```

### Error Response (403)
```json
{
  "status": false,
  "message": "Access denied. User is not a delivery personnel."
}
```

## Common Issues and Solutions

### 1. 404 Not Found
**Cause**: Using GET method instead of POST
**Solution**: Use POST method

### 2. 404 Not Found
**Cause**: Using query parameters instead of JSON body
**Solution**: Send data as JSON in request body

### 3. 401 Unauthorized
**Cause**: Incorrect email or password
**Solution**: Verify credentials are correct

### 4. 403 Forbidden
**Cause**: User does not have delivery role
**Solution**: Ensure user has delivery role assigned

## Testing Methods

### Using cURL
```bash
curl -X POST http://192.168.0.238/api/delivery/login \
  -H "Content-Type: application/json" \
  -d '{"email":"dhl@gmail.com","password":"12345678"}'
```

### Using Postman
1. Set method to **POST**
2. Enter URL: `http://192.168.0.238/api/delivery/login`
3. Go to **Body** tab
4. Select **raw** and choose **JSON** from dropdown
5. Enter JSON data:
```json
{
  "email": "dhl@gmail.com",
  "password": "12345678"
}
```

## User Requirements
The user must:
1. Have a valid email and password
2. Have the 'delivery' role assigned
3. Be assigned to a carrier (carrier_id)

## Token Usage
After successful login, use the returned token for subsequent requests:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Device Token (Currently Hidden)
Device token functionality is temporarily disabled. When re-enabled, it would be sent as:
```json
{
  "email": "dhl@gmail.com",
  "password": "12345678",
  "device_token": "firebase_device_token"
}
```
