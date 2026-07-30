#!/bin/bash

echo "=== WTI HRMP API Testing Setup ==="
echo ""

# Check if Laravel is running
echo "1. Checking if Laravel server is running..."
if curl -s http://localhost:8000 > /dev/null; then
    echo "   ✓ Laravel server is running on http://localhost:8000"
else
    echo "   ✗ Laravel server is not running"
    echo "   Please start the server with: php artisan serve --host=0.0.0.0 --port=8000"
    echo ""
    read -p "Press Enter to continue after starting the server..."
fi

echo ""
echo "2. Testing API connectivity..."
if curl -s http://localhost:8000/api > /dev/null; then
    echo "   ✓ API is accessible"
else
    echo "   ⚠ API returned an error (this might be normal if no root handler)"
fi

echo ""
echo "3. Checking route list..."
echo "   Available API routes:"
php artisan route:list --path=api | head -20

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Import the WTI-HRMP-API-Collection.json file into Insomnia"
echo "2. Configure the environment variables in Insomnia:"
echo "   - base_url: http://localhost:8000"
echo "   - auth_token: (leave empty initially)"
echo "3. Test the login endpoint with your credentials"
echo "4. Copy the token from the response and update auth_token variable"
echo "5. Start testing other endpoints"
echo ""
echo "For detailed instructions, see API_TESTING_GUIDE.md" 