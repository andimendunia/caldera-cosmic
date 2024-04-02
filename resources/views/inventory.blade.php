<x-app-layout>

    <x-slot name="title">{{ __('Inventaris') }}</x-slot>

    <x-slot name="header">
        <header class="bg-white dark:bg-neutral-800 shadow">
            <div class="flex justify-between max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div>
                    <h2 class="font-semibold text-xl text-neutral-800 dark:text-neutral-200 leading-tight">
                        <div class="inline-block py-6">{{ __('Inventaris') }}</div>
                    </h2>
                </div>
                <div class="space-x-8 -my-px ml-10 flex">
                    <x-nav-link href="{{ route('inventory.items.index') }}" :active="request()->routeIs('inventory/items*')" wire:navigate>
                        <i class="fa mx-2 fa-fw fa-search text-sm"></i>
                    </x-nav-link>
                    <x-nav-link href="{{ route('inventory.circs.index') }}" :active="request()->routeIs('inventory/circs*')" wire:navigate>
                        <i class="fa mx-2 fa-fw fa-arrow-right-arrow-left text-sm"></i>
                    </x-nav-link>
                    <x-nav-link href="{{ route('inventory.manage.index') }}" :active="request()->routeIs('inventory/manage*')" wire:navigate>
                        <i class="fa mx-2 fa-fw fa-ellipsis-h text-sm"></i>
                    </x-nav-link>
                </div>
            </div>
        </header>
    </x-slot>

    <div class="max-w-xl lg:max-w-2xl mx-auto px-4 py-16">
        <h2 class="text-4xl font-extrabold dark:text-white">{{ __('Selamat datang di Inventaris') }}</h2>
        <p class="mt-4 mb-12 text-lg text-neutral-500">{{ __('Cari dan buat sirkulasi barang.') }}</p>
        <p class="mb-4 text-lg font-normal text-neutral-500 dark:text-neutral-400">
            {{ __('Mulai dengan mengklik menu navigasi di pojok kanan atas.') }}</p>

        <ul class="space-y-4 text-left text-neutral-500 dark:text-neutral-400">
            <li class="flex items-center space-x-3 rtl:space-x-reverse">
                <i class="fa fa-search fa-fw me-2"></i>
                <span><span
                        class="font-semibold text-neutral-900 dark:text-white">{{ __('Cari') }}</span>{{ ' ' . __('untuk menjelajah barang dan melakukan sirkulasi barang.') }}</span>
            </li>
            <li class="flex items-center space-x-3 rtl:space-x-reverse">
                <i class="fa fa-arrow-right-arrow-left fa-fw me-2"></i>
                <span><span
                        class="font-semibold text-neutral-900 dark:text-white">{{ __('Sirkulasi') }}</span>{{ ' ' . __('untuk melihat sirkulasi barang yang telah dibuat beserta statusnya.') }}</span>
            </li>
            <li class="flex items-center space-x-3 rtl:space-x-reverse">
                <i class="fa fa-ellipsis-h fa-fw me-2"></i>
                <span><span class="font-semibold text-neutral-900 dark:text-white">{{ __('Administrasi') }}</span>
                    {{ ' ' . __('untuk mengelola barang beserta propertinya dan lainnya.') }}</span>
            </li>
        </ul>
    </div>
</x-app-layout>
