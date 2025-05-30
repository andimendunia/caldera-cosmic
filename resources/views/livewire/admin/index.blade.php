<x-app-layout>

   <x-slot name="title">{{ __('Admin') }}</x-slot>

    <div class="py-12 text-neutral-800 dark:text-neutral-200">
        <div class="max-w-xl flex flex-col gap-y-6 mx-auto sm:px-6 lg:px-8">
            <div class="relative text-neutral h-32 sm:rounded-lg overflow-hidden mb-8 border border-dashed border-neutral-300 dark:border-neutral-500">
                <div class="absolute top-0 left-0 flex h-full items-center px-4 lg:px-8 text-neutral-500">
                    <div>
                        <div class="uppercase font-bold mb-2"><i class="icon-triangle-alert me-2"></i>{{ __('Peringatan') }}</div>
                        <div>{{ __('Kamu sedang mengakses halaman yang hanya diperuntukkan bagi superuser.') }}</div>
                    </div>
                </div>
            </div>
            <div>
                <h1 class="uppercase text-sm text-neutral-500 mb-4 px-8">{{ __('Akun') }}</h1>
                <div class="bg-white dark:bg-neutral-800 shadow overflow-hidden sm:rounded-lg divide-y divide-neutral-200 dark:text-white dark:divide-neutral-700">
                    <a href="{{ route('admin.account-manage') }}" class="block hover:bg-caldy-500 hover:bg-opacity-10" wire:navigate>
                        <div class="flex items-center px-6 py-5">
                            <div class="grow">
                                <div class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ __('Kelola akun') }}</div>
                                <div class="text-sm text-neutral-500">
                                    {{ __('Edit, nonaktifkan, atur ulang kata sandi') }}
                                </div>
                            </div>
                            <div class="text-lg">
                                <i class="icon-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div>
                <h1 class="uppercase text-sm text-neutral-500 mb-4 px-8">{{ __('Inventaris') }}</h1>
                <div class="bg-white dark:bg-neutral-800 shadow overflow-hidden sm:rounded-lg divide-y divide-neutral-200 dark:text-white dark:divide-neutral-700">
                    <a href="{{ route('admin.inventory-areas') }}" class="block hover:bg-caldy-500 hover:bg-opacity-10" wire:navigate>
                        <div class="flex items-center px-6 py-5">
                            <div class="grow">
                                <div class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ __('Kelola area') }}</div>
                                <div class="text-sm text-neutral-500">
                                    {{ __('Tambah atau edit area inventaris') }}
                                </div>
                            </div>
                            <div class="text-lg">
                                <i class="icon-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('admin.inventory-auths') }}" class="block hover:bg-caldy-500 hover:bg-opacity-10" wire:navigate>
                        <div class="flex items-center px-6 py-5">
                            <div class="grow">
                                <div class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ __('Kelola wewenang') }}</div>
                                <div class="text-sm text-neutral-500">
                                    {{ __('Tambah, edit, atau hapus wewenang inventaris') }}
                                </div>
                            </div>
                            <div class="text-lg">
                                <i class="icon-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('admin.inventory-currs') }}" class="block hover:bg-caldy-500 hover:bg-opacity-10" wire:navigate>
                        <div class="flex items-center px-6 py-5">
                            <div class="grow">
                                <div class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ __('Kelola mata uang') }}</div>
                                <div class="text-sm text-neutral-500">
                                    {{ __('Tambah atau edit mata uang inventaris') }}
                                </div>
                            </div>
                            <div class="text-lg">
                                <i class="icon-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('admin.inventory-budgets') }}" class="block hover:bg-caldy-500 hover:bg-opacity-10" wire:navigate>
                        <div class="flex items-center px-6 py-5">
                            <div class="grow">
                                <div class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ __('Kelola anggaran') }}</div>
                                <div class="text-sm text-neutral-500">
                                    {{ __('Tambah atau edit anggaran inventaris') }}
                                </div>
                            </div>
                            <div class="text-lg">
                                <i class="icon-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>     
    </div>
</x-app-layout>
