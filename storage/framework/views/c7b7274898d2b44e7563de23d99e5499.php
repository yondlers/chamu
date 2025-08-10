<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'airbnb': '#FF5A5F',
                }
            }
        }
    }
</script>

<body class="h-screen overflow-hidden">
<!-- Main Split Container -->
<div class="flex h-full">
    <!-- Visitor Side -->
    <div class="visitor-side w-1/2 bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 relative overflow-hidden cursor-pointer transition-all duration-500 hover:w-3/5 group" onclick="selectRole('visitor')">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full"></div>
            <div class="absolute top-40 right-20 w-20 h-20 bg-white rounded-full"></div>
            <div class="absolute bottom-32 left-32 w-16 h-16 bg-white rounded-full"></div>
            <div class="absolute bottom-10 right-10 w-24 h-24 bg-white rounded-full"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-white p-12">
            <!-- Icon -->
            <div class="mb-8 transform group-hover:scale-110 transition-transform duration-300">
                <div class="w-32 h-32 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/30">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-5xl font-bold mb-6 text-center group-hover:text-6xl transition-all duration-300">
                Tenant
            </h1>

            <!-- Description -->
            <p class="text-xl text-center mb-8 opacity-90 max-w-md leading-relaxed">
                Find your next home on Chamu !
            </p>

            <!-- Features -->
            <div class="space-y-4 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 delay-200">
                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg">Search & find your dream home</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg">Manage your lease and home</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg">Streamline property application</span>
                </div>
            </div>

            <!-- CTA Button -->
            <a href="<?php echo e(route('tenants.user')); ?>" class="mt-8 bg-white text-purple-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transform hover:scale-105 transition-all duration-300 shadow-lg">
                Start Exploring
            </a>
        </div>

    </div>

    <!-- Host Side -->
    <div class="host-side w-1/2 bg-gradient-to-br from-airbnb via-red-500 to-pink-600 relative overflow-hidden cursor-pointer transition-all duration-500 hover:w-3/5 group" onclick="selectRole('host')">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-16 right-16 w-28 h-28 bg-white rounded-full"></div>
            <div class="absolute top-32 left-16 w-20 h-20 bg-white rounded-full"></div>
            <div class="absolute bottom-40 right-24 w-16 h-16 bg-white rounded-full"></div>
            <div class="absolute bottom-16 left-20 w-24 h-24 bg-white rounded-full"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 h-full flex flex-col justify-center items-center text-white p-12">
            <!-- Icon -->
            <div class="mb-8 transform group-hover:scale-110 transition-transform duration-300">
                <div class="w-32 h-32 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/30">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h1 class="text-5xl font-bold mb-6 text-center group-hover:text-6xl transition-all duration-300">
                Landlord / Agent
            </h1>

            <!-- Description -->
            <p class="text-xl text-center mb-8 opacity-90 max-w-md leading-relaxed">
                Manage your property here on Chamu
            </p>

            <!-- Features -->
            <div class="space-y-4 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 delay-200">
                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg">List your property</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg">Manage bookings</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-lg">Earn extra income</span>
                </div>
            </div>

            <!-- CTA Button -->
            <a href="<?php echo e(route('teams.create')); ?>" class="mt-8 bg-white text-red-600 px-8 py-4 rounded-full font-bold text-lg hover:bg-gray-100 transform hover:scale-105 transition-all duration-300 shadow-lg">
                Start Hosting
            </a>
        </div>

    </div>
</div>




</body>
<?php /**PATH /Users/slx/Code/chamu2/resources/views/components/welcome.blade.php ENDPATH**/ ?>