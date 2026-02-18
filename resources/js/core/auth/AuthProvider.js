/**
 * Custom Auth Provider Interface
 * 
 * Allows users to implement custom authentication logic
 * for any auth system (OAuth2, custom JWT, API keys, etc.)
 */

export class AuthProvider {
    /**
     * Get authentication headers
     * Called before every API request
     * 
     * @returns {Object} Headers to add to request
     */
    getHeaders() {
        return {};
    }

    /**
     * Handle 401 Unauthorized response
     * Called when API returns 401
     * 
     * @param {Error} error - The error object
     * @returns {boolean} - Return true to retry request, false to redirect
     */
    async onUnauthorized(error) {
        return false;
    }

    /**
     * Handle 403 Forbidden response
     * Called when API returns 403
     * 
     * @param {Error} error - The error object
     */
    onForbidden(error) {
        console.error('Access forbidden:', error);
    }

    /**
     * Handle 419 CSRF Token Mismatch
     * Called when CSRF token is invalid
     * 
     * @param {Error} error - The error object
     * @returns {boolean} - Return true to retry request
     */
    async onTokenMismatch(error) {
        return false;
    }
}

/**
 * Example: OAuth2 with Token Refresh
 */
export class OAuth2Provider extends AuthProvider {
    constructor(config = {}) {
        super();
        this.tokenKey = config.tokenKey || 'access_token';
        this.refreshTokenKey = config.refreshTokenKey || 'refresh_token';
        this.refreshEndpoint = config.refreshEndpoint || '/oauth/token';
    }

    getHeaders() {
        const token = localStorage.getItem(this.tokenKey);
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    }

    async onUnauthorized(error) {
        const refreshToken = localStorage.getItem(this.refreshTokenKey);
        if (!refreshToken) return false;

        try {
            // Attempt to refresh token
            const response = await fetch(this.refreshEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    grant_type: 'refresh_token',
                    refresh_token: refreshToken
                })
            });

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem(this.tokenKey, data.access_token);
                if (data.refresh_token) {
                    localStorage.setItem(this.refreshTokenKey, data.refresh_token);
                }
                return true; // Retry the request
            }
        } catch (err) {
            console.error('Token refresh failed:', err);
        }

        return false; // Redirect to login
    }
}

/**
 * Example: Custom API Key Authentication
 */
export class ApiKeyProvider extends AuthProvider {
    constructor(apiKey, headerName = 'X-API-Key') {
        super();
        this.apiKey = apiKey;
        this.headerName = headerName;
    }

    getHeaders() {
        return { [this.headerName]: this.apiKey };
    }
}

/**
 * Example: JWT with Auto-Refresh
 */
export class JWTProvider extends AuthProvider {
    constructor(config = {}) {
        super();
        this.tokenKey = config.tokenKey || 'jwt_token';
        this.refreshEndpoint = config.refreshEndpoint || '/api/auth/refresh';
    }

    getHeaders() {
        const token = localStorage.getItem(this.tokenKey);
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    }

    async onUnauthorized(error) {
        try {
            const response = await fetch(this.refreshEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem(this.tokenKey)}`
                }
            });

            if (response.ok) {
                const data = await response.json();
                localStorage.setItem(this.tokenKey, data.token);
                return true; // Retry
            }
        } catch (err) {
            console.error('JWT refresh failed:', err);
        }

        return false;
    }
}

/**
 * Example: Sanctum with CSRF
 */
export class SanctumProvider extends AuthProvider {
    constructor(config = {}) {
        super();
        this.tokenKey = config.tokenKey || 'sanctum_token';
        this.csrfEndpoint = config.csrfEndpoint || '/sanctum/csrf-cookie';
    }

    getHeaders() {
        const headers = {};
        
        // Add Bearer token if exists
        const token = localStorage.getItem(this.tokenKey);
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.content;
        }

        return headers;
    }

    async onTokenMismatch(error) {
        try {
            // Refresh CSRF token
            await fetch(this.csrfEndpoint, {
                credentials: 'include'
            });

            // Update meta tag
            const response = await fetch('/');
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newToken = doc.querySelector('meta[name="csrf-token"]');
            
            if (newToken) {
                const existingMeta = document.querySelector('meta[name="csrf-token"]');
                if (existingMeta) {
                    existingMeta.content = newToken.content;
                }
                return true; // Retry
            }
        } catch (err) {
            console.error('CSRF refresh failed:', err);
        }

        return false;
    }
}
