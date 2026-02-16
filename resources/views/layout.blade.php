<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Approval Process') - Laravel Approval Process</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-indigo-900 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold">Approval Process</h1>
                <p class="text-indigo-300 text-sm mt-1">Enterprise Workflow</p>
            </div>
            
            <nav class="mt-6">
                <a href="{{ route('approval-process.dashboard') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.dashboard*') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-chart-line mr-3"></i>
                    Dashboard
                </a>
                
                <a href="{{ route('approval-process.my-approvals') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.my-approvals') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-tasks mr-3"></i>
                    My Approvals
                    @if(isset($pendingCount) && $pendingCount > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingCount }}</span>
                    @endif
                </a>
                
                <a href="{{ route('approval-process.my-requests') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.my-requests') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-file-alt mr-3"></i>
                    My Requests
                </a>
                
                <a href="{{ route('approval-process.requests.index') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.requests*') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-inbox mr-3"></i>
                    All Requests
                </a>
                
                <div class="px-6 py-2 text-indigo-300 text-xs uppercase font-semibold mt-6">Admin</div>
                
                <a href="{{ route('approval-process.workflows.index') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.workflows*') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-project-diagram mr-3"></i>
                    Workflows
                </a>
                
                <a href="{{ route('approval-process.analytics.index') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.analytics*') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Analytics
                </a>
                
                <a href="{{ route('approval-process.reports.index') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.reports*') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-file-pdf mr-3"></i>
                    Reports
                </a>
                
                <a href="{{ route('approval-process.settings') }}" class="flex items-center px-6 py-3 hover:bg-indigo-800 {{ request()->routeIs('approval-process.settings') ? 'bg-indigo-800 border-l-4 border-white' : '' }}">
                    <i class="fas fa-cog mr-3"></i>
                    Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-8 py-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                        <p class="text-gray-600 text-sm mt-1">@yield('page-description', '')</p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="relative text-gray-600 hover:text-gray-800">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">3</span>
                        </button>
                        
                        <!-- User Menu -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                </div>
                                <span>{{ auth()->user()->name ?? 'User' }}</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                                <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                                <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Settings</a>
                                <hr class="my-2">
                                <a href="#" class="block px-4 py-2 text-red-600 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
