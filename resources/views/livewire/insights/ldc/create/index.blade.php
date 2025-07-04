<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] 
class extends Component {

};



?>

<x-slot name="title">{{ __('Sistem data kulit') }}</x-slot>

<x-slot name="header">
    <x-nav-insights-ldc></x-nav-insights-ldc>
</x-slot>

<div id="content" class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8 text-neutral-800 dark:text-neutral-200">
    @if (!Auth::user())
        <div class="flex flex-col items-center gap-y-6 px-6 py-20">
            <div class="text-center text-neutral-300 dark:text-neutral-700 text-5xl">
                <i class="icon-circle-alert"></i>
            </div>
            <div class="text-center text-neutral-500 dark:text-neutral-600">
                {{ __('Masuk terlebih dahulu untuk memasukkan data kulit') }}
            </div>
            <div>
                <a href="{{ route('login', ['redirect' => url()->current()]) }}" wire:navigate
                    class="flex items-center px-6 py-3 mb-3 text-white bg-caldy-600 rounded-md sm:mb-0 hover:bg-caldy-700 sm:w-auto">
                    {{ __('Masuk') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-1" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    @else
        <div class="relative bg-white dark:bg-neutral-800 shadow rounded-lg overflow-hidden">
            <div id="ldc-create-groups" class="border-b border-neutral-100 dark:border-neutral-700 overflow-x-auto">
                <livewire:insights.ldc.create.groups />
            </div>
            <div class="flex w-full items-stretch">
                <div class="border-r border-neutral-100 dark:border-neutral-700">
                    <div class="w-40 overflow-y-auto">
                        <livewire:insights.ldc.create.hides />
                    </div>
                </div>
                <livewire:insights.ldc.create.form />
                <div class="border-l border-neutral-100 dark:border-neutral-700">
                    <div class="w-64 overflow-y-auto">
                        <livewire:insights.ldc.create.quotas />
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
