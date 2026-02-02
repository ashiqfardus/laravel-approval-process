/**
 * Approval Process Package - JavaScript Components
 */

class ApprovalProcess {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/api/approval-process';
        this.token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
    }

    setupEventListeners() {
        // Approve button
        document.querySelectorAll('[data-action="approve"]').forEach(btn => {
            btn.addEventListener('click', (e) => this.approveRequest(e));
        });

        // Reject button
        document.querySelectorAll('[data-action="reject"]').forEach(btn => {
            btn.addEventListener('click', (e) => this.rejectRequest(e));
        });

        // Send back button
        document.querySelectorAll('[data-action="send-back"]').forEach(btn => {
            btn.addEventListener('click', (e) => this.sendBackRequest(e));
        });

        // Hold button
        document.querySelectorAll('[data-action="hold"]').forEach(btn => {
            btn.addEventListener('click', (e) => this.holdRequest(e));
        });

        // Filter buttons
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', (e) => this.filterRequests(e));
        });
    }

    initializeComponents() {
        // Initialize any Vue/React components here if needed
        this.initializeTimeline();
    }

    initializeTimeline() {
        const timelines = document.querySelectorAll('.approval-timeline');
        timelines.forEach(timeline => {
            this.setupTimelineAnimation(timeline);
        });
    }

    setupTimelineAnimation(timeline) {
        const items = timeline.querySelectorAll('.timeline-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('approval-fade-in');
            }, index * 150);
        });
    }

    async approveRequest(event) {
        event.preventDefault();
        const requestId = event.target.closest('[data-request-id]')?.getAttribute('data-request-id');
        const remarks = document.querySelector('[name="remarks"]')?.value;

        try {
            const response = await this.apiCall(`/requests/${requestId}/approve`, 'POST', {
                remarks: remarks
            });

            this.showNotification('Request approved successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showNotification('Failed to approve request: ' + error.message, 'error');
        }
    }

    async rejectRequest(event) {
        event.preventDefault();
        const requestId = event.target.closest('[data-request-id]')?.getAttribute('data-request-id');
        const reason = document.querySelector('[name="rejection_reason"]')?.value;
        const remarks = document.querySelector('[name="remarks"]')?.value;

        if (!reason) {
            this.showNotification('Please provide a rejection reason', 'warning');
            return;
        }

        try {
            const response = await this.apiCall(`/requests/${requestId}/reject`, 'POST', {
                reason: reason,
                remarks: remarks
            });

            this.showNotification('Request rejected successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showNotification('Failed to reject request: ' + error.message, 'error');
        }
    }

    async sendBackRequest(event) {
        event.preventDefault();
        const requestId = event.target.closest('[data-request-id]')?.getAttribute('data-request-id');
        const remarks = document.querySelector('[name="remarks"]')?.value;

        try {
            const response = await this.apiCall(`/requests/${requestId}/send-back`, 'POST', {
                remarks: remarks
            });

            this.showNotification('Request sent back successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showNotification('Failed to send back request: ' + error.message, 'error');
        }
    }

    async holdRequest(event) {
        event.preventDefault();
        const requestId = event.target.closest('[data-request-id]')?.getAttribute('data-request-id');
        const remarks = document.querySelector('[name="remarks"]')?.value;

        try {
            const response = await this.apiCall(`/requests/${requestId}/hold`, 'POST', {
                remarks: remarks
            });

            this.showNotification('Request put on hold!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showNotification('Failed to hold request: ' + error.message, 'error');
        }
    }

    async filterRequests(event) {
        event.preventDefault();
        const status = document.querySelector('[name="status"]')?.value;
        const startDate = document.querySelector('[name="start_date"]')?.value;
        const endDate = document.querySelector('[name="end_date"]')?.value;

        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);

        try {
            const response = await this.apiCall(`/requests?${params.toString()}`, 'GET');
            this.updateRequestsTable(response.data);
        } catch (error) {
            this.showNotification('Failed to filter requests: ' + error.message, 'error');
        }
    }

    updateRequestsTable(requests) {
        const tbody = document.querySelector('.approval-table tbody');
        if (!tbody) return;

        tbody.innerHTML = requests.map(request => `
            <tr>
                <td>${request.id}</td>
                <td>${request.requestable_type}</td>
                <td>${request.requested_by}</td>
                <td>${request.current_step?.name || 'N/A'}</td>
                <td>
                    <span class="approval-badge status-${request.status}">
                        ${this.formatStatus(request.status)}
                    </span>
                </td>
                <td>${this.formatDate(request.created_at)}</td>
                <td>
                    <a href="/approval-process/requests/${request.id}" class="text-blue-600">View</a>
                </td>
            </tr>
        `).join('');
    }

    async apiCall(endpoint, method = 'GET', data = null) {
        const url = `${this.apiUrl}${endpoint}`;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.token,
                'Accept': 'application/json'
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }

        return response.json();
    }

    formatStatus(status) {
        const statuses = {
            'approved': '✓ Approved',
            'rejected': '✗ Rejected',
            'pending': '⏳ Pending',
            'in-review': '👁 In Review',
            'draft': '📝 Draft'
        };
        return statuses[status] || status;
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `approval-notification approval-notification-${type}`;
        notification.textContent = message;

        const styles = {
            'success': 'background-color: #dcfce7; color: #166534; border-left: 4px solid #10b981;',
            'error': 'background-color: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444;',
            'warning': 'background-color: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b;',
            'info': 'background-color: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6;'
        };

        notification.style.cssText = `
            ${styles[type] || styles['info']}
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            animation: slideIn 0.3s ease-out;
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            z-index: 9999;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 4000);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.ApprovalProcess = new ApprovalProcess();
});
