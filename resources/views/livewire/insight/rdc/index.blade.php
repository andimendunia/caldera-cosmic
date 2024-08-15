<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\InsRubberBatch;
use Livewire\Attributes\On;

new #[Layout('layouts.app')] 
class extends Component {

    public string $code;

    #[On('updated')]
    public function with(): array
    {
        return [
            'batches' => InsRubberBatch::where('rdc_eval', 'queue')->get()
        ];
    }

    public function batchQuery()
    {
        $this->code = strtoupper(trim($this->code));
        if ($this->code) {
            $batch = InsRubberBatch::firstOrCreate(
                ['code' => $this->code]
            );
            $this->js('$dispatch("open-modal", "batch-info"); $dispatch("batch-load", { id: '. $batch->id .'})');
        } else {
            $this->js('notyfError("' . __('Kode tidak boleh kosong') . '")');
        }

    }

};

?>

<x-slot name="title">{{ __('Pendataan Kulit') }}</x-slot>

<x-slot name="header">
    <x-nav-insights-rdc></x-nav-insights-rdc>
</x-slot>

<div id="content" class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 text-neutral-800 dark:text-neutral-200">
    <div class="flex flex-col gap-y-8 sm:flex-row">
        <h1 class="grow text-2xl text-neutral-900 dark:text-neutral-100 px-8">
            {{ __('Hasil Uji per Batch') }}</h1>
        <div class="px-8">
            <form wire:submit="batchQuery" class="btn-group">
                <x-text-input class="w-20" wire:model="code" id="rdc-code" placeholder="{{ __('Kode') }}"></x-text-input->
                <x-secondary-button type="submit"><i class="fa fa-fw fa-chevron-right" wire:loading.class="hidden"></i><i class="fa fa-fw fa-spinner fa-spin-pulse hidden" wire:loading.class.remove="hidden"></i></x-secondary-button>
            </form>
        </div>
    </div>
    <div wire:key="batch-info">
        <x-modal name="batch-info">
            <livewire:insight.rdc.index-batch-info  />
        </x-modal>
    </div>
    <div wire:key="batch-test-create">
        <x-modal name="batch-test-create">
            <livewire:insight.rdc.index-batch-test-create  />
        </x-modal>
    </div>
    <div class="overflow-auto w-full mt-5">
        <div class="p-0 sm:p-1">
            <div class="bg-white dark:bg-neutral-800 shadow table sm:rounded-lg">
                <table wire:key="rdc-index-table" class="table">
                    <tr>
                        <th>{{ __('Diperbarui') }}</th>
                        <th>{{ __('Kode') }}</th>
                        <th>{{ __('Model') }}</th>
                        <th>{{ __('Warna') }}</th>
                        <th>{{ __('MCS') }}</th>
                        <th>{{ __('Hasil uji') }}</th>
                    </tr>
                    @foreach ($batches as $batch)
                        <tr wire:key="batch-tr-{{ $batch->id . $loop->index }}" tabindex="0"
                            x-on:click="$dispatch('open-modal', 'batch-test-create'); $dispatch('batch-test-create', { batch_id: '{{ $batch->id }}'})">
                            <td>
                                {{ $batch->updated_at }}
                            </td>
                            <td>
                                {{ $batch->code }}
                            </td>
                            <td>
                                {{ $batch->model }}
                            </td>
                            <td>
                                {{ $batch->color }}   
                            </td>
                            <td>
                                {{ $batch->mcs }}
                            </td>
                            <td>
                                {{ $batch->rdc_eval }}
                            </td>
                        </tr>
                    @endforeach
                </table>
                <div wire:key="batches-none">
                    @if (!$batches->count())
                        <div class="text-center py-12">
                            {{ __('Antrian kosong') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
