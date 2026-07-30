/**
 * Test script to verify API endpoints
 * Run this in the browser console to test the endpoints
 */

console.log('=== Testing API Endpoints ===');

// Test 1: Check if dev-login endpoint exists
async function testDevLoginEndpoint() {
    try {
        console.log('Testing /api/dev-login endpoint...');
        const response = await fetch('/api/dev-login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'test@example.com',
                password: 'testpassword'
            })
        });
        
        console.log('Dev-login response status:', response.status);
        const data = await response.text();
        console.log('Dev-login response:', data);
        
        if (response.status === 404) {
            console.log('❌ /api/dev-login endpoint not found');
        } else {
            console.log('✅ /api/dev-login endpoint exists');
        }
    } catch (error) {
        console.log('❌ Error testing dev-login:', error.message);
    }
}

// Test 2: Check if login endpoint exists
async function testLoginEndpoint() {
    try {
        console.log('Testing /api/login endpoint...');
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: 'test@example.com',
                password: 'testpassword'
            })
        });
        
        console.log('Login response status:', response.status);
        const data = await response.text();
        console.log('Login response:', data);
        
        if (response.status === 404) {
            console.log('❌ /api/login endpoint not found');
        } else {
            console.log('✅ /api/login endpoint exists');
        }
    } catch (error) {
        console.log('❌ Error testing login:', error.message);
    }
}

// Test 3: Check API base URL
console.log('API Base URL:', import.meta.env.VITE_API_URL || '/api');

// Run the tests
console.log('\n=== Running Tests ===');
testDevLoginEndpoint().then(() => {
    console.log('\n');
    return testLoginEndpoint();
}).then(() => {
    console.log('\n=== Test Complete ===');
});

// Helper function to test with actual credentials
window.testDevLoginWithCredentials = async function(email, password) {
    console.log('Testing dev login with credentials:', { email, password: '***' });
    try {
        const response = await fetch('/api/dev-login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        console.log('Dev login response:', data);
        
        if (response.ok) {
            console.log('✅ Dev login successful!');
        } else {
            console.log('❌ Dev login failed:', data.message);
        }
    } catch (error) {
        console.log('❌ Dev login error:', error.message);
    }
};

console.log('\n=== Manual Test Available ===');
console.log('Run window.testDevLoginWithCredentials("asu.admin@gmail.com", "your_password") to test with actual credentials');
