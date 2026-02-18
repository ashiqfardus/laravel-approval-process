# Authentication Setup Guide

## Overview

The admin panel supports **3 authentication methods**:

1. **Web Session Auth** (Monolithic Laravel apps with Blade/Inertia)
2. **Sanctum** (SPA/Mobile apps)
3. **Passport/JWT** (OAuth2/Token-based)

---

## 1. Web Session Auth (Default for Monolithic Apps)

**Use case:** Traditional Laravel apps with Blade, Livewire, or Inertia.

### Configuration

```env
# .env
APPROVAL_API_MIDDLEWARE=web,auth
APPROVAL_ADMIN_MIDDLEWARE=web,auth
```

```php
// config/approval-process.php
'api' => [
    'middleware' => ['web', 'auth'],
],
'ui' => [
    'admin_panel_middleware' => ['web', 'auth'],
],
```

### How it works
- User logs in via `/login`
- Session cookie is created
- Admin panel and API use the same session
- No token management needed

---

## 2. Sanctum (Recommended for API-First Apps)

**Use case:** SPA (Vue/React/Next.js) or mobile apps using Sanctum.

### Setup

#### Step 1: Install Sanctum (if not already)
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

#### Step 2: Configure Sanctum
```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```

#### Step 3: Update Package Config
```env
# .env
APPROVAL_API_MIDDLEWARE=api,auth:sanctum
APPROVAL_ADMIN_MIDDLEWARE=web  # No auth middleware, handle in frontend
```

```php
// config/approval-process.php
'api' => [
    'middleware' => ['api', 'auth:sanctum'],
],
'ui' => [
    'admin_panel_middleware' => ['web'], // No auth, frontend handles it
],
```

#### Step 4: Login Flow

**Option A: Cookie-based (SPA on same domain)**
```javascript
// 1. Get CSRF cookie
await axios.get('/sanctum/csrf-cookie');

// 2. Login
const response = await axios.post('/login', {
    email: 'user@example.com',
    password: 'password'
});

// 3. Access admin panel
// Cookies are automatically sent with requests
window.location.href = '/approval-admin';
```

**Option B: Token-based (SPA on different domain or mobile)**
```javascript
// 1. Login and get token
const response = await axios.post('/api/login', {
    email: 'user@example.com',
    password: 'password'
});

const token = response.data.token;

// 2. Store token
localStorage.setItem('auth_token', token);

// 3. Access admin panel
// Token is automatically added to API requests
window.location.href = '/approval-admin';
```

---

## 3. Passport (OAuth2)

**Use case:** OAuth2 authentication for third-party integrations.

### Setup

#### Step 1: Install Passport
```bash
composer require laravel/passport
php artisan migrate
php artisan passport:install
```

#### Step 2: Configure Package
```env
# .env
APPROVAL_API_MIDDLEWARE=api,auth:api
APPROVAL_ADMIN_MIDDLEWARE=web
```

```php
// config/approval-process.php
'api' => [
    'middleware' => ['api', 'auth:api'],
],
'ui' => [
    'admin_panel_middleware' => ['web'],
],
```

#### Step 3: Login Flow
```javascript
// 1. Get OAuth token
const response = await axios.post('/oauth/token', {
    grant_type: 'password',
    client_id: 'your-client-id',
    client_secret: 'your-client-secret',
    username: 'user@example.com',
    password: 'password',
    scope: ''
});

const token = response.data.access_token;

// 2. Store token
localStorage.setItem('access_token', token);

// 3. Access admin panel
window.location.href = '/approval-admin';
```

---

## 4. Custom JWT

**Use case:** Using tymon/jwt-auth or custom JWT implementation.

### Setup

```env
# .env
APPROVAL_API_MIDDLEWARE=api,jwt.auth
APPROVAL_ADMIN_MIDDLEWARE=web
```

```php
// config/approval-process.php
'api' => [
    'middleware' => ['api', 'jwt.auth'], // Your JWT middleware
],
```

### Login Flow
```javascript
// 1. Login and get JWT
const response = await axios.post('/api/auth/login', {
    email: 'user@example.com',
    password: 'password'
});

const token = response.data.access_token;

// 2. Store token
localStorage.setItem('auth_token', token);

// 3. Access admin panel
window.location.href = '/approval-admin';
```

---

## How the Admin Panel Handles Auth

### API Client Auto-Detection

The API client automatically detects and uses the appropriate auth method:

```javascript
// resources/js/core/api/client.js

// 1. Checks for CSRF token (web session)
const csrfToken = document.querySelector('meta[name="csrf-token"]');

// 2. Checks for Bearer token (Sanctum/JWT/Passport)
const bearerToken = localStorage.getItem('auth_token') || localStorage.getItem('access_token');

// 3. Adds appropriate headers
if (csrfToken) {
    headers['X-CSRF-TOKEN'] = csrfToken.content;
}
if (bearerToken) {
    headers['Authorization'] = `Bearer ${bearerToken}`;
}
```

### Token Management

```javascript
import apiClient from '@core/api/client.js';

// Set token after login
apiClient.setAuthToken(token);

// Clear token on logout
apiClient.clearAuthToken();
```

---

## Recommended Setups

### Monolithic App (Blade/Livewire/Inertia)
```env
APPROVAL_API_MIDDLEWARE=web,auth
APPROVAL_ADMIN_MIDDLEWARE=web,auth
```
✅ Simple, works out of the box

### SPA on Same Domain (Vue/React with Sanctum)
```env
APPROVAL_API_MIDDLEWARE=api,auth:sanctum
APPROVAL_ADMIN_MIDDLEWARE=web
```
✅ Cookie-based, no token management

### SPA on Different Domain (Vue/React with Sanctum)
```env
APPROVAL_API_MIDDLEWARE=api,auth:sanctum
APPROVAL_ADMIN_MIDDLEWARE=web
```
✅ Token-based, store in localStorage

### Mobile App (Sanctum/Passport)
```env
APPROVAL_API_MIDDLEWARE=api,auth:sanctum
APPROVAL_ADMIN_MIDDLEWARE=web
```
✅ Token-based, secure storage

### Microservices (JWT)
```env
APPROVAL_API_MIDDLEWARE=api,jwt.auth
APPROVAL_ADMIN_MIDDLEWARE=web
```
✅ Stateless, works across services

---

## Troubleshooting

### 401 Unauthorized

**Web Session:**
- Make sure user is logged in
- Check session is active
- Verify CSRF token is present

**Sanctum:**
- Check token is stored: `localStorage.getItem('auth_token')`
- Verify Sanctum is configured correctly
- Check `withCredentials: true` in axios

**Passport/JWT:**
- Verify token is valid
- Check token is in localStorage
- Ensure Authorization header is sent

### CORS Issues (SPA on different domain)

```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'supports_credentials' => true,
```

### Token Not Sent

Check browser console:
```javascript
// Should see Authorization header
console.log(apiClient.client.defaults.headers.common);
```

---

## Security Best Practices

1. **Always use HTTPS in production**
2. **Set secure cookie flags for Sanctum**
3. **Use short token expiration times**
4. **Implement token refresh logic**
5. **Don't store tokens in localStorage for sensitive apps** (use httpOnly cookies instead)
6. **Add rate limiting to auth endpoints**
7. **Implement CSRF protection for web sessions**

---

## Example: Complete Sanctum Setup

```php
// routes/api.php
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('admin-panel')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user
    ]);
});

Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out']);
})->middleware('auth:sanctum');
```

```javascript
// Login component
async function login(email, password) {
    try {
        const response = await axios.post('/api/login', { email, password });
        
        // Store token
        localStorage.setItem('auth_token', response.data.token);
        
        // Redirect to admin panel
        window.location.href = '/approval-admin';
    } catch (error) {
        console.error('Login failed:', error);
    }
}

// Logout
async function logout() {
    await axios.post('/api/logout');
    localStorage.removeItem('auth_token');
    window.location.href = '/login';
}
```

---

**Choose the setup that matches your application architecture!**
