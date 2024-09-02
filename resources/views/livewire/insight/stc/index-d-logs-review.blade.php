<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Carbon\Carbon;

new class extends Component {
    
    public array $logs = [];
    public array $xzones = [];
    public array $yzones = [];
    public int $ymax = 10;
    public int $ymin = 0;
    private array $xzoneColors = [
        'preheat' => '#FFA500',
        'zone_1' => '#775DD0',
        'zone_2' => '#775DD0',
        'zone_3' => '#775DD0',
        'zone_4' => '#775DD0',
    ];

    #[On('d-logs-review')]
    public function dLogsLoad($logs, $xzones, $yzones)
    {
        $this->logs = json_decode($logs, true);
        $this->xzones = json_decode($xzones, true);
        $this->yzones = json_decode($yzones, true);
        $this->ymax = $this->yzones ? max($this->yzones) + 5 : $this->ymax;
        $this->ymin = $this->yzones ? min($this->yzones) : $this->ymin;
        $this->generateChart();
    }

    private function generateChart()
    {
        $chartData = array_map(function ($log) {
            return [$this->parseDate($log['taken_at']), $log['temp']];
        }, $this->logs);

        $chartDataJs = json_encode($chartData);

        $this->js("
            let options = " . json_encode($this->getChartOptions($chartDataJs)) . ";

            const parent = \$wire.\$el.querySelector('#chart-container');
            parent.innerHTML = '';

            const newChartMain = document.createElement('div');
            newChartMain.id = 'chart-main';
            parent.appendChild(newChartMain);

            let mainChart = new ApexCharts(parent.querySelector('#chart-main'), options);
            mainChart.render();
        ");
    }

    private function getChartOptions($chartDataJs)
    {
        return [
            'chart' => [
                'height' => '100%',
                'type' => 'line',
                'toolbar' => [
                    'show' => true,
                    'tools' => [
                        'download' => '<img src="/icon-download.svg" width="18">',
                        'zoom' => '<img src="/icon-zoom-in.svg" width="18">',
                        'zoomin' => false,
                        'zoomout' => false,
                        'pan' => '<img src="/icon-hand.svg" width="20">',
                        'reset' => '<img src="/icon-zoom-out.svg" width="18">',
                    ]
                ],
                'animations' => [
                    'enabled' => true,
                    'easing' => 'easeout',
                    'speed' => 400,
                    'animateGradually' => [
                        'enabled' => false,
                    ],
                ]
            ],
            'series' => [[
                'name' => __('Suhu'),
                'data' => json_decode($chartDataJs, true),
                'color' => '#00BBF9'
            ]],
            'xaxis' => [
                'type' => 'datetime',
                'labels' => [
                    'datetimeUTC' => false,
               ]
            ],
            'yaxis' => [
                'title' => [
                    'text' => __('Suhu')
               ],
               'max' => $this->ymax,
               'min' => $this->ymin,

            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 1,
            ],
            'tooltip' => [
                'x' => [
                    'format' => 'dd MMM yyyy HH:mm',
                ]              
            ],
            'annotations' => [
                'xaxis' => $this->generateXAnnotations(),
                'yaxis' => $this->generateYAnnotations()
            ],
            'grid' => [
               'yaxis' => [
                  'lines' => [
                     'show' => false
                  ]
               ]
            ]
        ];
    }

    private function generateXAnnotations()
    {
        $annotations = [];
        $previousCount = 0;

        foreach ($this->xzones as $zone => $count) {
            if (strpos($zone, 'count') !== false && $count > 0) {
                $zoneName = str_replace('_count', '', $zone);
                $position = $previousCount + $count;

                if (isset($this->logs[$position])) {
                    $annotations[] = [
                        'x' => $this->parseDate($this->logs[$position]['taken_at']),
                        'borderColor' => $this->xzoneColors[$zoneName] ?? '#000000',
                        'label' => [
                            'style' => [
                                'color' => '#fff',
                                'background' => $this->xzoneColors[$zoneName] ?? '#000000'
                            ],
                            'text' => ucfirst(str_replace('_', ' ', $zoneName))
                        ]
                    ];
                }

                $previousCount += $count;
            }
        }

        return $annotations;
    }

    private function generateYAnnotations()
   {
      $annotations = [];
      foreach ($this->yzones as $index => $value) {
         $annotations[] = [
               'y' => $value,
               'borderColor' => '#bcbcbc',
               'label' => [
                  'borderColor' => '#bcbcbc',
                  'style' => [
                     'color' => '#fff',
                     'background' => '#bcbcbc'
                  ],
                  'text' => $value . "°C"
               ]
         ];
      }
      return $annotations;
   }

    private function parseDate($dateString)
    {
        return Carbon::parse($dateString)->timestamp * 1000;
    }

    public function with(): array
    {
        return [
            'logs' => $this->logs,
        ];
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-start">
        <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
            {{ __('Tinjau data') }}
        </h2>
        <x-text-button type="button" x-on:click="$dispatch('close')"><i class="fa fa-times"></i></x-text-button>
    </div>
    <div class="h-80 bg-white dark:brightness-75 text-neutral-900 rounded overflow-hidden my-8" id="chart-container" wire:key="chart-container" wire:ignore>
    </div>
    <div class="grid grid-cols-2 gap-x-3">
      <div>
         <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
            {{ __('Pembagian zona') }}
        </h2>
        <div class="mt-3">
         {{ __('Preheat diambil berdasarkan 5 data pertama. Selanjutnya, setiap zona diwakili oleh 12 data berturut-turut.') }}
        </div>
      </div>
      <div class="max-h-48 overflow-y-auto relative">
          <table class="table table-xs text-sm overflow-hidden">
              <thead class="sticky top-0 z-10">
                  <tr>
                      <th>{{ __('No.') }}</th>
                      <th>{{ __('Diambil pada') }}</th>
                      <th>{{ __('Suhu') }}</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach($logs as $index => $log)
                      <tr>
                          <td>{{ $index + 1 }}</td>
                          <td>{{ $log['taken_at'] }}</td>
                          <td>{{ $log['temp'] }}</td>
                      </tr>
                  @endforeach
              </tbody>
          </table>
      </div>
  </div>
    <x-spinner-bg wire:loading.class.remove="hidden"></x-spinner-bg>
    <x-spinner wire:loading.class.remove="hidden" class="hidden"></x-spinner>
</div>