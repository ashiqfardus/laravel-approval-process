import { createRouter, createWebHashHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        component: () => import('./Layout.vue'),
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('./pages/Dashboard.vue'),
                meta: { title: 'Dashboard' }
            },
            {
                path: 'entities',
                name: 'entities',
                component: () => import('./pages/Entities.vue'),
                meta: { title: 'Entities' }
            },
            {
                path: 'workflows',
                name: 'workflows',
                component: () => import('./pages/Workflows.vue'),
                meta: { title: 'Workflows' }
            },
            {
                path: 'workflows/:id',
                name: 'workflow-detail',
                component: () => import('./pages/WorkflowDetail.vue'),
                meta: { title: 'Workflow Details' }
            },
            {
                path: 'workflows/:id/edit',
                name: 'workflow-edit',
                component: () => import('./pages/WorkflowForm.vue'),
                meta: { title: 'Edit Workflow' }
            },
            {
                path: 'workflows/create',
                name: 'workflow-create',
                component: () => import('./pages/WorkflowForm.vue'),
                meta: { title: 'Create Workflow' }
            },
            {
                path: 'requests',
                name: 'requests',
                component: () => import('./pages/Requests.vue'),
                meta: { title: 'Requests' }
            },
            {
                path: 'requests/:id',
                name: 'request-detail',
                component: () => import('./pages/RequestDetail.vue'),
                meta: { title: 'Request Details' }
            },
            {
                path: 'analytics',
                name: 'analytics',
                component: () => import('./pages/Analytics.vue'),
                meta: { title: 'Analytics' }
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('./pages/Settings.vue'),
                meta: { title: 'Settings' }
            }
        ]
    }
];

const router = createRouter({
    history: createWebHashHistory(),
    routes
});

// Update page title
router.beforeEach((to, from, next) => {
    document.title = to.meta.title ? `${to.meta.title} - Approval Process` : 'Approval Process';
    next();
});

export default router;
