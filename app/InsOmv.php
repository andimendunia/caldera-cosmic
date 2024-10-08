<?php

namespace App;

use Illuminate\Support\Carbon;

class InsOmv
{
    public static function getChartOptions(array $amps, Carbon $start_at, array $step_durations, array $capture_points, int $height)
    {
        $chart_data = array_map(function ($amp) use ($start_at) {
            // Add the 'taken_at' seconds to the start_at
            $taken_at = $start_at->copy()->addSeconds($amp['taken_at'])->toDateTimeString();
        
            return [
                'taken_at' => $taken_at,
                'value' => $amp['value'],
            ];
        }, $amps);

        $x_annos = array_map(function($duration, $index) use ($start_at, $step_durations) {
            $timestamp = $start_at->copy()->addSeconds($duration)->getTimestamp() * 1000; // ApexCharts expects time in milliseconds
            $isLast = $index === array_key_last($step_durations); // Check if it's the last iteration
            return [
                'x' => $timestamp,
                'borderColor' => $isLast ? '#FF0000' : '#008080', // Red border for the last iteration
                'label' => [
                    'borderWidth' => 0,
                    'style' => [
                        'background' => $isLast ? '#FF0000' : '#008080', // Red background for the last iteration
                        'color' => '#fff',
                    ],
                    'text' => $isLast ? __('Selesai') : __('Langkah') . ' ' . ($index + 2),
                ],
            ];
        }, $step_durations, array_keys($step_durations));

        return [
            'chart' => [
                'redrawOnParentResize' => true,
                'height' => $height .'%',
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
                    ],
                ],
                'animations' => [
                    'enabled' => true,
                    'easing' => 'easeout',
                    'speed' => 400,
                    'animateGradually' => [
                        'enabled' => false,
                    ],
                ],
            ],
            'series' => [
                [
                    'name' => __('Arus listrik'),
                    'data' => array_map(function($item) {
                        return ['x' => $item['taken_at'], 'y' => $item['value']];
                    }, $chart_data),
                    'color' => '#D64550',
                ],
            ],
            'xaxis' => [
                'type' => 'datetime',
                'labels' => [
                    'datetimeUTC' => false,
                ],
            ],
            'yaxis' => [
                'min' => 0,
                'max' => 300,
                'title' => [
                    'text' => __('Arus listrik'),
                ]
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 1,
            ],
            'annotations' => [
                'xaxis' => $x_annos,
            ],
            'tooltip' => [
                'x' => [
                    'format' => 'HH:mm:ss'
                ]
            ]
        ];
    }  
    
}
