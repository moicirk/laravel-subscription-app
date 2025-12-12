@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Hero Section -->
    <div class="text-center py-16 sm:py-24">
        <!-- Main Title -->
        <h1 class="text-5xl sm:text-6xl font-bold text-gray-900 mb-6">
            Welcome to Subscription App
        </h1>

        <!-- Subtitle -->
        <p class="text-xl sm:text-2xl text-gray-600 mb-10 max-w-3xl mx-auto">
            Choose the perfect subscription plan that fits your needs and unlock amazing features
        </p>

        <!-- Plans Button -->
        <div class="flex justify-center gap-2">
            <a href="{{ route('plans.index') }}"
               class="inline-block bg-blue-600 text-white text-lg font-semibold px-8 py-4 rounded-lg hover:bg-blue-700 transition-colors shadow-lg hover:shadow-xl">
                View Our Plans
            </a>
        </div>
    </div>

    <!-- Features Grid -->
    <div class="py-16 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Flexible Plans</h3>
                <p class="text-gray-600">Choose from monthly or yearly subscription plans that suit your budget</p>
            </div>

            <!-- Feature 2 -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Easy Management</h3>
                <p class="text-gray-600">Upgrade, downgrade, or cancel your subscription anytime</p>
            </div>

            <!-- Feature 3 -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure Payments</h3>
                <p class="text-gray-600">Your payments are processed securely with industry-leading encryption</p>
            </div>
        </div>
    </div>
</div>
@endsection
