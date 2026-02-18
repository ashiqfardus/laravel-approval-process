import axios from 'axios';

class ApiClient {
    constructor() {
        this.client = axios.create({
            baseURL: '/api/approval-process',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            withCredentials: true // Important for Sanctum
        });

        // Request interceptor - handles auth headers
        this.client.interceptors.request.use(
            config => {
                // 1. Check for custom auth provider (highest priority)
                if (window.ApprovalAuth?.getHeaders) {
                    const customHeaders = window.ApprovalAuth.getHeaders();
                    Object.assign(config.headers, customHeaders);
                    return config;
                }

                // 2. Add CSRF token for web sessions
                const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    config.headers['X-CSRF-TOKEN'] = csrfToken.content;
                }

                // 3. Add Bearer token from localStorage (Sanctum/JWT/Passport)
                const token = localStorage.getItem('auth_token') ||
                    localStorage.getItem('access_token') ||
                    sessionStorage.getItem('auth_token');

                if (token) {
                    config.headers['Authorization'] = `Bearer ${token}`;
                }

                return config;
            },
            error => Promise.reject(error)
        );

        // Response interceptor - handles errors
        this.client.interceptors.response.use(
            response => response.data,
            async error => {
                console.error('API Error:', error);

                // Handle 401 Unauthorized
                if (error.response?.status === 401) {
                    // 1. Try custom auth handler first
                    if (window.ApprovalAuth?.onUnauthorized) {
                        const retry = await window.ApprovalAuth.onUnauthorized(error);
                        if (retry) {
                            // Retry the request with new auth
                            return this.client.request(error.config);
                        }
                    }

                    // 2. Default: redirect to login
                    const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/login';
                    window.location.href = loginUrl;
                }

                // Handle 403 Forbidden
                if (error.response?.status === 403) {
                    if (window.ApprovalAuth?.onForbidden) {
                        window.ApprovalAuth.onForbidden(error);
                    }
                }

                // Handle token refresh for 419 (CSRF token mismatch)
                if (error.response?.status === 419) {
                    if (window.ApprovalAuth?.onTokenMismatch) {
                        const retry = await window.ApprovalAuth.onTokenMismatch(error);
                        if (retry) {
                            return this.client.request(error.config);
                        }
                    }
                }

                return Promise.reject(error);
            }
        );
    }

    // Set auth token (for Sanctum/JWT/Passport)
    setAuthToken(token, storage = 'localStorage') {
        if (storage === 'sessionStorage') {
            sessionStorage.setItem('auth_token', token);
        } else {
            localStorage.setItem('auth_token', token);
        }
        this.client.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }

    // Clear auth token
    clearAuthToken() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('access_token');
        sessionStorage.removeItem('auth_token');
        delete this.client.defaults.headers.common['Authorization'];
    }

    // Get current token
    getAuthToken() {
        return localStorage.getItem('auth_token') ||
            localStorage.getItem('access_token') ||
            sessionStorage.getItem('auth_token');
    }

    // Stats
    async getStats() {
        return this.client.get('/dashboard/stats');
    }

    // Workflows
    async getWorkflows(params = {}) {
        return this.client.get('/workflows', { params });
    }

    async getWorkflow(id) {
        return this.client.get(`/workflows/${id}`);
    }

    async createWorkflow(data) {
        return this.client.post('/workflows', data);
    }

    async updateWorkflow(id, data) {
        return this.client.put(`/workflows/${id}`, data);
    }

    async deleteWorkflow(id) {
        return this.client.delete(`/workflows/${id}`);
    }

    // Entities
    async getEntities(params = {}) {
        return this.client.get('/entities', { params });
    }

    async createEntity(data) {
        return this.client.post('/entities', data);
    }

    async updateEntity(id, data) {
        return this.client.put(`/entities/${id}`, data);
    }

    async deleteEntity(id) {
        return this.client.delete(`/entities/${id}`);
    }

    async getConnections() {
        return this.client.get('/entities/connections');
    }

    async discoverEntities(connection = null) {
        const params = connection ? { connection } : {};
        return this.client.get('/entities/discover', { params });
    }

    // Roles
    async getRoles() {
        return this.client.get('/roles');
    }

    // Requests
    async getRequests(params = {}) {
        return this.client.get('/requests', { params });
    }

    async getRequest(id) {
        return this.client.get(`/requests/${id}`);
    }

    async approveRequest(id, data = {}) {
        return this.client.post(`/requests/${id}/approve`, data);
    }

    async rejectRequest(id, data = {}) {
        return this.client.post(`/requests/${id}/reject`, data);
    }

    async getPendingApprovals(params = {}) {
        // Return requests where current user is an approver and status is pending
        return this.client.get('/requests', {
            params: {
                status: 'pending',
                for_approval: true,
                ...params
            }
        });
    }

    async getMyRequests(params = {}) {
        // Return requests created by current user
        return this.client.get('/requests', {
            params: {
                my_requests: true,
                ...params
            }
        });
    }

    // Analytics
    async getAnalytics(type = 'overview', params = {}) {
        return this.client.get(`/analytics/${type}`, { params });
    }

    async getWorkflowAnalytics(workflowId = null) {
        const url = workflowId
            ? `/analytics/workflows/${workflowId}`
            : '/analytics/workflows';
        return this.client.get(url);
    }

    async getUserAnalytics(userId = null) {
        const url = userId
            ? `/analytics/users/${userId}`
            : '/analytics/users';
        return this.client.get(url);
    }

    // Activity Feed
    async getActivityFeed(params = {}) {
        return this.client.get('/requests', { params });
    }

    // Settings
    async getSettings() {
        return this.client.get('/settings');
    }

    async updateSettings(data) {
        return this.client.put('/settings', data);
    }
}

export default new ApiClient();
