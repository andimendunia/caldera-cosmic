<header class="bg-white dark:bg-neutral-800 shadow">
   <div class="flex justify-between max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
       <div>
           <h2 class="font-semibold text-xl text-neutral-800 dark:text-neutral-200 leading-tight">
               <x-link href="{{ route('insights.rdc.manage.index') }}" class="inline-block py-6" wire:navigate><i
                       class="icon-arrow-left"></i></x-link><span class="ml-4"><span class="hidden sm:inline">{{ __('Sistem data rheometer') }}</span><span class="sm:hidden inline">{{ __('RDC') }}</span></span>
           </h2>
       </div>
   </div>
</header>