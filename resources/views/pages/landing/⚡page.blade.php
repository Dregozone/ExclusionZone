<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.empty')] class extends Component
{
    //
};
?>

<div>
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-black to-gray-900 text-white">
      <!-- Hero Section -->
      <section class="relative h-screen flex items-center justify-center text-center px-4">
          <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1593627178095-920620111045?auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center opacity-20"></div>
          <div class="relative z-10 max-w-3xl">
              <h1 class="text-5xl md:text-6xl font-bold mb-6 drop-shadow-lg">
                  Welcome to <span class="text-green-400">Exclusion Zone</span>
              </h1>
              <p class="text-xl mb-8 text-gray-300">
                  Survive the wasteland, build your legacy, and uncover the secrets of the Zone
              </p>
              <div class="space-x-4">
                  <a href="{{ route('register') }}"
                     class="inline-flex items-center px-6 py-3 border border-green-500 text-green-400 bg-black hover:bg-green-900 transition duration-300 rounded-lg shadow-lg">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4"></path>
                      </svg>
                      Register
                  </a>
                  <a href="{{ route('login') }}"
                     class="inline-flex items-center px-6 py-3 bg-green-600 text-black hover:bg-green-700 transition duration-30
                     rounded-lg shadow-lg">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5.882V19m0-24h2m-2 2h6m-6 0v6m0 0h6m-6 0v6m0-6h2m-2 0l-2 2m2-2l2 2"></path>
                      </svg>
                      Login
                  </a>
              </div>
          </div>
      </section>

      <!-- About Section -->
      <section class="py-16 px-4">
          <div class="max-w-4xl mx-auto">
              <h2 class="text-3xl font-bold mb-8 text-center">About Exclusion Zone</h2>
              <div class="grid md:grid-cols-2 gap-8 items-center">
                  <div>
                      <img src="https://images.unsplash.com/photo-1593627178095-920620111045?auto=format&fit=crop&w=1920&q=80"
                           alt="Post-apocalyptic wasteland"
                           class="rounded-lg shadow-lg w-full h-64 object-cover">
                  </div>
                  <div>
                      <p class="text-gray-300 mb-4">
                          Exclusion Zone is a multiplayer text-based RPG set in a post-nuclear war wasteland.
                          Players will navigate through dangerous zones, scavenge for resources, and uncover
                          the mysteries of the Zone while battling other survivors in a harsh, unforgiving world.
                      </p>
                      <p class="text-gray-300">
                          Based on the atmospheric tone of the Stalker games and the existential dread of Roadside Picnic,
                          this game offers a unique blend of survival mechanics and narrative-driven gameplay.
                      </p>
                  </div>
              </div>
          </div>
      </section>

      <!-- Features Section -->
      <section class="py-16 px-4 bg-gray-800">
          <div class="max-w-4xl mx-auto">
              <h2 class="text-3xl font-bold mb-8 text-center">Key Features</h2>
              <div class="grid md:grid-cols-3 gap-8">
                  <div class="text-center p-6 bg-gray-700 rounded-lg">
                      <div class="w-16 h-16 mx-auto mb-4 bg-green-600 rounded-full flex items-center justify-center">
                          <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0
   0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                      </div>
                      <h3 class="text-xl font-bold mb-2">Multiplayer Survival</h3>
                      <p class="text-gray-300">Compete with other players in a shared, persistent world</p>
                  </div>
                  <div class="text-center p-6 bg-gray-700 rounded-lg">
                      <div class="w-16 h-16 mx-auto mb-4 bg-green-600 rounded-full flex items-center justify-center">
                          <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L18 21H5L13 10zM13 21H21M8 10V3L3 21H10"></path>
                          </svg>
                      </div>
                      <h3 class="text-xl font-bold mb-2">Dynamic World</h3>
                      <p class="text-gray-300">Explore a procedurally generated wasteland with ever-changing dangers</p>
                  </div>
                  <div class="text-center p-6 bg-gray-700 rounded-lg">
                      <div class="w-16 h-16 mx-auto mb-4 bg-green-600 rounded-full flex items-center justify-center">
                          <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2zm7-1a5 5 0 015 5v3h-10v-3a5 5
  0 015-5z"></path>
                          </svg>
                      </div>
                      <h3 class="text-xl font-bold mb-2">Survival Mechanics</h3>
                      <p class="text-gray-300">Manage resources, avoid radiation, and survive the harsh environment</p>
                  </div>
              </div>
          </div>
      </section>

      <!-- Call to Action Section -->
      <section class="py-16 px-4">
          <div class="max-w-3xl mx-auto text-center">
              <h2 class="text-3xl font-bold mb-6">Ready to Survive the Zone?</h2>
              <p class="text-gray-300 mb-8">
                  Join thousands of players in this immersive post-apocalyptic experience.
                  Your survival depends on your choices.
              </p>
              <div class="flex flex-col sm:flex-row justify-center gap-4">
                  <a href="{{ route('register') }}"
                     class="inline-flex items-center px-6 py-3 border border-green-500 text-green-400 bg-black hover:bg-green-900 transition duration-300 rounded-lg shadow-lg">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4"></path>
                      </svg>
                      Register Now
                  </a>
                  <a href="{{ route('login') }}"
                     class="inline-flex items-center px-6 py-3 bg-green-600 text-black hover:bg-green-700 transition duration-300 rounded-lg shadow-lg">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5.882V19m0-24h2m-2 2h6m-6 0v6m0 0h6m-6 0v6m0-6h2m-2 0l-2 2m2-2l2 2"></path>
                      </svg>
                      Login
                  </a>
              </div>
          </div>
      </section>

      <!-- Footer -->
      <footer class="py-8 px-4 bg-gray-900 border-t border-gray-800">
          <div class="max-w-4xl mx-auto text-center text-gray-400">
              <p>&copy; {{ date('Y') }} Exclusion Zone. All rights reserved.</p>
              <p class="mt-2">This site is a fictional game concept and not affiliated with any real-world entities.</p>
          </div>
      </footer>
  </div>
</div>
