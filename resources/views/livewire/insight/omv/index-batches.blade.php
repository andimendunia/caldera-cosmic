<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\InsOmvMetric;
use Carbon\Carbon;

new class extends Component {

    public int $line = 0;

    #[On('line-fetched')]
    public function setLine($line)
    {
        $this->line = $line;
    }

    public function with(): array
    {
        $metrics = InsOmvMetric::where('updated_at', '>=', Carbon::now()->subDay())->where('line', $this->line);
        $metrics = $metrics->orderBy('updated_at', 'desc')->limit(5)->get();

        return [
            'metrics' => $metrics,
        ];
    }
};

?>

<div wire:poll.5s class="w-64 bg-white dark:bg-neutral-800  bg-opacity-80 dark:bg-opacity-80 shadow rounded-lg">
    <div wire:key="modals"> 
        <x-modal name="metric-show" maxWidth="2xl">
            <livewire:insight.omv.summary.metric-show />
        </x-modal>
    </div>
    <div class="pt-6">
        <div class="flex justify-between text-neutral-500 text-sm px-6 pb-6 uppercase">
                <div><i class="fa fa-history me-2"></i>{{ __('Riwayat') }}</div>
            @if($line)
                <div>{{ __('Line') . ' ' . $line }}</div>
            @else
                <div x-on:click="$dispatch('open-modal', 'omv-worker-unavailable');" class="text-red-500 cursor-pointer text-sm uppercase">{{ __('Line') }}<i class="fa fa-exclamation-circle ms-2"></i></div>
            @endif
        </div>
        <hr class="border-neutral-200 dark:border-neutral-700 opacity-85" />
        @if($metrics->isEmpty())
            <div class="flex">
                <div class="my-auto py-6 text-sm text-center w-full">
                    {{ __('Tak ada riwayat terakhir') }}
                </div>
            </div>
        @else
            <ul class="py-3">
                @foreach ($metrics as $metric)
                    <li class="w-full hover:bg-caldy-500 hover:bg-opacity-10">
                        <x-link href="#" x-on:click.prevent="$dispatch('open-modal', 'metric-show'); $dispatch('metric-show', { id: '{{ $metric->id }}'})" class="grid gap-y-1 px-6 py-3">
                            <div class="text-sm text-neutral-500">
                                {{ $metric->updated_at->diffForHumans() }}
                            </div>
                            <div>
                                <div class="uppercase">{{ $metric->ins_rubber_batch->code ?? __('Tanpa kode') }}</div>
                                <div class="flex flex-wrap gap-1 -mx-2 text-sm">
                                    <x-pill class="inline-block uppercase" 
                                        color="{{ $metric->eval === 'on_time' ? 'green' : ($metric->eval === 'too_late' || $metric->eval === 'too_soon' ? 'red' : 'neutral') }}">{{ $metric->evalHuman() }}</x-pill>
                                    @if($metric->ins_rubber_batch)
                                    <x-pill class="inline-block uppercase"
                                    color="{{ $metric->ins_rubber_batch->rdc_eval === 'queue' ? 'yellow' : ($metric->ins_rubber_batch->rdc_eval === 'pass' ? 'green' : ($metric->ins_rubber_batch->rdc_eval === 'fail' ? 'red' : 'neutral')) }}">{{ 'RHEO: ' . $metric->ins_rubber_batch->rdcEvalHuman() }}</x-pill>                             
                                    @endif
                                </div>
                            </div>
                        </x-link>
                    </li>
                @endforeach
            </ul>
        @endif
    </div> 
</div>
