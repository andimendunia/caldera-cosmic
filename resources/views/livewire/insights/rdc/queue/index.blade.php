<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\InsRubberBatch;
use App\Models\InsRdcTest;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Validator;

new #[Layout('layouts.app')] 
class extends Component {

    public string $code = '';

    public bool $immediate_queue = false;

    public function mount()
    {
        $this->immediate_queue = Gate::allows('manage', InsRdcTest::class);
    }

    #[On('updated')]
    public function with(): array
    {
        return [
            'batches' => InsRubberBatch::where('rdc_queue', 1)->orderBy('updated_at')->get()
        ];
    }

    public function batchQuery()
    {
        $this->code = strtoupper(trim(str_replace('#', '', $this->code)));

        $validator = Validator::make(
            ['code' => $this->code ],
            ['code' => 'required|string|min:1|max:20'],
            [
                'required'  => __('Kode wajib diisi'),
                'string'    => __('Kode harus berupa teks/string.'),
                'min'       => __('Kode minimal 1 karakter'),
                'max'       => __('Kode maksimal 50 karakter')
            ]
        );

        if ($validator->fails()) {

            $errors = $validator->errors();
            $error = $errors->first('code');
            $this->js('toast("'.$error.'", { type: "danger" })'); 

        } else {

            $batch = InsRubberBatch::with('ins_omv_metric', 'ins_rdc_test')->firstOrCreate(
                    ['code' => $this->code]
                );

            if ($this->immediate_queue) {

                if ($batch->rdc_queue == 1) {
                    $this->js('toast("'. __('Sudah diantrikan') . '", { type: "danger" })'); 

                } else {
                    $batch->update([
                        'rdc_queue' => 1
                    ]);
                    $this->js('toast("' . __('Ditambahkan ke antrian') . '", { type: "success" })');
                    $this->dispatch('updated');
                }

            } else {

                $omv_metric = $batch->ins_omv_metric;
                $rdc_test = $batch->ins_rdc_test;

                $this->js('$dispatch("open-modal", "batch-info"); $dispatch("batch-load", { 
                    id: ' . $batch->id . ', 
                    updated_at_human: "' . $batch->updated_at->diffForHumans() . '", 
                    code: "' . $batch->code . '", 
                    model: "' . $batch->model . '", 
                    color: "' . $batch->color . '", 
                    mcs: "' . $batch->mcs . '", 
                    code_alt: "' . $batch->code_alt . '", 
                    omv_eval: "' . ( $omv_metric ? $omv_metric->eval : '' ) . '", 
                    omv_eval_human: "' . ( $omv_metric ? $omv_metric->evalHuman() : '' ) . '",
                    rdc_eval: "' . ( $rdc_test ? $rdc_test->eval : '' ) . '", 
                    rdc_eval_human: "' . ( $rdc_test ? $rdc_test->evalHuman() : '' ) . '",
                    rdc_tests_count: "' . $batch->ins_rdc_tests->count() . '"
                })');
            }
            $this->reset(['code']);

        }
    }
};

?>

<x-slot name="title">{{ __('Sistem data rheometer') }}</x-slot>

<x-slot name="header">
    <x-nav-insights-rdc></x-nav-insights-rdc>
</x-slot>

<div id="content" class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 text-neutral-800 dark:text-neutral-200">
    <div class="flex flex-col items-center gap-y-8 sm:flex-row">
        <h1 class="grow text-2xl text-neutral-900 dark:text-neutral-100 px-8">
            {{ __('Antrian pengujian') }}</h1>
        @can('manage', InsRdcTest::class)
            <div>
                <x-toggle name="auto_queue" wire:model="immediate_queue"
                    :checked="$immediate_queue ? true : false">{{ __('Langsung antrikan') }}
                </x-toggle>
            </div>
        @endcan
        <div class="px-8">
            <form wire:submit="batchQuery" class="btn-group">
                <x-text-input class="w-20" wire:model="code" id="rdc-code" placeholder="{{ __('Kode') }}"></x-text-input->
                <x-secondary-button type="submit"><i class="icon-chevron-right" wire:loading.class="hidden"></i><i class="icon-loader-circle icon-spin-pulse hidden" wire:loading.class.remove="hidden"></i></x-secondary-button>
            </form>
        </div>
    </div>
    <div wire:key="modals">
        <div wire:key="batch-info">
            <x-modal name="batch-info">
                <livewire:insights.rdc.queue.batch-info />
            </x-modal>
        </div>
        @can('manage', InsRdcTest::class)
        <div wire:key="test-create">
            <x-modal name="test-create" maxWidth="2xl">
                <livewire:insights.rdc.queue.test-create />
            </x-modal>
        </div>
        @endcan
    </div>
    <div wire:poll.30s class="overflow-auto w-full my-8">
        <div class="p-0 sm:p-1">
            <div class="bg-white dark:bg-neutral-800 shadow table sm:rounded-lg">
                <table wire:key="rdc-index-table" class="table">
                    <tr>
                        <th>{{ __('Nomor batch') }}</th>
                        <th>{{ __('Model/Warna/MCS') }}</th>
                        <th>{{ __('Diperbarui') }}</th>
                        <th></th>
                    </tr>
                    @foreach ($batches as $batch)
                        <tr wire:key="batch-tr-{{ $batch->id . $loop->index }}" tabindex="0"
                            x-on:click="$dispatch('open-modal', 'test-create'); $dispatch('test-create', { id: '{{ $batch->id }}'})">
                            <td>
                                {{ $batch->code }}
                            </td>
                            <td>
                                {{ ($batch->model ? $batch->model : '-') . ' / ' . ($batch->color ? $batch->color : '-') . ' / ' . ($batch->mcs ? $batch->mcs : '-') }}
                            </td>
                            <td>
                                {{ $batch->updated_at }}
                            </td>
                            <td>
                                {{ $batch->updated_at->diffForHumans() }}
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

@script
<script>
    document.addEventListener('keypress', (event) => {
        if (event.key === '#') {
            const rdcCodeInput = document.getElementById('rdc-code');
            if (rdcCodeInput) {
                event.preventDefault();
                rdcCodeInput.focus();
            }
        }
    });
</script>
@endscript
