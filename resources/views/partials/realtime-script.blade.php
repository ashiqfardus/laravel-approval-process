{{-- Real-time updates using Laravel Echo and Pusher --}}
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
    // Initialize Laravel Echo
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: '{{ config("broadcasting.connections.pusher.key") }}',
        cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
        forceTLS: true,
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }
    });

    // Real-time notification system
    const notifications = {
        show(message, type = 'info') {
            const colors = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700'
            };

            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${colors[type]} px-4 py-3 rounded shadow-lg z-50 max-w-md animate-slide-in`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 font-bold">&times;</button>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => notification.remove(), 5000);
        }
    };

    // Listen to approval request updates
    Echo.channel('approval-requests')
        .listen('.approval.request.updated', (e) => {
            console.log('Approval request updated:', e);
            
            // Show notification
            const actions = {
                'submitted': 'submitted',
                'approved': 'approved',
                'rejected': 'rejected',
                'sent_back': 'sent back',
                'held': 'put on hold',
                'cancelled': 'cancelled'
            };
            
            const action = actions[e.action] || 'updated';
            notifications.show(`Request #${e.request_id} has been ${action}`, 
                e.action === 'approved' ? 'success' : 
                e.action === 'rejected' ? 'error' : 'info'
            );

            // Update UI if we're on the requests page
            if (window.location.pathname.includes('/requests')) {
                // Reload the page or update specific elements
                setTimeout(() => window.location.reload(), 1000);
            }
        });

    // Listen to workflow updates
    Echo.channel('workflows')
        .listen('.workflow.updated', (e) => {
            console.log('Workflow updated:', e);
            notifications.show(`Workflow "${e.workflow_name}" has been ${e.change_type}`, 'info');
        });

    // Function to join specific request channel
    function joinRequestChannel(requestId) {
        Echo.private(`approval-request.${requestId}`)
            .listen('.approval.action.taken', (e) => {
                console.log('Action taken on request:', e);
                notifications.show(`New action: ${e.action_type}`, 'info');
                
                // Update timeline if present
                const timeline = document.getElementById('approval-timeline');
                if (timeline) {
                    // Reload timeline or append new action
                    window.location.reload();
                }
            })
            .listen('.approval.request.updated', (e) => {
                console.log('Request status changed:', e);
                
                // Update status badge
                const statusBadge = document.getElementById('request-status');
                if (statusBadge) {
                    statusBadge.textContent = e.status;
                    statusBadge.className = getStatusBadgeClass(e.status);
                }
            });
    }

    // Function to join workflow channel
    function joinWorkflowChannel(workflowId) {
        Echo.join(`workflow.${workflowId}`)
            .here((users) => {
                console.log('Users currently viewing workflow:', users);
                updateActiveUsers(users);
            })
            .joining((user) => {
                console.log('User joined:', user);
                notifications.show(`${user.name} is now viewing this workflow`, 'info');
                updateActiveUsers();
            })
            .leaving((user) => {
                console.log('User left:', user);
                updateActiveUsers();
            });
    }

    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
        const classes = {
            'pending': 'px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-800',
            'submitted': 'px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800',
            'in_progress': 'px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800',
            'approved': 'px-3 py-1 text-xs rounded-full bg-green-100 text-green-800',
            'rejected': 'px-3 py-1 text-xs rounded-full bg-red-100 text-red-800',
            'sent_back': 'px-3 py-1 text-xs rounded-full bg-orange-100 text-orange-800',
            'held': 'px-3 py-1 text-xs rounded-full bg-purple-100 text-purple-800',
            'cancelled': 'px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-800'
        };
        return classes[status] || classes['pending'];
    }

    // Helper function to update active users display
    function updateActiveUsers(users = []) {
        const container = document.getElementById('active-users');
        if (!container) return;

        if (users.length === 0) {
            container.innerHTML = '<span class="text-gray-500 text-sm">No other users viewing</span>';
        } else {
            container.innerHTML = users.map(user => `
                <div class="flex items-center space-x-2 text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span>${user.name}</span>
                    <span class="text-gray-500">(${user.role || 'viewer'})</span>
                </div>
            `).join('');
        }
    }

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
</script>
