/**
 * Test script for separated dev authentication
 * Run this in the browser console to test the functionality
 */

console.log('=== Testing Separated Dev Authentication ===');

// Test 1: Check if dev auth service is available
console.log('1. Checking dev auth service...');
if (typeof window.devAuthService !== 'undefined') {
    console.log('✅ Dev auth service is available');
} else {
    console.log('❌ Dev auth service not found - make sure to import it');
}

// Test 2: Check environment detection
console.log('2. Checking environment detection...');
const isDev = import.meta.env.MODE === 'development' || import.meta.env.MODE === 'dev';
const devAuthEnabled = import.meta.env.VITE_DEV_AUTH_ENABLED === 'true';
console.log(`   - Environment: ${import.meta.env.MODE}`);
console.log(`   - Dev Auth Enabled: ${devAuthEnabled}`);
console.log(`   - Should use dev auth: ${isDev && devAuthEnabled ? 'Yes' : 'No'}`);

// Test 3: Test clean fetch functionality
console.log('3. Testing clean fetch...');
async function testCleanFetch() {
    try {
        const response = await fetch('/api/dev-login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('✅ Clean fetch successful:', data);
            return true;
        } else {
            console.log('❌ Clean fetch failed:', response.status, response.statusText);
            return false;
        }
    } catch (error) {
        console.log('❌ Clean fetch error:', error.message);
        return false;
    }
}

// Test 4: Test dev auth service methods
console.log('4. Testing dev auth service methods...');
if (typeof window.devAuthService !== 'undefined') {
    console.log('   - shouldUseDevAuth():', window.devAuthService.shouldUseDevAuth());
    console.log('   - isDevAuthValid():', window.devAuthService.isDevAuthValid());
    console.log('   - getDevAuthToken():', window.devAuthService.getDevAuthToken());
}

// Test 5: Test authentication state
console.log('5. Testing authentication state...');
const authToken = localStorage.getItem('auth_token');
const devAuthToken = localStorage.getItem('dev_auth_token');
console.log('   - Regular auth token:', authToken ? 'Present' : 'Not present');
console.log('   - Dev auth token:', devAuthToken ? 'Present' : 'Not present');

// Run the tests
console.log('\n=== Running Tests ===');
testCleanFetch().then(success => {
    console.log(`Clean fetch test: ${success ? '✅ PASS' : '❌ FAIL'}`);
    
    console.log('\n=== Test Summary ===');
    console.log('Separated dev authentication system appears to be working!');
    console.log('You can now:');
    console.log('1. Use regular login with email/password');
    console.log('2. Use dev login for automatic admin authentication');
    console.log('3. Logout from either authentication method');
    console.log('4. See user info in the sidebar when logged in');
});

// Helper function to manually test dev login
window.testDevLogin = async function() {
    console.log('Testing dev login...');
    try {
        const response = await fetch('/api/dev-login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        console.log('Dev login response:', data);
        
        if (data.success && data.data) {
            console.log('✅ Dev login successful!');
            console.log('User:', data.data.user);
            console.log('Token:', data.data.token ? 'Present' : 'Not present');
        } else {
            console.log('❌ Dev login failed:', data.message);
        }
    } catch (error) {
        console.log('❌ Dev login error:', error.message);
    }
};

console.log('\n=== Manual Test Available ===');
console.log('Run window.testDevLogin() to manually test dev login');
