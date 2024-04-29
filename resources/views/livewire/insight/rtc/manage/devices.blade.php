<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

use App\Models\InsRtcDevice;
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
        $devices = InsRtcDevice::where(function (Builder $query) use ($q) {
            $query->orWhere('line', 'LIKE', '%' . $q . '%')->orWhere('ip_address', 'LIKE', '%' . $q . '%');
        })
            ->orderBy('line')
            ->paginate($this->perPage);

        return [
            'devices' => $devices,
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
<x-slot name="title">{{ __('Perangkat') . ' — ' . __('Rubber thickness control') }}</x-slot>
<x-slot name="header">
    <header class="bg-white dark:bg-neutral-800 shadow">
        <div class="flex justify-between max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div>
                <h2 class="font-semibold text-xl text-neutral-800 dark:text-neutral-200 leading-tight">
                    <x-link href="{{ route('insight.rtc.manage.index') }}" class="inline-block py-6" wire:navigate><i
                            class="fa fa-arrow-left"></i></x-link><span class="ml-4">{{ __('Perangkat RTC') }}</span>
                </h2>
            </div>
        </div>
    </header>
</x-slot>
<div id="content" class="py-12 max-w-2xl mx-auto sm:px-3 text-neutral-800 dark:text-neutral-200">
    <div>
      <div class="flex justify-between px-6 sm:px-0">
         <div class="w-40">
            <x-text-input-search wire:model.live="q" id="inv-q"
                placeholder="{{ __('CARI') }}"></x-text-input-search>
        </div>
         <x-secondary-button type="button" class="my-auto" x-data=""
         x-on:click.prevent="$dispatch('open-modal', 'create-device')">{{ __('Buat') }}</x-secondary-button>  

 
      </div>
      <x-modal name="create-device">
         <livewire:insight.rtc.manage.devices-form lazy wire:key="create-device" />
     </x-modal>
        <div class="w-full mt-5">
            <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg">
                <table wire:key="devices-table" class="table">
                    <tr>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Line') }}</th>
                        <th>{{ __('Alamat IP') }}</th>
                    </tr>
                    @foreach ($devices as $device)
                        <tr wire:key="device-tr-{{ $device->id . $loop->index }}" tabindex="0"
                            x-on:click="$dispatch('open-modal', 'edit-device-{{ $device->id }}')">
                            <td>
                                {{ $device->id }}
                            </td>
                            <td>
                                {{ $device->line }}
                            </td>
                            <td>
                                {{ $device->ip_address }}
                            </td>
                        </tr>
                        @can('manage', $device)
                            <x-modal :name="'edit-device-' . $device->id">
                                <livewire:insight.rtc.manage.devices-form wire:key="device-lw-{{ $device->id . $loop->index }}"
                                    :device="$device" lazy />
                            </x-modal>
                        @endcan
                    @endforeach
                </table>
                <div wire:key="devices-none">
                    @if (!$devices->count())
                        <div class="text-center py-12">
                            {{ __('Tak ada perangkat ditemukan') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div wire:key="observer" class="flex items-center relative h-16">
            @if (!$devices->isEmpty())
                @if ($devices->hasMorePages())
                    <div wire:key="more" x-data="{
                        observe() {
                            const observer = new IntersectionObserver((devices) => {
                                devices.forEach(device => {
                                    if (device.isIntersecting) {
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