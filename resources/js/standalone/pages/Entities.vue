<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Entities</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage approvable models and database tables
                </p>
            </div>
            <div class="flex gap-2">
                <button @click="openDiscoverModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-md transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Auto-Discover
                </button>
                <button @click="openCreateModal"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Entity
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-red-700 dark:text-red-400">
            {{ error }}
        </div>

        <!-- Empty State -->
        <div v-else-if="entities.length === 0"
             class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No entities yet</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Add a model or table to start building approval workflows.
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <button @click="openDiscoverModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Auto-Discover
                </button>
                <button @click="openCreateModal"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors">
                    Add Manually
                </button>
            </div>
        </div>

        <!-- Entities Table -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Connection</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="entity in entities" :key="entity.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">{{ entity.label }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ entity.name }}</div>
                            <div v-if="entity.description" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entity.description }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="entity.type === 'model'
                                ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                                : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'"
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                {{ entity.type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-mono">{{ entity.connection || 'default' }}</span>
                            <span v-if="entity.name && entity.name.includes('.')"
                                  class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                                  title="Cross-database table (db.table prefix)">
                                cross-db
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button @click="toggleStatus(entity)"
                                    :class="entity.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200'"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors cursor-pointer">
                                {{ entity.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                <button @click="openEditModal(entity)"
                                        title="Edit"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click="confirmDelete(entity)"
                                        title="Delete"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ================================================================
             ADD / EDIT ENTITY MODAL
             ================================================================ -->
        <div v-if="showFormModal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             @click.self="closeFormModal">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ editingEntity ? 'Edit Entity' : 'Add Entity' }}
                    </h3>
                    <button @click="closeFormModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveEntity" class="space-y-4">

                    <!-- ── Type selector (create only) ── -->
                    <div v-if="!editingEntity">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <div class="flex gap-3">
                            <label class="flex-1 flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors"
                                   :class="form.type === 'model'
                                       ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                       : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                                <input type="radio" v-model="form.type" value="model" class="sr-only" @change="onTypeChange">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Eloquent Model</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">App\Models\...</div>
                                </div>
                            </label>
                            <label class="flex-1 flex items-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors"
                                   :class="form.type === 'table'
                                       ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                       : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'">
                                <input type="radio" v-model="form.type" value="table" class="sr-only" @change="onTypeChange">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Database Table</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Direct table query</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- ── TABLE TYPE: 3-step selection ── -->
                    <template v-if="!editingEntity && form.type === 'table'">

                        <!-- Step 1: Connection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold mr-1">1</span>
                                Connection
                            </label>
                            <div v-if="loadingConnections" class="flex items-center gap-2 text-sm text-gray-500 py-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
                                Loading connections...
                            </div>
                            <select v-else v-model="form.connection" @change="onConnectionChange"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">Default connection</option>
                                <option v-for="conn in connections" :key="conn.name" :value="conn.name">
                                    {{ conn.name }}{{ conn.is_default ? ' (default)' : '' }} — {{ conn.driver }}{{ conn.database ? ' · ' + conn.database : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- Step 2: Database (MySQL/MariaDB only — shows primary + siblings) -->
                        <div v-if="crossDbHint || availableDatabases.length > 1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold mr-1">2</span>
                                Database
                            </label>
                            <div v-if="loadingTables" class="flex items-center gap-2 text-sm text-gray-500 py-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
                                Discovering databases...
                            </div>
                            <select v-else v-model="selectedDatabase" @change="onDatabaseChange"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">— Select a database —</option>
                                <option v-for="db in availableDatabases" :key="db.name" :value="db.name">
                                    {{ db.name }}{{ db.isPrimary ? ' (primary)' : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- Step 3: Table (searchable) -->
                        <div v-if="selectedDatabase || (!crossDbHint && !loadingTables && discoveredTables.length)">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold mr-1">{{ crossDbHint || availableDatabases.length > 1 ? '3' : '2' }}</span>
                                Table
                            </label>
                            <div v-if="loadingTables" class="flex items-center gap-2 text-sm text-gray-500 py-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
                                Fetching tables...
                            </div>
                            <div v-else-if="tableError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg p-2">
                                {{ tableError }}
                            </div>
                            <template v-else>
                                <!-- Searchable table picker -->
                                <div class="relative">
                                    <input v-model="tableSearch"
                                           :placeholder="tablesForSelectedDb.length ? `Search ${tablesForSelectedDb.length} tables...` : 'No tables found'"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                           @focus="tableDropdownOpen = true"
                                           @blur="onTableSearchBlur">
                                    <div v-if="form.name" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">✓</span>
                                    </div>
                                </div>

                                <!-- Selected table display -->
                                <div v-if="form.name && !tableDropdownOpen" class="mt-1 px-2 py-1 bg-primary-50 dark:bg-primary-900/20 rounded text-xs text-primary-700 dark:text-primary-300 font-mono">
                                    Selected: {{ form.name }}
                                </div>

                                <!-- Dropdown list -->
                                <div v-if="tableDropdownOpen && filteredTablesForDb.length"
                                     class="mt-1 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 shadow-lg max-h-48 overflow-y-auto">
                                    <button v-for="t in filteredTablesForDb" :key="t.name"
                                            type="button"
                                            @mousedown.prevent="selectTable(t)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-primary-50 dark:hover:bg-primary-900/20 text-gray-900 dark:text-white font-mono transition-colors"
                                            :class="form.name === t.name ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : ''">
                                        {{ t.name }}
                                    </button>
                                </div>
                                <div v-else-if="tableDropdownOpen && tableSearch && !filteredTablesForDb.length"
                                     class="mt-1 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                    No tables match "{{ tableSearch }}"
                                </div>
                            </template>
                        </div>

                        <!-- Fallback manual input (no DB selected yet on non-MySQL, or restricted access) -->
                        <div v-if="!loadingTables && !crossDbHint && !availableDatabases.length && !tableError">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Table name</label>
                            <input v-model="form.name" placeholder="e.g. orders"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono">
                        </div>

                    </template>

                    <!-- ── MODEL TYPE: model class dropdown ── -->
                    <div v-if="!editingEntity && form.type === 'model'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model Class</label>
                        <div v-if="loadingModels" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 py-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
                            Discovering models...
                        </div>
                        <template v-else>
                            <select v-if="discoveredModels.length" v-model="form.name" @change="onModelSelect"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">— Select a model —</option>
                                <option v-for="m in discoveredModels" :key="m.class" :value="m.class">
                                    {{ m.name }} ({{ m.class }})
                                </option>
                            </select>
                            <input v-else v-model="form.name" required
                                   placeholder="App\Models\Order"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent font-mono">
                        </template>
                    </div>

                    <!-- ── Label ── -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                        <input v-model="form.label" required placeholder="e.g. Purchase Order"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>

                    <!-- ── Description ── -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description <span class="text-gray-400">(optional)</span>
                        </label>
                        <textarea v-model="form.description" rows="2" placeholder="Brief description..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"></textarea>
                    </div>

                    <!-- Form error -->
                    <div v-if="formError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                        {{ formError }}
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeFormModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="saving || !canSubmit"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 rounded-lg transition-colors flex items-center gap-2">
                            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            {{ editingEntity ? 'Save Changes' : 'Add Entity' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================================================================
             AUTO-DISCOVER MODAL
             ================================================================ -->
        <div v-if="showDiscoverModal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             @click.self="showDiscoverModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl mx-4 p-6 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Auto-Discover Entities</h3>
                    <button @click="showDiscoverModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Filters row: Connection + Database -->
                <div class="mb-4 flex flex-wrap gap-3">
                    <!-- Connection -->
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Connection</label>
                        <select v-model="discoverConnection" @change="onDiscoverConnectionChange"
                                class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">Default</option>
                            <option v-for="conn in connections" :key="conn.name" :value="conn.name">
                                {{ conn.name }}{{ conn.is_default ? ' (default)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Database filter (MySQL only) -->
                    <div v-if="discoverAvailableDatabases.length > 1" class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Database</label>
                        <select v-model="discoverSelectedDatabase"
                                class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All databases</option>
                            <option v-for="db in discoverAvailableDatabases" :key="db.name" :value="db.name">
                                {{ db.name }}{{ db.isPrimary ? ' (primary)' : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Table search -->
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search tables</label>
                        <input v-model="discoverTableSearch" placeholder="Filter tables..."
                               class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>

                <!-- Discovering spinner -->
                <div v-if="discovering" class="flex flex-col items-center py-8 gap-3">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Scanning models and tables...</p>
                </div>

                <!-- Results -->
                <div v-else-if="discovered" class="flex-1 overflow-y-auto space-y-4">
                    <!-- Models -->
                    <div v-if="discovered.models?.length && !discoverSelectedDatabase">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                            Eloquent Models ({{ discovered.models.length }})
                        </h4>
                        <div class="space-y-1">
                            <label v-for="model in discovered.models" :key="model.class"
                                   class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                <input type="checkbox" :value="{ type: 'model', name: model.class, label: model.label || model.name }"
                                       v-model="selectedDiscovered"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ model.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ model.class }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Tables — grouped by database -->
                    <template v-for="(group, dbName) in filteredDiscoverGroups" :key="dbName">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-purple-500 inline-block"></span>
                                <span class="font-mono">{{ dbName }}</span>
                                <span class="text-gray-400 font-normal">({{ group.length }})</span>
                                <!-- Select all for this db -->
                                <button type="button" @click="toggleSelectAllDb(dbName, group)"
                                        class="ml-auto text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    {{ isAllDbSelected(dbName, group) ? 'Deselect all' : 'Select all' }}
                                </button>
                            </h4>
                            <div class="space-y-1">
                                <label v-for="t in group" :key="t.name"
                                       class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <input type="checkbox"
                                           :value="{ type: 'table', name: t.name, label: t.label, connection: discoverConnection || null }"
                                           v-model="selectedDiscovered"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ t.name }}</div>
                                </label>
                            </div>
                        </div>
                    </template>

                    <div v-if="!discovered.models?.length && !Object.keys(filteredDiscoverGroups).length"
                         class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
                        No entities found{{ discoverTableSearch ? ` matching "${discoverTableSearch}"` : '' }}.
                    </div>
                </div>

                <div v-if="discovered && !discovering" class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700 mt-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ selectedDiscovered.length }} selected</span>
                    <div class="flex gap-3">
                        <button @click="showDiscoverModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button @click="importDiscovered" :disabled="!selectedDiscovered.length || saving"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 rounded-lg transition-colors">
                            Import Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================
             DELETE CONFIRM MODAL
             ================================================================ -->
        <div v-if="deletingEntity"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             @click.self="deletingEntity = null">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Delete Entity</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                    Are you sure you want to delete <strong>{{ deletingEntity.label }}</strong>?
                    Any workflows using this entity may be affected.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deletingEntity = null"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button @click="deleteEntity" :disabled="saving"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-lg transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '../../core/api/client.js';

// ── State ──────────────────────────────────────────────────────────────────
const entities           = ref([]);
const loading            = ref(true);
const error              = ref(null);
const saving             = ref(false);

// Connections
const connections        = ref([]);
const loadingConnections = ref(false);

// Models (from discover, cached)
const discoveredModels   = ref([]);
const loadingModels      = ref(false);

// Tables (loaded per-connection when type=table)
const discoveredTables   = ref([]);
const loadingTables      = ref(false);
const tableError         = ref(null);
const crossDbHint        = ref(false);

// Table selection state (Add Entity modal)
const selectedDatabase   = ref('');   // which DB the user picked in step 2
const tableSearch        = ref('');   // search input for step 3
const tableDropdownOpen  = ref(false);

// Form modal
const showFormModal      = ref(false);
const editingEntity      = ref(null);
const formError          = ref(null);
const form               = ref({ type: 'model', name: '', label: '', description: '', connection: '' });

// Discover modal
const showDiscoverModal       = ref(false);
const discovering             = ref(false);
const discovered              = ref(null);
const selectedDiscovered      = ref([]);
const discoverConnection      = ref('');
const discoverSelectedDatabase = ref('');
const discoverTableSearch     = ref('');

// Delete modal
const deletingEntity     = ref(null);

// ── Computed ───────────────────────────────────────────────────────────────

/**
 * All unique database names available from the current connection's table list.
 * Primary DB tables have no dot; cross-db tables have "dbname.tablename".
 * Returns [{name, isPrimary}]
 */
const availableDatabases = computed(() => {
    if (!discoveredTables.value.length) return [];
    const primaryName = primaryDbName.value;
    const dbs = new Set();
    dbs.add(primaryName);
    for (const t of discoveredTables.value) {
        if (!t.error && t.cross_db) {
            dbs.add(t.name.split('.')[0]);
        }
    }
    return [...dbs].map(name => ({ name, isPrimary: name === primaryName }));
});

/**
 * The primary database name — tables without a dot belong to this DB.
 * We derive it from the selected connection config.
 */
const primaryDbName = computed(() => {
    if (!form.value.connection) {
        // default connection
        const def = connections.value.find(c => c.is_default);
        return def?.database || 'primary';
    }
    const conn = connections.value.find(c => c.name === form.value.connection);
    return conn?.database || form.value.connection;
});

/**
 * Tables belonging to the currently selected database (step 3).
 * For the primary DB: tables without a dot.
 * For cross-db: tables whose prefix matches selectedDatabase.
 */
const tablesForSelectedDb = computed(() => {
    if (!selectedDatabase.value) {
        // No DB selected yet — show nothing (or all primary if no cross-db hint)
        if (!crossDbHint.value) {
            return discoveredTables.value.filter(t => !t.error && !t.cross_db);
        }
        return [];
    }
    const isPrimary = selectedDatabase.value === primaryDbName.value;
    if (isPrimary) {
        return discoveredTables.value.filter(t => !t.error && !t.cross_db);
    }
    return discoveredTables.value.filter(t =>
        !t.error && t.cross_db && t.name.startsWith(selectedDatabase.value + '.')
    );
});

/** Filtered by search query */
const filteredTablesForDb = computed(() => {
    const q = tableSearch.value.toLowerCase().trim();
    if (!q) return tablesForSelectedDb.value;
    return tablesForSelectedDb.value.filter(t => t.name.toLowerCase().includes(q));
});

// ── Discover modal computed ────────────────────────────────────────────────

const discoverPrimaryDbName = computed(() => {
    if (!discoverConnection.value) {
        const def = connections.value.find(c => c.is_default);
        return def?.database || 'primary';
    }
    const conn = connections.value.find(c => c.name === discoverConnection.value);
    return conn?.database || discoverConnection.value;
});

const discoverAvailableDatabases = computed(() => {
    if (!discovered.value?.tables) return [];
    const primaryName = discoverPrimaryDbName.value;
    const dbs = new Set();
    dbs.add(primaryName);
    for (const t of discovered.value.tables) {
        if (!t.error && t.cross_db) {
            dbs.add(t.name.split('.')[0]);
        }
    }
    return [...dbs].map(name => ({ name, isPrimary: name === primaryName }));
});

/**
 * Tables in the discover modal, grouped by database name, filtered by
 * discoverSelectedDatabase and discoverTableSearch.
 * Returns { dbName: [table, ...] }
 */
const filteredDiscoverGroups = computed(() => {
    if (!discovered.value?.tables) return {};
    const primaryName = discoverPrimaryDbName.value;
    const q = discoverTableSearch.value.toLowerCase().trim();
    const groups = {};

    for (const t of discovered.value.tables) {
        if (t.error) continue;
        const dbName = t.cross_db ? t.name.split('.')[0] : primaryName;

        // Filter by selected database
        if (discoverSelectedDatabase.value && dbName !== discoverSelectedDatabase.value) continue;

        // Filter by search
        if (q && !t.name.toLowerCase().includes(q)) continue;

        if (!groups[dbName]) groups[dbName] = [];
        groups[dbName].push(t);
    }
    return groups;
});

// Submit guard
const canSubmit = computed(() => {
    if (editingEntity.value) return true;
    return !!form.value.name && !!form.value.label;
});

// ── Load data ──────────────────────────────────────────────────────────────

async function loadEntities() {
    loading.value = true;
    error.value   = null;
    try {
        const data     = await apiClient.getEntities();
        entities.value = Array.isArray(data) ? data : (data.data ?? []);
    } catch {
        error.value = 'Failed to load entities. Please try again.';
    } finally {
        loading.value = false;
    }
}

async function loadConnections() {
    loadingConnections.value = true;
    try {
        const data        = await apiClient.getConnections();
        connections.value = Array.isArray(data) ? data : [];
    } catch {
        connections.value = [];
    } finally {
        loadingConnections.value = false;
    }
}

async function loadModels() {
    loadingModels.value = true;
    try {
        const data             = await apiClient.discoverEntities();
        discoveredModels.value = data?.models ?? [];
    } catch {
        discoveredModels.value = [];
    } finally {
        loadingModels.value = false;
    }
}

async function loadTablesForConnection(connectionName) {
    loadingTables.value    = true;
    tableError.value       = null;
    crossDbHint.value      = false;
    discoveredTables.value = [];
    selectedDatabase.value = '';
    tableSearch.value      = '';
    form.value.name        = '';

    try {
        const data             = await apiClient.discoverEntities(connectionName || null);
        discoveredTables.value = data?.tables ?? [];
        crossDbHint.value      = data?.cross_db_hint ?? false;
    } catch {
        tableError.value = 'Could not fetch tables for this connection.';
    } finally {
        loadingTables.value = false;
    }
}

// ── Form modal ─────────────────────────────────────────────────────────────

async function openCreateModal() {
    editingEntity.value    = null;
    form.value             = { type: 'model', name: '', label: '', description: '', connection: '' };
    formError.value        = null;
    selectedDatabase.value = '';
    tableSearch.value      = '';
    tableDropdownOpen.value = false;
    crossDbHint.value      = false;
    discoveredTables.value = [];
    showFormModal.value    = true;

    if (!connections.value.length) loadConnections();
    if (!discoveredModels.value.length) loadModels();
}

function openEditModal(entity) {
    editingEntity.value = entity;
    form.value = {
        type:        entity.type,
        name:        entity.name,
        label:       entity.label,
        description: entity.description || '',
        connection:  entity.connection || '',
    };
    formError.value     = null;
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value    = false;
    editingEntity.value    = null;
    discoveredTables.value = [];
    selectedDatabase.value = '';
    tableSearch.value      = '';
    tableDropdownOpen.value = false;
    crossDbHint.value      = false;
}

function onTypeChange() {
    form.value.name        = '';
    form.value.connection  = '';
    selectedDatabase.value = '';
    tableSearch.value      = '';
    tableDropdownOpen.value = false;
    crossDbHint.value      = false;
    discoveredTables.value = [];

    if (form.value.type === 'model' && !discoveredModels.value.length) {
        loadModels();
    }
}

function onConnectionChange() {
    selectedDatabase.value = '';
    tableSearch.value      = '';
    form.value.name        = '';
    loadTablesForConnection(form.value.connection);
}

function onDatabaseChange() {
    form.value.name   = '';
    tableSearch.value = '';
    tableDropdownOpen.value = false;
}

function selectTable(t) {
    form.value.name     = t.name;
    tableSearch.value   = t.name;
    tableDropdownOpen.value = false;
    // Auto-fill label if empty
    if (!form.value.label) {
        const parts = t.name.split('.');
        const tablePart = parts[parts.length - 1];
        form.value.label = tablePart
            .replace(/_/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase());
    }
}

function onTableSearchBlur() {
    // Small delay so mousedown on list items fires first
    setTimeout(() => { tableDropdownOpen.value = false; }, 150);
}

function onModelSelect() {
    const model = discoveredModels.value.find(m => m.class === form.value.name);
    if (model && !form.value.label) {
        form.value.label = model.label || model.name;
    }
}

// ── Save ───────────────────────────────────────────────────────────────────

async function saveEntity() {
    saving.value    = true;
    formError.value = null;

    try {
        if (editingEntity.value) {
            await apiClient.updateEntity(editingEntity.value.id, {
                label:       form.value.label,
                description: form.value.description || null,
            });
        } else {
            await apiClient.createEntity({
                type:        form.value.type,
                name:        form.value.name,
                label:       form.value.label,
                description: form.value.description || null,
                connection:  form.value.connection || null,
            });
        }
        closeFormModal();
        await loadEntities();
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error;
        formError.value = msg || 'Failed to save entity. Please check your input.';
    } finally {
        saving.value = false;
    }
}

// ── Status toggle ──────────────────────────────────────────────────────────

async function toggleStatus(entity) {
    try {
        await apiClient.updateEntity(entity.id, { is_active: !entity.is_active });
        entity.is_active = !entity.is_active;
    } catch {
        await loadEntities();
    }
}

// ── Delete ─────────────────────────────────────────────────────────────────

function confirmDelete(entity) {
    deletingEntity.value = entity;
}

async function deleteEntity() {
    if (!deletingEntity.value) return;
    saving.value = true;
    try {
        await apiClient.deleteEntity(deletingEntity.value.id);
        deletingEntity.value = null;
        await loadEntities();
    } catch {
        // ignore
    } finally {
        saving.value = false;
    }
}

// ── Auto-discover ──────────────────────────────────────────────────────────

async function openDiscoverModal() {
    showDiscoverModal.value       = true;
    discovered.value              = null;
    selectedDiscovered.value      = [];
    discoverConnection.value      = '';
    discoverSelectedDatabase.value = '';
    discoverTableSearch.value     = '';

    if (!connections.value.length) await loadConnections();
    await runDiscover();
}

async function runDiscover() {
    discovering.value             = true;
    discovered.value              = null;
    selectedDiscovered.value      = [];
    discoverSelectedDatabase.value = '';
    try {
        discovered.value = await apiClient.discoverEntities(discoverConnection.value || null);
    } catch {
        discovered.value = { models: [], tables: [] };
    } finally {
        discovering.value = false;
    }
}

async function onDiscoverConnectionChange() {
    await runDiscover();
}

// Select all / deselect all tables for a database group
function isAllDbSelected(dbName, group) {
    return group.every(t =>
        selectedDiscovered.value.some(s => s.name === t.name)
    );
}

function toggleSelectAllDb(dbName, group) {
    if (isAllDbSelected(dbName, group)) {
        // Deselect all in this group
        selectedDiscovered.value = selectedDiscovered.value.filter(
            s => !group.some(t => t.name === s.name)
        );
    } else {
        // Select all in this group (add missing ones)
        const toAdd = group
            .filter(t => !selectedDiscovered.value.some(s => s.name === t.name))
            .map(t => ({ type: 'table', name: t.name, label: t.label, connection: discoverConnection.value || null }));
        selectedDiscovered.value = [...selectedDiscovered.value, ...toAdd];
    }
}

async function importDiscovered() {
    saving.value = true;
    await Promise.allSettled(
        selectedDiscovered.value.map(item =>
            apiClient.createEntity({
                type:       item.type,
                name:       item.name,
                label:      item.label,
                connection: item.connection || null,
            })
        )
    );
    saving.value            = false;
    showDiscoverModal.value = false;
    await loadEntities();
}

// ── Init ───────────────────────────────────────────────────────────────────

onMounted(async () => {
    await loadEntities();
    loadConnections();
});
</script>
