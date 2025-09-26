<?php
// Simple test to verify delivery login route

echo "Delivery Login Route Test\n";
echo "========================\n\n";

echo "Route registered: POST /api/delivery/login\n";
echo "Controller: App\Http\Controllers\Api\Delivery\AuthController@login\n\n";

echo "To test this route correctly:\n";
echo "1. Use POST method (not GET)\n";
echo "2. Send data as JSON in request body (not query parameters)\n";
echo "3. Include email and password fields in JSON\n\n";

echo "Example JSON payload:\n";
echo "{\n";
echo "  \"email\": \"dhl@gmail.com\",\n";
echo "  \"password\": \"12345678\"\n";
echo "}\n\n";

echo "Expected response format:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Login successful\",\n";
echo "  \"data\": {\n";
echo "    \"user\": {\n";
echo "      \"id\": 1,\n";
echo "      \"name\": \"username\",\n";
echo "      \"email\": \"dhl@gmail.com\",\n";
echo "      \"phone\": \"phone_number\",\n";
echo "      \"avatar\": \"avatar_url\",\n";
echo "      \"carrier_id\": 1,\n";
echo "      \"carrier_name\": \"Carrier Name\"\n";
echo "    },\n";
echo "    \"token\": \"access_token\"\n";
echo "  }\n";
echo "}\n";
