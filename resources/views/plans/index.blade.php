@extends('layouts.app')

@section('title', 'Subscription Plans')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Choose Your Plan</h1>
        <p class="text-xl text-gray-600">Select the perfect plan for your needs</p>
    </div>

    <!-- Current Subscription Alert -->
    @if($currentSubscription)
        <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-blue-900 font-medium">
                        Current Plan: {{ $currentSubscription->plan->name }}
                    </p>
                    <p class="text-blue-700 text-sm">
                        Active until {{ $currentSubscription->end_date->format('M d, Y') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($plans as $plan)
            <div class="flex flex-col bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300
                {{ $currentSubscription && $currentSubscription->plan_id === $plan->id ? 'ring-2 ring-blue-500' : '' }}">

                <!-- Plan Header -->
                <div class="flex justify-between items-center bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white">
                    <h2 class="text-2xl font-bold">{{ $plan->name }}</h2>
                    <div class="flex items-baseline">
                        <span class="text-4xl font-extrabold">${{ number_format($plan->price, 2) }}</span>
                        <span class="ml-2 text-blue-100">/ {{ strtolower($plan->type->value) }}</span>
                    </div>
                </div>

                <!-- Plan Description -->
                @if($plan->description)
                    <div class="flex px-6 py-4 border-b border-gray-200">
                        <p class="text-gray-600">{{ $plan->description }}</p>
                    </div>
                @endif

                <!-- Plan Features -->
                <div class="flex grow px-6 py-6">
                    <ul class="space-y-3">
                        @forelse($plan->features as $feature)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-gray-700 font-medium">{{ $feature->name }}</p>
                                    @if($feature->description)
                                        <p class="text-gray-500 text-sm">{{ $feature->description }}</p>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-gray-500 text-sm">No specific features listed</li>
                        @endforelse
                    </ul>
                </div>

                <!-- Subscribe Button -->
                <div class="flex px-6 py-6 bg-gray-50">
                    @if(!$isAuthenticated)
                        <!-- Guest User - Show Login Button -->
                        <a href="/login" class="block w-full bg-blue-600 text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-blue-700 transition-colors">
                            Login to Subscribe
                        </a>
                    @elseif($currentSubscription && $currentSubscription->plan_id === $plan->id)
                        <!-- Current Plan -->
                        <button disabled class="block w-full bg-gray-300 text-gray-600 text-center px-4 py-3 rounded-md font-semibold cursor-not-allowed">
                            Current Plan
                        </button>
                    @elseif($currentSubscription)
                        <!-- Upgrade/Downgrade -->
                        @if($plan->price > $currentSubscription->plan->price)
                            <form action="/subscriptions/{{ $currentSubscription->id }}/upgrade" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="action" value="upgrade">
                                <button type="submit" class="block w-full bg-green-600 text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-green-700 transition-colors">
                                    Upgrade to This Plan
                                </button>
                            </form>
                        @else
                            <form action="/subscriptions/{{ $currentSubscription->id }}/downgrade" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="action" value="downgrade">
                                <button type="submit" class="block w-full bg-yellow-600 text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-yellow-700 transition-colors">
                                    Downgrade to This Plan
                                </button>
                            </form>
                        @endif
                    @else
                        <!-- No Subscription - Show Subscribe Button -->
                        <form action="/subscriptions/subscribe" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button type="submit" class="block w-full bg-blue-600 text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-blue-700 transition-colors">
                                Subscribe Now
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 text-lg">No plans available at the moment.</p>
            </div>
        @endforelse
    </div>

    <!-- Cancel Subscription Option -->
    @if($currentSubscription)
        <div class="mt-12 text-center">
            <button onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                    class="text-red-600 hover:text-red-800 font-medium">
                Cancel Current Subscription
            </button>
        </div>

        <!-- Cancel Modal -->
        <div id="cancelModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Cancel Subscription</h3>
                    <form action="/subscriptions/{{ $currentSubscription->id }}/cancel" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                                Please tell us why you're canceling:
                            </label>
                            <textarea name="reason" id="reason" rows="4" required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Your feedback helps us improve..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Keep Subscription
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Confirm Cancellation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
