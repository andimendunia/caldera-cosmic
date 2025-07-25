<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

new #[Layout('layouts.app')] 
class extends Component {
    
    #[Url]
    public $view = 'raw-data';
    public array $view_titles = [];
    public array $view_icons = [];

    public function mount()
    {
        $this->view_titles = [       
            'raw-data'              => __('Data mentah'),
            'quality-analytics'     => __('Analitik Kualitas'),
            'production-analytics'  => __('Analitik Produksi'),
            'machine-performance'   => __('Kinerja Mesin'),
            'worker-performance'    => __('Kinerja Pekerja'),
        ];

        $this->view_icons = [
            'raw-data'              => 'icon-database',
            'quality-analytics'     => 'icon-shield-check',
            'production-analytics'  => 'icon-layers',
            'machine-performance'   => 'icon-rectangle-horizontal',
            'worker-performance'    => 'icon-users',
        ];
    }

    public function getViewTitle(): string
    {
        return $this->view_titles[$this->view] ?? '';
    }

    public function getViewIcon(): string
    {
        return $this->view_icons[$this->view] ?? '';
    }
};

?>

<x-slot name="title">{{ __('Sistem data kulit') }}</x-slot>

<x-slot name="header">
    <x-nav-insights-ldc></x-nav-insights-ldc>
</x-slot>

<div id="content" class="relative py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 text-neutral-800 dark:text-neutral-200">
    
    <div wire:key="ldc-data-index-nav" class="flex px-8 mb-6">
        <x-dropdown align="left">
            <x-slot name="trigger">
                <x-text-button type="button" class="flex gap-2 items-center ml-1">
                    <i class="{{ $this->getViewIcon() }}"></i>
                    <div class="text-2xl">{{ $this->getViewTitle() }}</div>
                    <i class="icon-chevron-down"></i>
                </x-text-button>
            </x-slot>
            <x-slot name="content">
                @foreach ($view_titles as $view_key => $view_title)
                <x-dropdown-link href="#" wire:click.prevent="$set('view', '{{ $view_key }}')" class="flex items-center gap-2">
                    <i class="{{ $view_icons[$view_key] }}"></i>
                    <span>{{ $view_title }}</span>
                    @if($view === $view_key)
                        <div class="ml-auto w-2 h-2 bg-caldy-500 rounded-full"></div>
                    @endif
                </x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>
    </div>
    <div wire:loading.class.remove="hidden" class="hidden h-96">
        <x-spinner></x-spinner>
    </div>
    <div wire:key="ldc-data-index-container" wire:loading.class="hidden">
        @switch($view)
            @case('raw-data')
            <livewire:insights.ldc.data.raw-data />                       
                @break
            @case('quality-analytics')
            <livewire:insights.ldc.data.quality-analytics />                       
                @break
            @case('production-analytics')
            <livewire:insights.ldc.data.production-analytics />                       
                @break
            @case('machine-performance')
            <livewire:insights.ldc.data.machine-performance />                       
                @break
            @case('worker-performance')
            <livewire:insights.ldc.data.worker-performance />                       
                @break
            @default
                <div wire:key="no-view" class="w-full py-20">
                    <div class="text-center text-neutral-300 dark:text-neutral-700 text-5xl mb-3">
                        <i class="icon-tv-minimal relative"><i
                                class="icon-circle-help absolute bottom-0 -right-1 text-lg text-neutral-500 dark:text-neutral-400"></i></i>
                    </div>
                    <div class="text-center text-neutral-400 dark:text-neutral-600">{{ __('Pilih tampilan') }}
                    </div>
                </div>                
        @endswitch
        <script>
            function progressApp() {
                return {
                    observeProgress() {               
                    const streamElement = document.querySelector('[wire\\:stream="progress"]');
                    
                    if (streamElement) {
                        const observer = new MutationObserver((mutations) => {
                                mutations.forEach(mutation => {
                                if (mutation.type === 'characterData' || mutation.type === 'childList') {
                                    const currentValue = streamElement.textContent;
                                    console.log('Stream value updated:', currentValue);
                                    
                                    // Do something with the captured value
                                    this.handleProgress(currentValue);
                                }
                                });
                        });
                        
                        observer.observe(streamElement, { 
                            characterData: true, 
                            childList: true,
                            subtree: true 
                        });
                    }

                    },

                    handleProgress(value) {
                    this.progress = value;
                    },
                };
            }
        </script>
    </div>
</div>