<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Subscription Plans')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center text-xl font-bold text-gray-800">
                        <span class="relative flex items-center mr-2">
                            <span id="status-dot" class="w-3 h-3 rounded-full bg-gray-400"></span>
                        </span>
                        Subscription App
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <span class="text-gray-600">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Login</a>
                        <a href="{{ route('register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Sign Up</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-10">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div id="notifications"></div>

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} Subscription App. All rights reserved.
            </p>
        </div>
    </footer>

    @stack('scripts')
    <script>
        // Wait for Echo to be initialized
        document.addEventListener('DOMContentLoaded', function() {
            const statusDot = document.getElementById('status-dot');
            const isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};

            // Listen for user login events
            window.Echo.channel('user-login')
                .listen('.user.logged.in', (e) => {
                    console.log('User logged in:', e);

                    // Create notification element
                    const notification = document.createElement('div');
                    notification.className = 'bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg animate-fade-in mb-1';
                    notification.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm text-green-600">
                            User ID: ${e.user.id}, Name: ${e.user.name}
                        </p>
                    </div>
                </div>
            `;

                    // Add to notifications container
                    const container = document.getElementById('notifications');
                    container.insertBefore(notification, container.firstChild);

                    // Remove after 5 seconds
                    setTimeout(() => {
                        notification.style.opacity = '0';
                        notification.style.transition = 'opacity 0.5s ease-out';
                        setTimeout(() => notification.remove(), 500);
                    }, 5000);
                });

            // Handle online/offline status
            if (isAuthenticated) {
                // Join presence channel for authenticated users
                window.Echo.join('users.online')
                    .here((users) => {
                        console.log('Currently online users:', users);
                        // Update status to online (green)
                        statusDot.classList.remove('bg-gray-400');
                        statusDot.classList.add('bg-green-500');
                    })
                    .joining((user) => {
                        console.log('User joining:', user.name);
                    })
                    .leaving((user) => {
                        console.log('User leaving:', user.name);
                    })
                    .error((error) => {
                        console.error('Error joining presence channel:', error);
                        // Keep dot gray on error
                        statusDot.classList.remove('bg-green-500');
                        statusDot.classList.add('bg-gray-400');
                    });

                console.log('Joined presence channel: users.online');
            } else {
                console.log('User not authenticated - status dot remains gray');
            }

            console.log('WebSocket listener initialized for user-login channel');
        });
    </script>

</body>
</html>
