@props(['disabled' => false, 'id', 'icon'])

<div class="relative h-full">
    <label for="{{ $id }}" class="absolute top-2 left-3 opacity-30 leading-none text-sm"><i class="icon-search"></i></label>
    <input id="{{ $id }}" type="search" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'block w-full h-full text-sm pl-10 py-1 border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 dark:text-neutral-300 focus:border-caldy-500 dark:focus:border-caldy-600 focus:ring-caldy-500 dark:focus:ring-caldy-600 rounded-md shadow-sm']) !!}>
</div>