# Approval Process Admin Panel - Laravel Horizon Style

## Vision
Create a **standalone, professional admin panel** similar to Laravel Horizon/Telescope for managing the approval process package. This panel should be completely independent, beautiful, and require zero configuration.

---

## Architecture

### 1. Standalone SPA Admin Panel
**URL:** `/approval-admin`

**Technology Stack:**
- **Backend:** Laravel API routes (already exist)
- **Frontend:** Vue.js 3 + Vite (like Horizon)
- **Styling:** Tailwind CSS
- **State Management:** Pinia or Vue Composition API
- **Charts:** Chart.js or ApexCharts
- **Real-time:** Laravel Echo + Reverb

**Key Principle:** 
- Self-contained SPA that doesn't depend on host application's layout
- All assets bundled with package
- Works out of the box after `composer require`

---

## Features & Pages

### 1. Dashboard (Home)
**Route:** `/approval-admin`

**Sections:**
- **Stats Overview Cards:**
  - Total Workflows (active/inactive)
  - Pending Approvals (with trend)
  - Approval Rate (%)
  - Average Processing Time
  - Overdue Requests (alert badge)

- **Real-time Activity Feed:**
  - Live updates of approval actions
  - User avatars, timestamps
  - Action types (approved, rejected, submitted)

- **Quick Actions Grid:**
  - Create Workflow
  - Add Entity
  - View Pending Approvals
  - Generate Report

- **Charts:**
  - Approval trends (last 30 days)
  - Workflow performance comparison
  - Status distribution pie chart

### 2. Entities Management
**Route:** `/approval-admin#/entities`

**Features:**
- **Auto-Discovery Panel:**
  - Scan button to discover models
  - Scan button to discover tables (per connection)
  - Preview list before adding

- **Entity List Table:**
  - Type badge (Model/Table)
  - Name with syntax highlighting
  - Label (editable inline)
  - Connection dropdown
  - Status toggle (active/inactive)
  - Actions: Edit, Delete, Test Connection

- **Add Entity Modal:**
  - Type selector (Model/Table)
  - Auto-complete for discovered items
  - Manual input option
  - Connection selector (with test button)
  - Label and description
  - Save & Add Another option

- **Multi-Database Support:**
  - Connection dropdown shows all configured connections
  - Test connection button
  - Visual indicator for connection status
  - Format: `table:orders@sales` or `App\Models\Order@production`

### 3. Workflows Management
**Route:** `/approval-admin#/workflows`

**Features:**
- **Workflow List:**
  - Card-based layout (like Horizon jobs)
  - Name, description, entity
  - Steps count with visual indicator
  - Status toggle
  - Active requests count
  - Actions: View, Edit, Clone, Delete, Designer

- **Visual Workflow Designer:**
  - Drag-and-drop canvas
  - Step nodes with connectors
  - Approver assignment
  - Weightage configuration (for parallel)
  - Condition builder
  - Real-time validation
  - Export/Import JSON

- **Create/Edit Workflow:**
  - Step-by-step wizard:
    1. Basic Info (name, entity, description)
    2. Add Steps (sequence, type, SLA)
    3. Assign Approvers (with weightage)
    4. Configure Conditions (optional)
    5. Review & Save

- **Workflow Templates:**
  - Pre-built templates (3-level approval, parallel, conditional)
  - Clone from existing
  - Import from JSON

### 4. Approval Requests
**Route:** `/approval-admin#/requests`

**Features:**
- **Request List:**
  - Filterable table (status, workflow, date range, user)
  - Search by ID, title, requester
  - Bulk actions (approve, reject, reassign)
  - Export to CSV/Excel

- **Request Detail View:**
  - Full request information
  - Timeline visualization (vertical)
  - Approval chain with avatars
  - Weightage progress bar (for parallel)
  - Action buttons (if user is approver)
  - Audit log
  - Attachments viewer
  - Comments/notes section

- **My Approvals:**
  - Pending items requiring action
  - Priority sorting
  - Quick approve/reject
  - Batch processing

### 5. Analytics & Reports
**Route:** `/approval-admin#/analytics`

**Features:**
- **Workflow Performance:**
  - Completion rate per workflow
  - Average time per step
  - Bottleneck identification
  - Success rate trends

- **User Performance:**
  - Approvals given (per user)
  - Average response time
  - Rejection rate
  - Leaderboard

- **System Health:**
  - Overdue requests
  - SLA compliance rate
  - Peak usage times
  - Database connection status

- **Custom Reports:**
  - Report builder UI
  - Save custom queries
  - Schedule reports
  - Export options

### 6. Settings
**Route:** `/approval-admin#/settings`

**Sections:**
- **General:**
  - Package version
  - UI enabled/disabled
  - Layout configuration
  - Items per page

- **Notifications:**
  - Email settings
  - Real-time (Reverb/Pusher)
  - SMS (optional)
  - Webhook URLs

- **Security:**
  - Require reason on reject
  - IP logging
  - Signature verification
  - Audit log retention

- **SLA Configuration:**
  - Working days/hours
  - Holidays
  - Auto-escalation rules
  - Reminder schedules

- **Advanced:**
  - Cache settings
  - Queue configuration
  - Database connections
  - Feature flags

---

## Technical Implementation

### Directory Structure
```
src/
├── AdminPanel/
│   ├── Controllers/
│   │   └── AdminPanelController.php (serves SPA)
│   ├── Resources/
│   │   ├── js/
│   │   │   ├── app.js (Vue app entry)
│   │   │   ├── router.js (Vue Router)
│   │   │   ├── store.js (Pinia store)
│   │   │   ├── components/
│   │   │   │   ├── Dashboard.vue
│   │   │   │   ├── Entities/
│   │   │   │   │   ├── EntityList.vue
│   │   │   │   │   ├── EntityForm.vue
│   │   │   │   │   └── EntityDiscovery.vue
│   │   │   │   ├── Workflows/
│   │   │   │   │   ├── WorkflowList.vue
│   │   │   │   │   ├── WorkflowForm.vue
│   │   │   │   │   ├── WorkflowDesigner.vue
│   │   │   │   │   └── StepBuilder.vue
│   │   │   │   ├── Requests/
│   │   │   │   │   ├── RequestList.vue
│   │   │   │   │   ├── RequestDetail.vue
│   │   │   │   │   └── ApprovalTimeline.vue
│   │   │   │   ├── Analytics/
│   │   │   │   │   ├── WorkflowAnalytics.vue
│   │   │   │   │   ├── UserAnalytics.vue
│   │   │   │   │   └── SystemHealth.vue
│   │   │   │   └── Settings/
│   │   │   │       └── SettingsPanel.vue
│   │   │   └── shared/
│   │   │       ├── Layout.vue
│   │   │       ├── Navbar.vue
│   │   │       ├── Sidebar.vue
│   │   │       └── StatusBadge.vue
│   │   ├── css/
│   │   │   └── admin-panel.css
│   │   └── views/
│   │       └── admin-panel.blade.php (SPA container)
│   └── vite.config.js
└── routes/
    └── admin.php (admin panel routes)
```

### Build Process
```bash
# Development
npm install
npm run dev

# Production
npm run build
```

### Asset Publishing
```php
// In ServiceProvider
$this->publishes([
    __DIR__.'/../dist' => public_path('vendor/approval-process'),
], 'approval-admin-assets');
```

---

## User Experience Flow

### First Time Setup
1. Install package: `composer require ashiqfardus/laravel-approval-process`
2. Run migrations: `php artisan migrate`
3. Visit: `/approval-admin`
4. See welcome screen with setup wizard:
   - Step 1: Add entities (auto-discover or manual)
   - Step 2: Create first workflow
   - Step 3: Configure notifications
   - Step 4: Done! Start using

### Daily Usage
1. Visit `/approval-admin`
2. Dashboard shows overview
3. Navigate via sidebar (always visible)
4. All actions stay within admin panel
5. Real-time updates via WebSocket
6. No page reloads (SPA)

---

## Design Principles

### 1. Zero Configuration
- Works immediately after installation
- Auto-discovers models and tables
- Sensible defaults
- Optional customization

### 2. Beautiful & Professional
- Modern, clean design
- Consistent with Laravel ecosystem
- Dark mode support
- Responsive (mobile-friendly)
- Smooth animations

### 3. Self-Contained
- No dependency on host app layout
- Bundled assets
- Own authentication (configurable)
- Independent routing

### 4. Developer Friendly
- Clear API
- Extensible components
- Well-documented
- Easy to customize

### 5. Production Ready
- Optimized builds
- Lazy loading
- Error handling
- Performance monitoring

---

## API Endpoints (Already Exist)

All API routes are already implemented at `/api/approval-process/*`:
- ✅ Workflows CRUD
- ✅ Steps management
- ✅ Approvers & weightage
- ✅ Requests & actions
- ✅ Analytics & reports
- ✅ Notifications
- ✅ Delegations
- ✅ Attachments
- ✅ Signatures

**Frontend just needs to consume these APIs!**

---

## Implementation Phases

### Phase 1: Core Infrastructure (2-3 hours)
- [ ] Set up Vite + Vue 3
- [ ] Create base layout (Navbar + Sidebar)
- [ ] Implement routing (Vue Router)
- [ ] Set up API client (Axios)
- [ ] Add Tailwind CSS
- [ ] Create main blade template

### Phase 2: Dashboard (1-2 hours)
- [ ] Stats cards with API integration
- [ ] Activity feed with real-time updates
- [ ] Quick action buttons
- [ ] Charts (Chart.js integration)

### Phase 3: Entity Management (2-3 hours)
- [ ] Entity list table
- [ ] Auto-discovery feature
- [ ] Add/Edit entity modal
- [ ] Multi-database connection selector
- [ ] Test connection feature
- [ ] CRUD operations

### Phase 4: Workflow Management (3-4 hours)
- [ ] Workflow list (card layout)
- [ ] Create workflow wizard
- [ ] Edit workflow form
- [ ] Step builder component
- [ ] Approver assignment UI
- [ ] Weightage configuration
- [ ] Clone workflow feature

### Phase 5: Visual Designer (4-5 hours)
- [ ] Canvas component
- [ ] Drag-and-drop nodes
- [ ] Connection lines
- [ ] Step configuration panel
- [ ] Condition builder
- [ ] Save/Load workflow
- [ ] Export/Import JSON

### Phase 6: Request Management (2-3 hours)
- [ ] Request list with filters
- [ ] Request detail view
- [ ] Timeline visualization
- [ ] Approval actions
- [ ] Bulk operations
- [ ] Search functionality

### Phase 7: Analytics (2-3 hours)
- [ ] Workflow analytics charts
- [ ] User performance metrics
- [ ] System health dashboard
- [ ] Custom report builder
- [ ] Export functionality

### Phase 8: Settings (1-2 hours)
- [ ] General settings form
- [ ] Notification configuration
- [ ] Security settings
- [ ] SLA configuration
- [ ] Feature flags

### Phase 9: Polish & Optimization (2-3 hours)
- [ ] Dark mode
- [ ] Loading states
- [ ] Error handling
- [ ] Responsive design
- [ ] Performance optimization
- [ ] Build for production

---

## Comparison: Current vs. Horizon-Style

### Current Approach (Blade + iframes)
❌ Multiple layouts
❌ Page reloads
❌ Iframe issues
❌ Inconsistent UX
❌ Hard to maintain

### Horizon-Style Approach (SPA)
✅ Single page application
✅ No page reloads
✅ Consistent design
✅ Real-time updates
✅ Professional UX
✅ Easy to extend
✅ Better performance

---

## Example: Laravel Horizon Structure

```
horizon/
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── routes.js
│   │   ├── screens/
│   │   │   ├── Dashboard.vue
│   │   │   ├── Jobs.vue
│   │   │   ├── Batches.vue
│   │   │   └── Monitoring.vue
│   │   └── components/
│   └── css/
│       └── app.css
├── dist/ (built assets)
└── views/
    └── layout.blade.php (SPA container)
```

**We'll follow the same pattern!**

---

## Benefits

### For Package Users
- ✅ Professional admin interface out of the box
- ✅ No need to build custom UI
- ✅ Consistent experience across projects
- ✅ Easy updates (just update package)

### For Developers
- ✅ Clear separation of concerns
- ✅ Modern frontend stack
- ✅ Easy to customize
- ✅ Well-documented

### For End Users
- ✅ Beautiful, intuitive interface
- ✅ Fast, responsive
- ✅ Real-time updates
- ✅ Mobile-friendly

---

## Configuration

### Minimal Config Required
```php
// config/approval-process.php
'admin_panel' => [
    'enabled' => true,
    'path' => '/approval-admin',
    'middleware' => ['web', 'auth'],
    'brand' => 'My Company',
    'logo' => null, // Custom logo URL
],
```

### Optional Customization
```php
'admin_panel' => [
    'theme' => [
        'primary_color' => '#4F46E5', // Indigo
        'dark_mode' => true,
    ],
    'features' => [
        'visual_designer' => true,
        'analytics' => true,
        'reports' => true,
    ],
    'permissions' => [
        'manage_workflows' => 'manage-workflows',
        'manage_entities' => 'manage-entities',
        'view_analytics' => 'view-analytics',
    ],
],
```

---

## Development Workflow

### 1. Set Up Frontend
```bash
cd src/AdminPanel/Resources
npm install
npm run dev
```

### 2. Build for Production
```bash
npm run build
# Assets compiled to dist/
```

### 3. Publish Assets
```bash
php artisan vendor:publish --tag=approval-admin-assets
```

### 4. Access Admin Panel
```
https://your-app.test/approval-admin
```

---

## Key Differences from Current Implementation

### Current (Blade-based)
- Multiple blade files
- Uses host app layout
- Page reloads
- Limited interactivity
- Hard to maintain consistency

### New (Horizon-style SPA)
- Single Vue app
- Own layout (independent)
- No page reloads
- Highly interactive
- Consistent across all projects
- Real-time by default
- Professional animations
- Better performance

---

## Timeline Estimate

**Total:** 20-25 hours

- Infrastructure & Setup: 3 hours
- Dashboard: 2 hours
- Entities: 3 hours
- Workflows: 4 hours
- Visual Designer: 5 hours
- Requests: 3 hours
- Analytics: 3 hours
- Settings: 2 hours
- Polish: 3 hours
- Testing: 2 hours

---

## Success Criteria

### Must Have
- ✅ Works out of the box (zero config)
- ✅ Beautiful, professional UI
- ✅ All CRUD operations functional
- ✅ Real-time updates
- ✅ Mobile responsive
- ✅ Dark mode
- ✅ Fast performance

### Nice to Have
- ✅ Visual workflow designer
- ✅ Custom report builder
- ✅ Keyboard shortcuts
- ✅ Export/Import workflows
- ✅ Multi-language support

---

## Architecture: Component-Based Design

### Core Principle: Reusable Widgets

All UI components are built as **standalone, reusable widgets** that:
1. Power the main admin panel
2. Can be extracted and used independently
3. Share the same codebase (DRY principle)
4. Work across Vue, React, Blade, and vanilla JS

```
┌─────────────────────────────────────────────┐
│   Standalone Admin Panel (/approval-admin)  │
│   ┌─────────────────────────────────────┐   │
│   │  Composed of Reusable Widgets       │   │
│   │  ┌──────┐ ┌──────┐ ┌──────┐        │   │
│   │  │Stats │ │List  │ │Chart │        │   │
│   │  └──────┘ └──────┘ └──────┘        │   │
│   └─────────────────────────────────────┘   │
└─────────────────────────────────────────────┘
                    │
                    │ Same widgets can be
                    │ extracted and used in
                    ▼
┌─────────────────────────────────────────────┐
│   User's Custom Application                 │
│   ┌──────┐  ┌──────┐  ┌──────┐             │
│   │Stats │  │List  │  │Chart │             │
│   └──────┘  └──────┘  └──────┘             │
└─────────────────────────────────────────────┘
```

---

## Multiple UI Integration Options

Inspired by [laravel-horizon-running-jobs](https://github.com/ashiqfardus/laravel-horizon-running-jobs), we'll provide **5 flexible ways** to integrate the approval system UI:

### Option 1: Standalone Admin Panel (Primary) ✅
**URL:** `/approval-admin`

**What it is:**
- Full-featured SPA admin panel
- **Built entirely from reusable widgets**
- Complete package management interface
- Zero configuration required
- Works immediately after `composer require`

**Use case:** Best for most users who want a ready-to-use admin interface

```bash
# Just visit after installation
https://your-app.test/approval-admin
```

**Internal Structure:**
```vue
<!-- The admin panel is composed of widgets -->
<template>
    <AdminLayout>
        <StatsWidget />           <!-- Reusable -->
        <PendingApprovalsWidget /> <!-- Reusable -->
        <ActivityFeedWidget />     <!-- Reusable -->
        <WorkflowListWidget />     <!-- Reusable -->
    </AdminLayout>
</template>
```

### Option 2: JavaScript Widget (Embeddable)
**For:** Adding approval widgets to existing pages

**Publish assets:**
```bash
php artisan vendor:publish --tag=approval-process-assets
```

**Usage:**
```html
<!-- Add to any page -->
<div id="approval-widget"></div>

<script src="/vendor/approval-process/widget.js"></script>
<script>
    ApprovalProcess.init({
        container: '#approval-widget',
        apiUrl: '/api/approval-process',
        refreshInterval: 5000,
        widgets: ['pending-approvals', 'my-requests', 'stats']
    });
</script>
```

**Available Widgets:**
- `pending-approvals` - List of items awaiting approval
- `my-requests` - User's submitted requests
- `stats` - Quick stats cards
- `recent-activity` - Activity feed
- `workflow-status` - Workflow progress tracker

### Option 3: Vue.js Components (For Vue Apps)
**For:** Vue.js/Nuxt applications

**Published components:**
```javascript
// Import individual components
import {
    ApprovalDashboard,
    WorkflowList,
    RequestList,
    ApprovalTimeline,
    EntityManager,
    WorkflowDesigner
} from './vendor/approval-process/components';

export default {
    components: {
        ApprovalDashboard,
        WorkflowList,
        RequestList
    }
}
```

**Usage:**
```vue
<template>
    <div>
        <approval-dashboard />
        <workflow-list :limit="10" />
        <request-list status="pending" />
    </div>
</template>
```

### Option 4: React Components (For React/Next.js Apps)
**For:** React/Next.js applications

**Published components:**
```javascript
// Import React components
import {
    ApprovalDashboard,
    WorkflowList,
    RequestList,
    ApprovalTimeline
} from '@vendor/approval-process/react';

function MyPage() {
    return (
        <div>
            <ApprovalDashboard />
            <WorkflowList limit={10} />
            <RequestList status="pending" />
        </div>
    );
}
```

### Option 5: Blade Components (For Blade Views)
**For:** Traditional Laravel Blade applications

**Usage:**
```blade
{{-- In any Blade view --}}
<x-approval-dashboard />

<x-approval-pending-list limit="5" />

<x-approval-workflow-status :workflow="$workflow" />

<x-approval-request-timeline :request="$request" />
```

**Available Blade Components:**
- `<x-approval-dashboard />` - Full dashboard
- `<x-approval-pending-list />` - Pending approvals
- `<x-approval-stats />` - Stats cards
- `<x-approval-workflow-status />` - Workflow progress
- `<x-approval-request-timeline />` - Request timeline
- `<x-approval-activity-feed />` - Recent activity

---

## Component Library Structure

```
resources/
├── js/
│   ├── core/                    # Core reusable widgets (framework-agnostic)
│   │   ├── widgets/
│   │   │   ├── StatsWidget.js
│   │   │   ├── PendingApprovalsWidget.js
│   │   │   ├── ActivityFeedWidget.js
│   │   │   ├── WorkflowListWidget.js
│   │   │   ├── RequestListWidget.js
│   │   │   ├── TimelineWidget.js
│   │   │   ├── ChartWidget.js
│   │   │   └── EntityManagerWidget.js
│   │   ├── api/
│   │   │   └── client.js        # API client (shared)
│   │   └── utils/
│   │       ├── formatters.js
│   │       └── helpers.js
│   │
│   ├── standalone/              # Full SPA admin panel (uses core widgets)
│   │   ├── app.js               # Main entry
│   │   ├── router.js
│   │   ├── store.js
│   │   ├── Layout.vue           # Admin layout wrapper
│   │   └── pages/
│   │       ├── Dashboard.vue    # ← Uses StatsWidget, ActivityFeedWidget
│   │       ├── Workflows.vue    # ← Uses WorkflowListWidget
│   │       ├── Requests.vue     # ← Uses RequestListWidget
│   │       ├── Entities.vue     # ← Uses EntityManagerWidget
│   │       ├── Analytics.vue    # ← Uses ChartWidget
│   │       └── Settings.vue
│   │
│   ├── vanilla/                 # Vanilla JS wrapper (exposes core widgets)
│   │   └── widget.js            # Main loader for vanilla JS usage
│   │
│   ├── vue/                     # Vue 3 wrapper (exposes core widgets as Vue components)
│   │   ├── index.js
│   │   ├── StatsWidget.vue      # ← Wraps core/widgets/StatsWidget.js
│   │   ├── PendingApprovals.vue # ← Wraps core/widgets/PendingApprovalsWidget.js
│   │   ├── WorkflowList.vue     # ← Wraps core/widgets/WorkflowListWidget.js
│   │   ├── RequestList.vue      # ← Wraps core/widgets/RequestListWidget.js
│   │   └── ApprovalTimeline.vue # ← Wraps core/widgets/TimelineWidget.js
│   │
│   └── react/                   # React wrapper (exposes core widgets as React components)
│       ├── index.jsx
│       ├── StatsWidget.jsx      # ← Wraps core/widgets/StatsWidget.js
│       ├── PendingApprovals.jsx # ← Wraps core/widgets/PendingApprovalsWidget.js
│       ├── WorkflowList.jsx     # ← Wraps core/widgets/WorkflowListWidget.js
│       ├── RequestList.jsx      # ← Wraps core/widgets/RequestListWidget.js
│       └── ApprovalTimeline.jsx # ← Wraps core/widgets/TimelineWidget.js
│
├── views/
│   ├── standalone-admin.blade.php  # SPA container
│   └── components/                 # Blade components (server-side rendered)
│       ├── stats.blade.php
│       ├── pending-list.blade.php
│       ├── workflow-list.blade.php
│       └── timeline.blade.php
│
└── css/
    ├── core.css             # Core widget styles (shared)
    ├── standalone.css       # Admin panel specific styles
    ├── widgets.css          # Vanilla JS widget styles
    └── components.css       # Blade component styles
```

### Key Architecture Points

1. **Core Widgets (Framework-Agnostic)**
   - Written in vanilla JS/TypeScript
   - Pure logic, minimal DOM manipulation
   - Used by ALL other implementations

2. **Standalone Admin Panel**
   - Vue 3 SPA
   - **Imports and uses core widgets**
   - Adds routing, layout, state management

3. **Framework Wrappers**
   - Thin wrappers around core widgets
   - Vue components wrap core widgets
   - React components wrap core widgets
   - Blade components call API directly

4. **Single Source of Truth**
   - All logic in `core/widgets/`
   - All wrappers consume the same core
   - Bug fixes propagate everywhere

---

## Build Configuration

### Vite Config for Multiple Builds
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [vue(), react()],
    build: {
        rollupOptions: {
            input: {
                // Standalone admin panel
                standalone: 'resources/js/standalone/app.js',
                
                // Vanilla JS widgets
                widget: 'resources/js/widgets/widget.js',
                
                // Vue components
                vue: 'resources/js/vue/index.js',
                
                // React components
                react: 'resources/js/react/index.jsx',
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name].[ext]'
            }
        },
        outDir: 'dist',
    }
});
```

### Publishing Assets
```php
// In ServiceProvider
$this->publishes([
    __DIR__.'/../dist' => public_path('vendor/approval-process'),
], 'approval-process-assets');

$this->publishes([
    __DIR__.'/../resources/js/vue' => resource_path('js/vendor/approval-process/vue'),
], 'approval-process-vue');

$this->publishes([
    __DIR__.'/../resources/js/react' => resource_path('js/vendor/approval-process/react'),
], 'approval-process-react');

$this->publishes([
    __DIR__.'/../resources/views/components' => resource_path('views/vendor/approval-process/components'),
], 'approval-process-blade');
```

---

## Widget API Examples

### Pending Approvals Widget
```javascript
ApprovalProcess.widget('pending-approvals', {
    container: '#pending-widget',
    limit: 5,
    autoRefresh: true,
    refreshInterval: 10000,
    onApprove: (requestId) => {
        console.log('Approved:', requestId);
    },
    onReject: (requestId) => {
        console.log('Rejected:', requestId);
    }
});
```

### Stats Widget
```javascript
ApprovalProcess.widget('stats', {
    container: '#stats-widget',
    metrics: ['pending', 'approved', 'rejected', 'overdue'],
    theme: 'dark',
    showTrends: true
});
```

### Activity Feed Widget
```javascript
ApprovalProcess.widget('activity-feed', {
    container: '#activity-widget',
    limit: 10,
    realtime: true,
    filters: ['approved', 'rejected', 'submitted']
});
```

---

## React Component Props

### WorkflowList Component
```typescript
interface WorkflowListProps {
    limit?: number;
    status?: 'active' | 'inactive' | 'all';
    onSelect?: (workflow: Workflow) => void;
    showActions?: boolean;
    theme?: 'light' | 'dark';
}
```

### RequestList Component
```typescript
interface RequestListProps {
    status?: 'pending' | 'approved' | 'rejected' | 'all';
    workflowId?: number;
    userId?: number;
    limit?: number;
    onAction?: (action: string, requestId: number) => void;
}
```

---

## Vue Component Props

### ApprovalDashboard Component
```vue
<approval-dashboard
    :user-id="currentUserId"
    :show-stats="true"
    :show-pending="true"
    :show-recent="true"
    :refresh-interval="5000"
    @approve="handleApprove"
    @reject="handleReject"
/>
```

### WorkflowDesigner Component
```vue
<workflow-designer
    :workflow="workflow"
    :editable="true"
    :show-grid="true"
    @save="handleSave"
    @validate="handleValidate"
/>
```

---

## Blade Component Examples

### Dashboard Component
```blade
<x-approval-dashboard
    :user-id="auth()->id()"
    :show-stats="true"
    :show-pending="true"
    :limit="10"
/>
```

### Pending List Component
```blade
<x-approval-pending-list
    :limit="5"
    :show-actions="true"
    :compact="false"
/>
```

### Workflow Status Component
```blade
<x-approval-workflow-status
    :workflow="$workflow"
    :request="$request"
    :show-timeline="true"
/>
```

---

## Installation & Setup Guide

### For Standalone Admin Panel (Default)
```bash
# Install package
composer require ashiqfardus/laravel-approval-process

# Run migrations
php artisan migrate

# Visit admin panel
https://your-app.test/approval-admin
```

### For JavaScript Widgets
```bash
# Publish assets
php artisan vendor:publish --tag=approval-process-assets

# Add to your HTML
<script src="/vendor/approval-process/widget.js"></script>
```

### For Vue Components
```bash
# Publish Vue components
php artisan vendor:publish --tag=approval-process-vue

# Import in your Vue app
import { ApprovalDashboard } from '@/vendor/approval-process/vue';
```

### For React Components
```bash
# Publish React components
php artisan vendor:publish --tag=approval-process-react

# Import in your React app
import { ApprovalDashboard } from '@/vendor/approval-process/react';
```

### For Blade Components
```bash
# Publish Blade components
php artisan vendor:publish --tag=approval-process-blade

# Use in your views
<x-approval-dashboard />
```

---

## Configuration

### Widget Configuration
```php
// config/approval-process.php

'widgets' => [
    'enabled' => true,
    'default_refresh_interval' => 10000, // 10 seconds
    'default_limit' => 10,
    'theme' => 'light', // 'light' or 'dark'
    'realtime' => true, // Enable WebSocket updates
],
```

### Component Configuration
```php
'components' => [
    'vue' => [
        'enabled' => true,
        'namespace' => 'ApprovalProcess',
    ],
    'react' => [
        'enabled' => true,
        'namespace' => 'ApprovalProcess',
    ],
    'blade' => [
        'enabled' => true,
        'prefix' => 'approval',
    ],
],
```

---

## Development Workflow

### Phase 1: Core Widgets (Foundation)
Build framework-agnostic widgets that will be used everywhere:

```javascript
// Example: core/widgets/StatsWidget.js
export class StatsWidget {
    constructor(container, options = {}) {
        this.container = container;
        this.options = options;
        this.apiClient = new ApiClient();
    }
    
    async render() {
        const stats = await this.apiClient.getStats();
        this.container.innerHTML = this.template(stats);
        this.attachEvents();
    }
    
    template(stats) {
        return `
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>${stats.pending}</h3>
                    <p>Pending</p>
                </div>
                <!-- More stats -->
            </div>
        `;
    }
    
    attachEvents() {
        // Event handlers
    }
}
```

### Phase 2: Standalone Admin Panel (Primary Use Case)
Build the SPA that **uses** the core widgets:

```vue
<!-- standalone/pages/Dashboard.vue -->
<template>
    <div class="dashboard">
        <div ref="statsWidget"></div>
        <div ref="activityWidget"></div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { StatsWidget, ActivityFeedWidget } from '../../core/widgets';

const statsWidget = ref(null);
const activityWidget = ref(null);

onMounted(() => {
    // Use core widgets
    new StatsWidget(statsWidget.value, {
        refreshInterval: 10000
    }).render();
    
    new ActivityFeedWidget(activityWidget.value, {
        limit: 10,
        realtime: true
    }).render();
});
</script>
```

### Phase 3: Framework Wrappers (Optional Extraction)
Create thin wrappers for users who want to extract widgets:

```vue
<!-- vue/StatsWidget.vue -->
<template>
    <div ref="container"></div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { StatsWidget as CoreStatsWidget } from '../core/widgets';

const props = defineProps({
    refreshInterval: { type: Number, default: 10000 }
});

const container = ref(null);

onMounted(() => {
    new CoreStatsWidget(container.value, {
        refreshInterval: props.refreshInterval
    }).render();
});
</script>
```

```jsx
// react/StatsWidget.jsx
import { useEffect, useRef } from 'react';
import { StatsWidget as CoreStatsWidget } from '../core/widgets';

export function StatsWidget({ refreshInterval = 10000 }) {
    const containerRef = useRef(null);
    
    useEffect(() => {
        const widget = new CoreStatsWidget(containerRef.current, {
            refreshInterval
        });
        widget.render();
        
        return () => widget.destroy();
    }, [refreshInterval]);
    
    return <div ref={containerRef} />;
}
```

---

## Next Steps (Revised)

### Phase 1: Core Foundation (4-5 hours)
1. **Set up Vite** with multi-build configuration
2. **Create API client** (shared by all widgets)
3. **Build core widgets** (framework-agnostic):
   - StatsWidget
   - PendingApprovalsWidget
   - ActivityFeedWidget
   - WorkflowListWidget
   - RequestListWidget
   - TimelineWidget
   - ChartWidget
   - EntityManagerWidget

### Phase 2: Standalone Admin Panel (10-12 hours)
4. **Set up Vue 3 SPA** structure
5. **Create admin layout** (navbar, sidebar)
6. **Build pages** using core widgets:
   - Dashboard (stats + activity)
   - Workflows (list + designer)
   - Requests (list + detail)
   - Entities (manager)
   - Analytics (charts)
   - Settings
7. **Add routing** and state management
8. **Implement real-time** updates

### Phase 3: Framework Wrappers (4-5 hours)
9. **Create Vue wrappers** for core widgets
10. **Create React wrappers** for core widgets
11. **Create Blade components** (server-side)
12. **Create vanilla JS loader** (widget.js)

### Phase 4: Documentation & Polish (3-4 hours)
13. **Write integration guides** for each method
14. **Create demo examples**
15. **Add Storybook** for widget showcase
16. **Optimize builds** and bundle sizes
17. **Add TypeScript definitions**
18. **Write tests** for core widgets

---

## Benefits of This Approach

### For Package Users
✅ **Maximum Flexibility** - Choose the integration method that fits their stack
✅ **Zero Lock-in** - Not forced to use a specific frontend framework
✅ **Progressive Enhancement** - Start with widgets, upgrade to full SPA
✅ **Framework Agnostic** - Works with Vue, React, Blade, or vanilla JS

### For Developers
✅ **Modern Stack** - Vue 3, React 18, Vite
✅ **Reusable Components** - DRY principle across all builds
✅ **Easy Maintenance** - Single source of truth
✅ **Well Documented** - Clear examples for each approach

### For End Users
✅ **Consistent UX** - Same design across all integration methods
✅ **Fast Performance** - Optimized builds for each use case
✅ **Real-time Updates** - WebSocket support in all components
✅ **Mobile Friendly** - Responsive across all devices

---

**Date:** 2026-02-17
**Status:** PLANNED (Enhanced with multiple UI options)
**Priority:** HIGH
**Inspiration:** [laravel-horizon-running-jobs](https://github.com/ashiqfardus/laravel-horizon-running-jobs)

This approach makes the package truly universal and production-ready for any Laravel application!
