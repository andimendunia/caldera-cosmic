<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

use App\InsStc;
use App\Models\InsStcDSum;
use App\Models\InsStcDLog;
use App\Models\InsStcMachine;
use App\Traits\HasDateRangeFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {

    use HasDateRangeFilter;

    #[Url]
    public string $start_at = '';

    #[Url]
    public string $end_at = '';

    #[Url]
    public $line;

    #[Url]
    public string $position = '';

    public array $lines = [];
    public array $deviationSummary = [];
    public array $severityBreakdown = [];
    public array $lineDeviations = [];

    public function mount()
    {
        if(!$this->start_at || !$this->end_at)
        {
            $this->setThisWeek();
        }

        $this->lines = InsStcMachine::orderBy('line')->get()->pluck('line')->toArray();
    }

    #[On('update')]
    public function updated()
    {
        $this->calculateDeviations();
        $this->renderCharts();
    }

    private function calculateDeviations()
    {
        $start = Carbon::parse($this->start_at);
        $end = Carbon::parse($this->end_at)->endOfDay();
        $targets = InsStc::$target_values; // [75, 73, 68, 63, 58, 53, 43, 43]

        $query = InsStcDSum::with(['ins_stc_machine'])
            ->when($this->line, function (Builder $query) {
                $query->whereHas('ins_stc_machine', function (Builder $query) {
                    $query->where('line', $this->line);
                });
            })
            ->when($this->position, function (Builder $query) {
                $query->where('position', $this->position);
            })
            ->whereBetween('created_at', [$start, $end]);

        $dSums = $query->get();

        $totalMeasurements = 0;
        $totalSections = 0;
        $totalDeviations = 0;
        $deviationSections = 0;
        $majorPlusDeviations = 0; // Major (6-9°C) + Critical (>9°C)
        $majorPlusSections = 0;
        $criticalDeviations = 0; // Only Critical (>9°C)
        $criticalSections = 0;
        $severityCount = ['minor' => 0, 'major' => 0, 'critical' => 0];
        $lineStats = [];

        foreach ($dSums as $dSum) {
            $hbValues = json_decode($dSum->hb_values, true) ?? [];
            $line = $dSum->ins_stc_machine->line;

            if (!isset($lineStats[$line])) {
                $lineStats[$line] = [
                    'total' => 0,
                    'deviations' => 0,
                    'minor' => 0,
                    'major' => 0,
                    'critical' => 0
                ];
            }

            $lineStats[$line]['total']++;
            $totalMeasurements++;

            // Check each zone for deviations (8 sections per measurement)
            for ($i = 0; $i < 8; $i++) {
                $totalSections++;
                
                if (isset($hbValues[$i]) && isset($targets[$i])) {
                    $deviation = abs($hbValues[$i] - $targets[$i]);
                    
                    if ($deviation >= 3) { // New threshold: only count deviations ≥3°C
                        $totalDeviations++;
                        $deviationSections++;
                        $lineStats[$line]['deviations']++;

                        // Classify severity with new ranges
                        if ($deviation > 9) { // Critical: >9°C
                            $severityCount['critical']++;
                            $lineStats[$line]['critical']++;
                            $majorPlusDeviations++; // Count for major+ 
                            $majorPlusSections++;
                            $criticalDeviations++; // Count for critical only
                            $criticalSections++;
                        } elseif ($deviation >= 6) { // Major: 6-9°C
                            $severityCount['major']++;
                            $lineStats[$line]['major']++;
                            $majorPlusDeviations++; // Count for major+
                            $majorPlusSections++;
                        } else { // Minor: 3-6°C
                            $severityCount['minor']++;
                            $lineStats[$line]['minor']++;
                            // Don't count minor for major+ or critical
                        }
                    }
                }
            }
        }

        $this->deviationSummary = [
            'total_measurements' => $totalMeasurements,
            'total_sections' => $totalSections,
            'total_deviations' => $totalDeviations,
            'deviation_sections' => $deviationSections,
            'major_plus_deviations' => $majorPlusDeviations,
            'major_plus_sections' => $majorPlusSections,
            'critical_deviations' => $criticalDeviations, // New: only critical
            'critical_sections' => $criticalSections, // New: only critical sections
            'deviation_rate' => $totalSections > 0 ? round(($deviationSections / $totalSections) * 100, 2) : 0,
            'major_plus_rate' => $totalSections > 0 ? round(($majorPlusSections / $totalSections) * 100, 2) : 0,
            'critical_rate' => $totalSections > 0 ? round(($criticalSections / $totalSections) * 100, 2) : 0 // New: critical only rate
        ];

        $this->severityBreakdown = $severityCount;
        
        // Sort lineDeviations by deviation rate (highest first)
        uasort($lineStats, function($a, $b) {
            $rateA = $a['total'] > 0 ? ($a['deviations'] / ($a['total'] * 8)) * 100 : 0;
            $rateB = $b['total'] > 0 ? ($b['deviations'] / ($b['total'] * 8)) * 100 : 0;
            return $rateB <=> $rateA;
        });
        
        $this->lineDeviations = $lineStats;
    }

    private function renderCharts()
    {
        // Severity breakdown pie chart with updated labels
        $severityChartData = [
            'labels' => [__('Minor (3-6°C)'), __('Major (6-9°C)'), __('Critical (>9°C)')],
            'datasets' => [[
                'data' => array_values($this->severityBreakdown),
                'backgroundColor' => ['rgba(255, 205, 86, 0.8)', 'rgba(255, 159, 64, 0.8)', 'rgba(255, 99, 132, 0.8)']
            ]]
        ];

        // Line deviation rate chart with updated calculation
        $lineData = [];
        foreach ($this->lineDeviations as $line => $stats) {
            $lineData[] = [
                'line' => $line,
                'label' => "Line " . sprintf('%02d', $line),
                'rate' => $stats['total'] > 0 ? round(($stats['deviations'] / ($stats['total'] * 8)) * 100, 2) : 0,
                'minor' => $stats['minor'],
                'major' => $stats['major'], 
                'critical' => $stats['critical']
            ];
        }
        
        // Sort by deviation rate (highest first)
        usort($lineData, function($a, $b) {
            return $b['rate'] <=> $a['rate'];
        });
        
        $lineLabels = array_column($lineData, 'label');
        $minorData = array_column($lineData, 'minor');
        $majorData = array_column($lineData, 'major');
        $criticalData = array_column($lineData, 'critical');

        $lineChartData = [
            'labels' => $lineLabels,
            'datasets' => [
                [
                    'label' => __('Minor (3-6°C)'),
                    'data' => $minorData,
                    'backgroundColor' => 'rgba(255, 205, 86, 0.8)'
                ],
                [
                    'label' => __('Major (6-9°C)'),
                    'data' => $majorData,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.8)'
                ],
                [
                    'label' => __('Critical (>9°C)'),
                    'data' => $criticalData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.8)'
                ]
            ]
        ];

        $this->js("
            (function() {
                  var severityCtx = document.getElementById('severity-chart');
                  if (window.severityChart) window.severityChart.destroy();
                  window.severityChart = new Chart(severityCtx, {
                     type: 'doughnut',
                     data: " . json_encode($severityChartData) . ",
                     options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: '" . __('Klasifikasi Deviasi') . "',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                }
                            },
                            legend: {
                                position: 'left'
                            }
                        }
                     }
                  });
                  
                  var lineCtx = document.getElementById('line-chart');
                  if (window.lineChart) window.lineChart.destroy();
                  window.lineChart = new Chart(lineCtx, {
                     type: 'bar',
                     data: " . json_encode($lineChartData) . ",
                     options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            title: {
                                display: true,
                                text: '" . __('Distribusi Deviasi per Line') . "',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                }
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                beginAtZero: true
                            },
                            y: {
                                stacked: true
                            }
                        }
                     }
                  });
            })()
         ");
    }
};

?>

<div>
    <div class="p-0 sm:p-1 mb-6">
        <div class="flex flex-col lg:flex-row gap-3 w-full bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-4">
            <div>
                <div class="flex mb-2 text-xs text-neutral-500">
                    <div class="flex">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <x-text-button class="uppercase ml-3">{{ __('Rentang') }}<i class="icon-chevron-down ms-1"></i></x-text-button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link href="#" wire:click.prevent="setToday">
                                    {{ __('Hari ini') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="#" wire:click.prevent="setYesterday">
                                    {{ __('Kemarin') }}
                                </x-dropdown-link>
                                <hr class="border-neutral-300 dark:border-neutral-600" />
                                <x-dropdown-link href="#" wire:click.prevent="setThisWeek">
                                    {{ __('Minggu ini') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="#" wire:click.prevent="setLastWeek">
                                    {{ __('Minggu lalu') }}
                                </x-dropdown-link>
                                <hr class="border-neutral-300 dark:border-neutral-600" />
                                <x-dropdown-link href="#" wire:click.prevent="setThisMonth">
                                    {{ __('Bulan ini') }}
                                </x-dropdown-link>
                                <x-dropdown-link href="#" wire:click.prevent="setLastMonth">
                                    {{ __('Bulan lalu') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-text-input wire:model.live="start_at" id="cal-date-start" type="date"></x-text-input>
                    <x-text-input wire:model.live="end_at"  id="cal-date-end" type="date"></x-text-input>
                </div>
            </div>
            <div class="border-l border-neutral-300 dark:border-neutral-700 mx-2"></div>
            <div class="flex gap-3">
                <div>
                    <label for="device-line"
                    class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Line') }}</label>
                    <x-select id="device-line" wire:model.live="line">
                        <option value=""></option>
                        @foreach($lines as $line)
                        <option value="{{ $line }}">{{ $line }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <label for="device-position"
                    class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Posisi') }}</label>
                    <x-select id="device-position" wire:model.live="position">
                        <option value=""></option>
                        <option value="upper">{{ __('Atas') }}</option>
                        <option value="lower">{{ __('Bawah') }}</option>
                    </x-select>
                </div>
            </div>
            <div class="border-l border-neutral-300 dark:border-neutral-700 mx-2"></div>
            <div class="grow flex justify-center gap-x-2 items-center">
                <div wire:loading.class.remove="hidden" class="flex gap-3 hidden">
                    <div class="relative w-3">
                        <x-spinner class="sm mono"></x-spinner>
                    </div>
                    <div>{{ __('Memuat...') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid: Chart + KPI Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Pie Chart -->
        <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-6">
            <div class="h-full">
                <canvas id="severity-chart"></canvas>
            </div>
        </div>
        
        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-6">
                <div class="text-neutral-500 dark:text-neutral-400 text-xs uppercase mb-2">{{ __('Total Pengukuran') }}</div>
                <div class="text-2xl font-bold">{{ number_format($deviationSummary['total_measurements'] ?? 0) }}</div>
                <div class="text-xs text-neutral-500 mt-1">{{ number_format($deviationSummary['total_sections'] ?? 0) . ' ' . __('sections') }}</div>
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-6">
                <div class="text-neutral-500 dark:text-neutral-400 text-xs uppercase mb-2">{{ __('Total Deviasi') }}</div>
                <div class="text-2xl font-bold text-red-500">{{ number_format($deviationSummary['total_deviations'] ?? 0) }}</div>
                <div class="text-xs text-neutral-500 mt-1">{{ number_format($deviationSummary['deviation_sections'] ?? 0) . ' ' . __('sections (≥3°C)') }}</div>
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-6">
                <div class="text-neutral-500 dark:text-neutral-400 text-xs uppercase mb-2">{{ __('Deviasi Major+') }}</div>
                <div class="text-2xl font-bold text-orange-600">{{ number_format($deviationSummary['major_plus_deviations'] ?? 0) }}</div>
                <div class="text-xs text-neutral-500 mt-1">{{ number_format($deviationSummary['major_plus_sections'] ?? 0) . ' ' . __('sections (≥6°C)') }}</div>
            </div>
            <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-6">
                <div class="text-neutral-500 dark:text-neutral-400 text-xs uppercase mb-2">{{ __('Tingkat Deviasi Kritikal') }}</div>
                <div class="text-2xl font-bold {{ ($deviationSummary['critical_rate'] ?? 0) > 10 ? 'text-red-500' : (($deviationSummary['critical_rate'] ?? 0) > 8 ? 'text-yellow-500' : 'text-green-500') }}">
                    {{ ($deviationSummary['critical_rate'] ?? 0) }}%
                </div>
                <div class="text-xs text-neutral-500 mt-1">{{ __('Target: <10%') }}</div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Line Chart + Table -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Line Chart -->
        <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg p-6">
            <div class="h-full">
                <canvas id="line-chart"></canvas>
            </div>
        </div>
        
        <!-- Detailed Table -->
        <div class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-sm text-sm w-full">
                    <thead>
                        <tr class="text-xs uppercase text-neutral-500 border-b">
                            <th class="px-4 py-3">{{ __('Line') }}</th>
                            <th class="px-4 py-3">{{ __('Ukur') }}</th>
                            <th class="px-4 py-3">{{ __('Deviasi') }}</th>
                            <th class="px-4 py-3">{{ __('Tingkat (%)') }}</th>
                            <th class="px-4 py-3">{{ __('Minor') }}</th>
                            <th class="px-4 py-3">{{ __('Major') }}</th>
                            <th class="px-4 py-3">{{ __('Critical') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lineDeviations as $line => $stats)
                        @php
                            $machine = \App\Models\InsStcMachine::where('line', $line)->first();
                            $deviationRate = $stats['total'] > 0 ? round(($stats['deviations'] / ($stats['total'] * 8)) * 100, 2) : 0;
                        @endphp
                        <tr class="border-b border-neutral-100 dark:border-neutral-700">
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <span class="font-mono font-bold">{{ sprintf('%02d', $line) }}</span>
                                    @if($machine && strpos($machine->ip_address, '127.') !== 0)
                                        <i class="icon-badge-check text-caldy-500 ml-2" title="{{ __('Kontrol otomatis') }}"></i>
                                    @endif
                                    @if($machine)
                                        <span class="text-xs ml-2 {{ (substr($machine->code, 0, 3) == 'OLD' ) ? 'text-caldy-500' : 'text-neutral-500' }}">
                                            {{ substr($machine->code, 0, 3) == 'OLD' ? __('Lama') : __('Baru') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($stats['total']) }}</td>
                            <td class="px-4 py-3">{{ number_format($stats['deviations']) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="mr-2 w-16 bg-neutral-200 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $deviationRate > 10 ? 'bg-red-500' : ($deviationRate > 5 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                             style="width: {{ min($deviationRate, 100) }}%"></div>
                                    </div>
                                    <span class="text-sm {{ $deviationRate > 10 ? 'text-red-600' : ($deviationRate > 5 ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ $deviationRate }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($stats['minor']) }}</td>
                            <td class="px-4 py-3">{{ number_format($stats['major']) }}</td>
                            <td class="px-4 py-3">{{ number_format($stats['critical']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.$dispatch('update');
</script>
@endscript