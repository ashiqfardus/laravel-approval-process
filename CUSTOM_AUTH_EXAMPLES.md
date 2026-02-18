# Custom Authentication Examples

The admin panel now supports **ANY** authentication system through custom auth providers.

---

## Quick Start

### Method 1: Global Auth Provider (Simplest)

Add this to your HTML before loading the admin panel:

```html
<script>
window.ApprovalAuth = {
    getHeaders: () => {
        // Return headers to add to every request
        return {
            'Authorization': 'Bearer ' + localStorage.getItem('my_token')
        };
    },
    
    onUnauthorized: async (error) => {
        // Handle 401 - return true to retry, false to redirect
        return false;
    }
};
</script>
```

### Method 2: Config-based (Laravel)

```php
// config/approval-process.php
'ui' => [
    'custom_auth_script' => "
        window.ApprovalAuth = {
            getHeaders: () => ({
                'X-API-Key': '" . config('app.api_key') . "'
            })
        };
    ",
],
```

---

## Example 1: OAuth2 with Token Refresh

```javascript
window.ApprovalAuth = {
    getHeaders: () => {
        const token = localStorage.getItem('access_token');
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    },
    
    onUnauthorized: async (error) => {
        const refreshToken = localStorage.getItem('refresh_token');
        if (!refreshToken) return false;

        try {
            const response = await fetch('/oauth/token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    grant_type: 'refresh_token',
                    refresh_token: refreshToken,
                    client_id: 'your-client-id',
                    client_secret: 'your-secret'
                })
            });

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem('access_token', data.access_token);
                localStorage.setItem('refresh_token', data.refresh_token);
                return true; // Retry the failed request
            }
        } catch (err) {
            console.error('Token refresh failed:', err);
        }

        return false; // Redirect to login
    }
};
```

---

## Example 2: Custom JWT with Auto-Refresh

```javascript
window.ApprovalAuth = {
    getHeaders: () => {
        const token = localStorage.getItem('jwt_token');
        return { 'Authorization': `Bearer ${token}` };
    },
    
    onUnauthorized: async (error) => {
        try {
            const response = await fetch('/api/auth/refresh', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('jwt_token')}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem('jwt_token', data.token);
                return true; // Retry
            }
        } catch (err) {
            console.error('JWT refresh failed:', err);
        }

        // Clear token and redirect
        localStorage.removeItem('jwt_token');
        window.location.href = '/login';
        return false;
    }
};
```

---

## Example 3: API Key Authentication

```javascript
window.ApprovalAuth = {
    getHeaders: () => {
        return {
            'X-API-Key': 'your-api-key-here',
            'X-Client-ID': 'your-client-id'
        };
    },
    
    onUnauthorized: () => {
        alert('Invalid API key');
        return false;
    }
};
```

---

## Example 4: Firebase Auth

```javascript
import { getAuth } from 'firebase/auth';

window.ApprovalAuth = {
    getHeaders: async () => {
        const auth = getAuth();
        const user = auth.currentUser;
        
        if (user) {
            const token = await user.getIdToken();
            return { 'Authorization': `Bearer ${token}` };
        }
        
        return {};
    },
    
    onUnauthorized: async () => {
        // Refresh Firebase token
        const auth = getAuth();
        const user = auth.currentUser;
        
        if (user) {
            await user.getIdToken(true); // Force refresh
            return true; // Retry
        }
        
        return false; // Redirect to login
    }
};
```

---

## Example 5: AWS Cognito

```javascript
import { Auth } from 'aws-amplify';

window.ApprovalAuth = {
    getHeaders: async () => {
        try {
            const session = await Auth.currentSession();
            const token = session.getIdToken().getJwtToken();
            return { 'Authorization': `Bearer ${token}` };
        } catch (err) {
            return {};
        }
    },
    
    onUnauthorized: async () => {
        try {
            const session = await Auth.currentSession();
            return true; // Cognito auto-refreshes
        } catch (err) {
            await Auth.signOut();
            window.location.href = '/login';
            return false;
        }
    }
};
```

---

## Example 6: Auth0

```javascript
import { Auth0Client } from '@auth0/auth0-spa-js';

const auth0 = new Auth0Client({
    domain: 'your-domain.auth0.com',
    client_id: 'your-client-id'
});

window.ApprovalAuth = {
    getHeaders: async () => {
        try {
            const token = await auth0.getTokenSilently();
            return { 'Authorization': `Bearer ${token}` };
        } catch (err) {
            return {};
        }
    },
    
    onUnauthorized: async () => {
        try {
            await auth0.getTokenSilently({ ignoreCache: true });
            return true; // Retry with new token
        } catch (err) {
            await auth0.loginWithRedirect();
            return false;
        }
    }
};
```

---

## Example 7: Supabase

```javascript
import { createClient } from '@supabase/supabase-js';

const supabase = createClient('your-url', 'your-key');

window.ApprovalAuth = {
    getHeaders: async () => {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (session) {
            return { 'Authorization': `Bearer ${session.access_token}` };
        }
        
        return {};
    },
    
    onUnauthorized: async () => {
        const { data: { session } } = await supabase.auth.refreshSession();
        return !!session; // Retry if session refreshed
    }
};
```

---

## Example 8: Multiple Auth Methods

```javascript
window.ApprovalAuth = {
    getHeaders: () => {
        // Try multiple auth methods in order
        
        // 1. Bearer token
        const bearerToken = localStorage.getItem('access_token');
        if (bearerToken) {
            return { 'Authorization': `Bearer ${bearerToken}` };
        }
        
        // 2. API Key
        const apiKey = localStorage.getItem('api_key');
        if (apiKey) {
            return { 'X-API-Key': apiKey };
        }
        
        // 3. Session cookie (no headers needed)
        return {};
    },
    
    onUnauthorized: async () => {
        // Try to refresh token
        const refreshToken = localStorage.getItem('refresh_token');
        if (refreshToken) {
            // Attempt refresh...
            return true;
        }
        
        // Fallback to login
        return false;
    }
};
```

---

## Example 9: Custom CSRF Handling

```javascript
window.ApprovalAuth = {
    getHeaders: () => {
        return {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'X-Requested-With': 'XMLHttpRequest'
        };
    },
    
    onTokenMismatch: async (error) => {
        // Refresh CSRF token
        try {
            const response = await fetch('/sanctum/csrf-cookie');
            if (response.ok) {
                // Get new token from response headers or fetch page again
                const newPage = await fetch(window.location.href);
                const html = await newPage.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newToken = doc.querySelector('meta[name="csrf-token"]')?.content;
                
                if (newToken) {
                    document.querySelector('meta[name="csrf-token"]').content = newToken;
                    return true; // Retry request
                }
            }
        } catch (err) {
            console.error('CSRF refresh failed:', err);
        }
        
        return false;
    }
};
```

---

## Example 10: Session Storage (Instead of localStorage)

```javascript
window.ApprovalAuth = {
    getHeaders: () => {
        // Use sessionStorage for more security
        const token = sessionStorage.getItem('auth_token');
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    },
    
    onUnauthorized: () => {
        sessionStorage.clear();
        window.location.href = '/login';
        return false;
    }
};
```

---

## Advanced: Full Custom Provider Class

```javascript
class MyCustomAuthProvider {
    constructor() {
        this.tokenKey = 'my_custom_token';
        this.refreshing = false;
    }
    
    getHeaders() {
        const token = this.getToken();
        const headers = {};
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Add custom headers
        headers['X-Client-Version'] = '1.0.0';
        headers['X-Device-ID'] = this.getDeviceId();
        
        return headers;
    }
    
    async onUnauthorized(error) {
        // Prevent multiple simultaneous refresh attempts
        if (this.refreshing) {
            await this.waitForRefresh();
            return true;
        }
        
        this.refreshing = true;
        
        try {
            const newToken = await this.refreshToken();
            if (newToken) {
                this.setToken(newToken);
                return true; // Retry
            }
        } finally {
            this.refreshing = false;
        }
        
        return false;
    }
    
    onForbidden(error) {
        console.error('Access denied:', error);
        // Show custom error message
        alert('You do not have permission to access this resource');
    }
    
    getToken() {
        return localStorage.getItem(this.tokenKey);
    }
    
    setToken(token) {
        localStorage.setItem(this.tokenKey, token);
    }
    
    getDeviceId() {
        let deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'device_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    }
    
    async refreshToken() {
        // Your custom refresh logic
        const response = await fetch('/api/auth/refresh', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${this.getToken()}` }
        });
        
        if (response.ok) {
            const data = await response.json();
            return data.token;
        }
        
        return null;
    }
    
    async waitForRefresh() {
        // Wait for ongoing refresh to complete
        while (this.refreshing) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }
}

// Use the custom provider
window.ApprovalAuth = new MyCustomAuthProvider();
```

---

## Testing Your Auth Provider

```javascript
// Test in browser console
console.log('Headers:', await window.ApprovalAuth.getHeaders());

// Test unauthorized handler
window.ApprovalAuth.onUnauthorized({ response: { status: 401 } })
    .then(retry => console.log('Should retry:', retry));
```

---

## Default Behavior (No Custom Provider)

If you don't set `window.ApprovalAuth`, the default behavior is:

1. **Web Session**: Uses CSRF token from meta tag
2. **Bearer Token**: Checks localStorage for `auth_token` or `access_token`
3. **401 Response**: Redirects to `/login`

This works for 80% of use cases!

---

**Choose the example that matches your auth system, or create your own!**
