<button {{ $attributes->merge(['class' => 'inline-flex items-center hover:opacity-80 rounded focus:outline-none focus:ring-2 focus:ring-caldy-500 focus:ring-offset-2 dark:focus:ring-offset-neutral-800 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>