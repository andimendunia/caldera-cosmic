<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
class extends Component
{

};

?>

<x-slot name="title">{{ __('Bukan barang baru') . ' — ' . __('Inventaris') }}</x-slot>

<x-slot name="header">
    <x-nav-inventory-sub>{{ __('Bukan barang baru') }}</x-nav-inventory-sub>
</x-slot>

<div class="py-12 max-w-5xl mx-auto sm:px-6 lg:px-8 text-neutral-800 dark:text-neutral-200">
    <livewire:inventory.items.form />
    <hr class="border-neutral-200 dark:border-neutral-800 my-8" />
    <div class="max-w-lg mx-auto px-6 md:px-0">
        <livewire:comments.index />
    </div>
</div>
