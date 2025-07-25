<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Illuminate\Database\Eloquent\Builder;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public $q = '';

    public $perPage = 20;

    #[On('updated')]
    public function with(): array
    {
        $q = trim($this->q);
        
        // Mock data for development - will be replaced with actual InsCtcMachine model
        $allMachines = [
            [
                'id' => 1,
                'line' => 3,
                'ip_address' => '172.70.86.13',
                'name' => 'CTC Line 3',
                'is_active' => true
            ],
            [
                'id' => 2,
                'line' => 4,
                'ip_address' => '172.70.86.14',
                'name' => 'CTC Line 4',
                'is_active' => true
            ],
            [
                'id' => 3,
                'line' => 5,
                'ip_address' => '172.70.86.15',
                'name' => 'CTC Line 5',
                'is_active' => false
            ]
        ];

        // Apply search filter
        if ($q) {
            $allMachines = array_filter($allMachines, function($machine) use ($q) {
                return stripos($machine['line'], $q) !== false || 
                       stripos($machine['ip_address'], $q) !== false ||
                       stripos($machine['name'], $q) !== false;
            });
        }

        // TODO: Replace with actual database query when backend is ready
        // $machines = InsCtcMachine::where(function (Builder $query) use ($q) {
        //     $query->orWhere('line', 'LIKE', '%' . $q . '%')
        //           ->orWhere('ip_address', 'LIKE', '%' . $q . '%')
        //           ->orWhere('name', 'LIKE', '%' . $q . '%');
        // })
        // ->orderBy('line')
        // ->paginate($this->perPage);

        return [
            'machines' => collect($allMachines)->take($this->perPage),
        ];
    }

    public function updating($property)
    {
        if ($property == 'q') {
            $this->reset('perPage');
        }
    }

    public function loadMore()
    {
        $this->perPage += 20;
    }
};
?>
<x-slot name="title">{{ __('Mesin') . ' — ' . __('Kendali tebal calendar') }}</x-slot>
<x-slot name="header">
    <x-nav-insights-ctc-sub />
</x-slot>
<div id="content" class="py-12 max-w-2xl mx-auto sm:px-3 text-neutral-800 dark:text-neutral-200">
    <div>
        <div class="flex flex-col sm:flex-row gap-y-6 justify-between px-6">
            <h1 class="text-2xl text-neutral-900 dark:text-neutral-100">{{ __('Mesin') }}</h1>
            <div x-data="{ open: false }" class="flex justify-end gap-x-2">
                @can('superuser')
                    <x-secondary-button type="button" 
                        x-on:click.prevent="$dispatch('open-modal', 'machine-create')"><i class="icon-plus"></i></x-secondary-button>
                @endcan
                <x-secondary-button type="button" x-on:click="open = true; setTimeout(() => $refs.search.focus(), 100)" x-show="!open"><i class="icon-search"></i></x-secondary-button>
                <div class="w-40" x-show="open" x-cloak>
                    <x-text-input-search wire:model.live="q" id="inv-q" x-ref="search"
                        placeholder="{{ __('CARI') }}"></x-text-input-search>
                </div>
            </div>
        </div>
        <div wire:key="machine-create">
            <x-modal name="machine-create" maxWidth="sm">
                <livewire:insights.ctc.manage.machine-create />
            </x-modal>
        </div>
        <div wire:key="machine-edit"> 
            <x-modal name="machine-edit" maxWidth="sm">
                <livewire:insights.ctc.manage.machine-edit wire:key="machine-edit" />
            </x-modal>
        </div>
        <div class="overflow-auto w-full my-8">
            <div class="p-0 sm:p-1">
                <div class="bg-white table dark:bg-neutral-800 shadow sm:rounded-lg">
                    <table wire:key="machines-table" class="table">
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Line') }}</th>
                            <th>{{ __('Nama') }}</th>
                            <th>{{ __('Alamat IP') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                        @foreach ($machines as $machine)
                            <tr wire:key="machine-tr-{{ $machine['id'] . $loop->index }}" tabindex="0"
                                x-on:click="$dispatch('open-modal', 'machine-edit'); $dispatch('machine-edit', { id: {{ $machine['id'] }} })">
                                <td>
                                    {{ $machine['id'] }}
                                </td>
                                <td>
                                    {{ $machine['line'] }}
                                </td>
                                <td>
                                    {{ $machine['name'] }}
                                </td>
                                <td>
                                    {{ $machine['ip_address'] }}
                                </td>
                                <td>
                                    @if($machine['is_active'])
                                        <span class="inline-flex px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">{{ __('Aktif') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">{{ __('Nonaktif') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    <div wire:key="machines-none">
                        @if (!$machines->count())
                            <div class="text-center py-12">
                                {{ __('Tak ada mesin ditemukan') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div wire:key="observer" class="flex items-center relative h-16">
            @if (!$machines->isEmpty())
                @if ($machines->count() >= $this->perPage)
                    <div wire:key="more" x-data="{
                        observe() {
                            const observer = new IntersectionObserver((machines) => {
                                machines.forEach(machine => {
                                    if (machine.isIntersecting) {
                                        @this.loadMore()
                                    }
                                })
                            })
                            observer.observe(this.$el)
                        }
                    }" x-init="observe"></div>
                    <x-spinner class="sm" />
                @else
                    <div class="mx-auto">{{ __('Tidak ada lagi') }}</div>
                @endif
            @endif
        </div>
    </div>
</div>