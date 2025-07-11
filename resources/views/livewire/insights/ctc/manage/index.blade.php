<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {};

?>
<x-slot name="title">{{ __('Kelola') . ' — ' . __('Kendali tebal calendar') }}</x-slot>

<x-slot name="header">
    <x-nav-insights-ctc></x-nav-insights-ctc>
</x-slot>

<div class="py-12">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8 text-neutral-600 dark:text-neutral-400">
        <h1 class="text-2xl text-neutral-900 dark:text-neutral-100 px-8">
            {{ __('Kelola') }}</h1>
        <div class="grid grid-cols-1 gap-1 my-8 ">
            <x-card-link href="{{ route('insights.ctc.manage.auths') }}" wire:navigate>
                <div class="flex px-8">
                    <div>
                        <div class="flex pr-5 h-full text-neutral-600 dark:text-neutral-400">
                            <div class="m-auto"><i class="icon-user-lock"></i></div>
                        </div>
                    </div>
                    <div class="grow truncate py-4">
                        <div class="truncate text-lg font-medium text-neutral-900 dark:text-neutral-100">
                            {{ __('Kelola wewenang') }}
                        </div>
                        <div class="truncate text-sm text-neutral-600 dark:text-neutral-400">
                            {{ __('Kelola wewenang pengguna CTC') }}
                        </div>
                    </div>
                </div>
            </x-card-link>
            <x-card-link href="{{ route('insights.ctc.manage.machines') }}" wire:navigate>
                <div class="flex px-8">
                    <div>
                        <div class="flex pr-5 h-full text-neutral-600 dark:text-neutral-400">
                            <div class="m-auto"><i class="icon-cpu"></i></div>
                        </div>
                    </div>
                    <div class="grow truncate py-4">
                        <div class="truncate text-lg font-medium text-neutral-900 dark:text-neutral-100">
                            {{ __('Kelola mesin') }}
                        </div>
                        <div class="truncate text-sm text-neutral-600 dark:text-neutral-400">
                            {{ __('Kelola mesin yang ada di line produksi') }}
                        </div>
                    </div>
                </div>
            </x-card-link>
            <x-card-link href="{{ route('insights.ctc.manage.recipes') }}" wire:navigate>
                <div class="flex px-8">
                    <div>
                        <div class="flex pr-5 h-full text-neutral-600 dark:text-neutral-400">
                            <div class="m-auto"><i class="icon-flask-conical"></i></div>
                        </div>
                    </div>
                    <div class="grow truncate py-4">
                        <div class="truncate text-lg font-medium text-neutral-900 dark:text-neutral-100">
                            {{ __('Kelola resep') }}
                        </div>
                        <div class="truncate text-sm text-neutral-600 dark:text-neutral-400">
                            {{ __('Kelola resep dan rekomendasi untuk HMI') }}
                        </div>
                    </div>
                </div>
            </x-card-link>
        </div>
    </div>
</div>