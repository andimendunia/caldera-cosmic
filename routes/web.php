<?php

use App\Http\Controllers\DownloadController;
use Livewire\Volt\Volt;
use App\Models\InsRtcMetric;
use App\Models\InsRtcRecipe;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\InsRtcMetricResource;
use App\Http\Resources\InsRtcRecipeResource;

Volt::route('/', 'home')->name('home');
Volt::route('/latihan',     'latihan-test');
Volt::route('/inventory',   'inventory.index')->name('inventory');
Volt::route('/machines',        'machines.index')->name('machines');
Volt::route('/projects',     'projects.index')->name('projects');
// Route::view('kpi', 'kpi')->name('kpi');
// Route::view('profile', 'profile')->name('profile');
// Route::view('help', 'help')->name('help');

// Insights routes
Route::prefix('insights')->group(function () {

    Route::name('insights.')->group(function () {

        Volt::route('/ss/{id}', 'insights.ss.index')->name('ss'); // slideshow
    });

    Route::name('insights.rtc.')->group(function () {

        Volt::route('/rtc/manage/authorizations',   'insights.rtc.manage.auths')     ->name('manage.auths');
        Volt::route('/rtc/manage/devices',          'insights.rtc.manage.devices')   ->name('manage.devices');
        Volt::route('/rtc/manage/recipes',          'insights.rtc.manage.recipes')   ->name('manage.recipes');
        Volt::route('/rtc/manage',                  'insights.rtc.manage.index')     ->name('manage.index');
        Volt::route('/rtc/slideshows',              'insights.rtc.slideshows')       ->name('slideshows');
        Volt::route('/rtc',                         'insights.rtc.index')            ->name('index');

        Route::get('/rtc/metric/{device_id}', function (string $device_id) {
            $metric = InsRtcMetric::join('ins_rtc_clumps', 'ins_rtc_clumps.id', '=', 'ins_rtc_metrics.ins_rtc_clump_id')
                ->where('ins_rtc_clumps.ins_rtc_device_id', $device_id)
                ->latest('dt_client')
                ->first();
            return $metric ? new InsRtcMetricResource($metric) : abort(404);
        })->name('metric');

        Route::get('/rtc/recipe/{recipe_id}', function (string $recipe_id) {
            return new InsRtcRecipeResource(InsRtcRecipe::findOrFail($recipe_id));
        })->name('recipe');

    });

    Route::name('insights.ldc.')->group(function () {

        Volt::route('/ldc/manage/authorizations',   'insights.ldc.manage.auths') ->name('manage.auths');
        Volt::route('/ldc/manage/machines',         'insights.ldc.manage.machines') ->name('manage.machines');
        Volt::route('/ldc/manage',                  'insights.ldc.manage.index') ->name('manage.index');
        Volt::route('/ldc/data',                    'insights.ldc.data.index')->name('data.index');
        Volt::route('/ldc/create',                  'insights.ldc.create.index')->name('create.index');
        Route::get('/ldc', function () {
            if (auth()->check()) {
                return redirect()->route('insights.ldc.create.index');
            }
            return redirect()->route('insights.ldc.data.index');
        })->name('index');
    });

    Route::name('insights.omv.')->group(function () {

        Volt::route('/omv/manage/authorizations',   'insights.omv.manage.auths')     ->name('manage.auths');
        Volt::route('/omv/manage/recipes',          'insights.omv.manage.recipes')   ->name('manage.recipes');
        Volt::route('/omv/manage',                  'insights.omv.manage.index')     ->name('manage.index');
        Volt::route('/omv/data',                    'insights.omv.data.index')       ->name('data.index');
        Volt::route('/omv/create',                  'insights.omv.create.index')     ->name('create.index');
        Route::get('/omv', function () {
            if (auth()->check()) {
                return redirect()->route('insights.omv.create.index');
            }
            return redirect()->route('insights.omv.data.index');
        })->name('index');
    });

    Route::name('insights.rdc.')->group(function () {

        Volt::route('/rdc/manage/authorizations',   'insights.rdc.manage.auths')     ->name('manage.auths');
        Volt::route('/rdc/manage/machines',         'insights.rdc.manage.machines')  ->name('manage.machines');
        Volt::route('/rdc/manage',                  'insights.rdc.manage.index')     ->name('manage.index');
        Volt::route('/rdc/data',                    'insights.rdc.data.index')       ->name('data.index');
        Volt::route('/rdc/queue',                   'insights.rdc.queue.index')      ->name('queue.index');
        Route::get('/rdc', function () {
            if (auth()->check()) {
                return redirect()->route('insights.rdc.queue.index');
            }
            return redirect()->route('insights.rdc.data.index');
        })->name('index');

    });

    Route::name('insights.stc.')->group(function () {

        Volt::route('/stc/manage/authorizations',   'insights.stc.manage.auths')     ->name('manage.auths');
        Volt::route('/stc/manage/machines',         'insights.stc.manage.machines')  ->name('manage.machines');
        Volt::route('/stc/manage/devices',          'insights.stc.manage.devices')   ->name('manage.devices');
        Volt::route('/stc/manage',                  'insights.stc.manage.index')     ->name('manage.index');
        Volt::route('/stc/data',                    'insights.stc.data.index')       ->name('data.index');
        Volt::route('/stc/create',                  'insights.stc.create.index')     ->name('create.index');
        Route::get('/stc', function () {
            if (auth()->check()) {
                return redirect()->route('insights.stc.create.index');
            }
            return redirect()->route('insights.stc.data.index');
        })->name('index');

    });

    Route::name('insights.erd.')->group(function () {

        Volt::route('/erd/manage/authorizations',   'insights.erd.manage.auths')     ->name('manage.auths');
        Volt::route('/erd/manage/machines',         'insights.erd.manage.machines')  ->name('manage.machines');
        Volt::route('/erd/manage/devices',          'insights.erd.manage.devices')   ->name('manage.devices');
        Volt::route('/erd/manage',                  'insights.erd.manage.index')     ->name('manage.index');
        Volt::route('/erd/summary',                 'insights.erd.summary.index')    ->name('summary.index');
        Volt::route('/erd',                         'insights.erd.index')            ->name('index');

    });
    Volt::route('/', 'insights.index')->name('insights');
});

// Download route
Route::name('download.')->group(function () {

    Route::get('/download/inv-stocks/{token}', [DownloadController::class, 'invStocks'])    ->name('inv-stocks');
    Route::get('/download/inv-circs/{token}', [DownloadController::class, 'invCircs'])      ->name('inv-circs');
    Route::get('/download/ins-rtc-metrics', [DownloadController::class, 'insRtcMetrics'])   ->name('ins-rtc-metrics');
    Route::get('/download/ins-rtc-clumps', [DownloadController::class, 'insRtcClumps'])     ->name('ins-rtc-clumps');
    Route::get('/download/ins-ldc-hides', [DownloadController::class, 'insLdcHides'])       ->name('ins-ldc-hides');

});

// All routes that needs to be authenticated
Route::middleware('auth')->group(function () {

    // Account routes
    Route::prefix('account')->group(function () {

        Route::name('account.')->group(function () {

            Volt::route('/general',     'account.general')      ->name('general');
            Volt::route('/password',    'account.password')     ->name('password');
            Volt::route('/language',    'account.language')     ->name('language');
            Volt::route('/theme',       'account.theme')        ->name('theme');
            Volt::route('/edit',        'account.edit')         ->name('edit');

        });

        Volt::route('/', 'account.index')->name('account');

    });

    // inventory routes
    Route::prefix('inventory')->group(function () {

        Route::name('inventory.items.')->group(function () {

            Route::middleware('can:create,' . \App\Models\InvItem::class)->group(function () {
                Volt::route('/items/create',    'inventory.items.create')       ->name('create');
            });
            Volt::route('/items/mass-update',   'inventory.items.mass-update')  ->name('mass-update');
            Volt::route('/items/{id}',          'inventory.items.show')         ->name('show');
            Volt::route('/items/{id}/edit',     'inventory.items.edit')         ->name('edit');
            Volt::route('/items/',              'inventory.items.index')        ->name('index');

        });

        Route::name('inventory.circs.')->group(function () {

            Volt::route('/circs/summary/',  'inventory.circs.summary.index')    ->name('summary.index');
            Volt::route('/circs/create',    'inventory.circs.create')           ->name('create');
            Volt::route('/circs/print',     'inventory.circs.print')            ->name('print');
            Volt::route('/circs',           'inventory.circs.index')            ->name('index');

        });

        Route::name('inventory.reqs.')->group(function () {
            Volt::route('/reqs',              'inventory.reqs.index')   ->name('index');
        });

    });

    // Administration routes
    Route::prefix('admin')->middleware('can:superuser')->group(function () {

        Route::name('admin.')->group(function () {

            Volt::route('/account-manage',  'admin.account.manage')     ->name('account-manage');
            Volt::route('/inventory-auths', 'admin.inventory.auths')    ->name('inventory-auths');
            Volt::route('/inventory-areas', 'admin.inventory.areas')    ->name('inventory-areas');
            Volt::route('/inventory-currs', 'admin.inventory.currs')    ->name('inventory-currs');

        });

        Route::view('/', 'livewire.admin.index')->name('admin');
    });

});


require __DIR__.'/auth.php';
