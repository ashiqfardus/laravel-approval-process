# Quick Start - Approval Process Admin Panel

## 🚀 For Users (Installing the Package)

### 1. Install Package
```bash
composer require ashiqfardus/laravel-approval-process
```

### 2. Run Migrations
```bash
php artisan migrate
```

### 3. Publish Assets
```bash
php artisan vendor:publish --tag=approval-process-assets
```

### 4. Access Admin Panel
```
Visit: https://your-app.test/approval-admin
```

**That's it!** Zero configuration needed.

---

## 🛠️ For Developers (Working on the Package)

### Setup
```bash
cd "D:\Laravel Applications\laravel-approval-process"
npm install
```

### Development
```bash
npm run dev    # Hot reload for development
```

### Build
```bash
npm run build  # Production build
```

### Test in Demo
```bash
cd "D:\Laravel Applications\laravel-approval-process-demo"
php artisan vendor:publish --tag=approval-process-assets --force
```

---

## 📦 Widget Usage

### Vanilla JS
```html
<div id="stats"></div>
<script src="/vendor/approval-process/widget.js"></script>
<script>
    ApprovalProcess.widget('stats', {
        container: '#stats',
        refreshInterval: 10000
    });
</script>
```

### Vue 3
```bash
php artisan vendor:publish --tag=approval-process-vue
```

```vue
<script setup>
import { StatsWidget } from '@/vendor/approval-process/vue';
</script>

<template>
    <StatsWidget :refresh-interval="10000" />
</template>
```

---

## 🎨 Available Widgets

1. **stats** - Dashboard statistics
2. **pending-approvals** - Pending approvals list
3. **activity-feed** - Recent activity
4. **workflow-list** - Workflow cards

---

## ⚙️ Configuration

```php
// config/approval-process.php
'ui' => [
    'admin_panel_middleware' => ['web', 'auth'],
],
```

---

## 📚 Full Documentation

- `ADMIN_PANEL_PLAN.md` - Architecture & design
- `IMPLEMENTATION_ROADMAP.md` - Development checklist
- `IMPLEMENTATION_PROGRESS.md` - Current status
- `ADMIN_UI_COMPLETE.md` - Complete reference

---

## 🐛 Troubleshooting

### Assets not loading?
```bash
php artisan vendor:publish --tag=approval-process-assets --force
php artisan cache:clear
```

### Build errors?
```bash
npm install
npm run build
```

### Route not found?
Check that `approval-process.ui.enabled` is `true` in config.

---

**Need help?** Check the full documentation or open an issue on GitHub.
