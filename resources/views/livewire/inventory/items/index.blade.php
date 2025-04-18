<?php

use App\Models\InvItem;
use App\Models\InvStock;
use App\Models\InvArea;
use App\Models\InvLoc;
use App\Models\InvTag;
use App\Models\InvCurr;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Carbon\Carbon;

new #[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public int $perPage = 24;

    public string $view = 'content';

    public string $sort = 'updated';

    public array $areas = [];
    
    public array $area_ids = [];

    #[Url]
    public array $tags = [];
    
    // public array $tag_hints = [];
    public string $loc_parent = '';    
    
    public string $loc_bin = '';
    
    public array $loc_parent_hints = [];
    
    public array $loc_bin_hints = [];
    
    #[Url]
    public string $q = '';
    
    public array $qwords = []; // caldera: do you need it?

    #[Url]
    public string $filter = '';

    #[Url]
    public bool $is_deleted = false;

    public function mount()
    {
        $user_id = Auth::user()->id;

        if ($user_id === 1) {
            $areas = InvArea::all();
        } else {
            $user = User::find($user_id);
            $areas = $user->inv_areas;
        }

        $this->areas = $areas->toArray();

        $savedParams = session('inv_search_params', []);
    
        if ($savedParams) {
            $this->q            = $savedParams['q'] ?? '';
            $this->loc_parent   = $savedParams['loc_parent'] ?? '';
            $this->loc_bin      = $savedParams['loc_bin'] ?? '';
            $this->tags         = $this->tags ? ($this->tags[0] ? [$this->tags[0]]: []): ($savedParams['tags'] ?? []);
            $this->area_ids     = $savedParams['area_ids'] ?? [];
            $this->filter       = $this->filter ?: ($savedParams['filter'] ?? '');
            $this->view         = $savedParams['view'] ?? 'content';
            $this->sort         = $savedParams['sort'] ?? '';
        } else {
            $this->area_ids     = $areas->pluck('id')->toArray();
        }

        if($this->is_deleted) {
            $this->js('toast("' . __('Barang dihapus') . '", { type: "success" } )');
        }

    }

    public function with(): array
    {
        $q          = trim($this->q);
        $loc_parent = trim($this->loc_parent);
        $loc_bin    = trim($this->loc_bin);
        $tags       = $this->tags;

        $inv_search_params = [
            'q'             => $q,
            'loc_parent'    => $loc_parent,
            'loc_bin'       => $loc_bin,
            'tags'          => $tags,
            'area_ids'      => $this->area_ids,
            'filter'        => $this->filter,
            'view'          => $this->view,
            'sort'          => $this->sort
        ];
        
        session(['inv_search_params' => $inv_search_params]);

        $inv_search_query = InvStock::with([
            'inv_item', 
            'inv_curr',
            'inv_item.inv_loc', 
            'inv_item.inv_area', 
            'inv_item.inv_tags'
        ])
        ->whereHas('inv_item', function ($query) use ($q, $loc_parent, $loc_bin, $tags) {
            // search
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('name', 'like', "%$q%")
                         ->orWhere('code', 'like', "%$q%")
                         ->orWhere('desc', 'like', "%$q%");
            })
            ->whereIn('inv_area_id', $this->area_ids);

            // location
            $query->where(function ($subQuery) use ($loc_parent, $loc_bin) {
                if ($loc_parent || $loc_bin) {
                    $subQuery->whereHas('inv_loc', function ($subSubQuery) use ($loc_parent, $loc_bin) {

                        if ($loc_parent) {
                            $subSubQuery->where('parent', 'like', "%$loc_parent%");
                        }

                        if ($loc_bin) {
                            $subSubQuery->where('bin', 'like', "%$loc_bin%");
                        }
                    });
                }
            });

            // tags
            $query->where(function ($subQuery) use ($tags) {
                if (count($tags)) {
                    $subQuery->whereHas('inv_tags', function ($subSubQuery) {
                        $subSubQuery->whereIn('name', $this->tags);
                    });
                }
            });

            
            // filter
            switch ($this->filter) {
                case 'no-code':
                    $query->whereNull('code');
                    break;

                case 'no-photo':
                    $query->whereNull('photo');
                    break;

                case 'no-location':
                    $query->whereNull('inv_loc_id');
                    break;

                case 'no-tags':
                    $query->whereDoesntHave('inv_tags');
                    break;

                case 'inactive':
                    $query->where('is_active', false);
                    break;

                case 'gt-100-days':
                    $now = Carbon::now();
                    $sub_100_days = $now->copy()->subDays(100);
                    $query->where(function ($q) use ($sub_100_days) {
                    $q->where('last_withdrawal', '>', $sub_100_days)
                    ->orWhereNull('last_withdrawal');
                    });
                    break;                        

                case 'gt-90-days':
                    $now            = Carbon::now();
                    $sub_100_days   = $now->copy()->subDays(100);
                    $sub_90_days    = $now->copy()->subDays(90);
                    $query->whereBetween('last_withdrawal', [$sub_100_days, $sub_90_days]);
                    break;

                case 'gt-60-days':
                    $now            = Carbon::now();
                    $sub_90_days    = $now->copy()->subDays(90);
                    $sub_60_days    = $now->copy()->subDays(60);
                    $query->whereBetween('last_withdrawal', [$sub_90_days, $sub_60_days]);
                    break;

                case 'gt-30-days':
                    $now            = Carbon::now();
                    $sub_60_days    = $now->copy()->subDays(60);
                    $sub_30_days    = $now->copy()->subDays(30);
                    $query->whereBetween('last_withdrawal', [$sub_60_days, $sub_30_days]);
                    break;

                case 'lt-30-days':
                    $now            = Carbon::now();
                    $sub_30_days    = $now->copy()->subDays(30);
                    $query->where('last_withdrawal', '<', $sub_30_days);
                    break;

                default:
                    $query->where('is_active', true);
                    break;
            }
        })
        ->where('is_active', true);

        switch ($this->sort) {
            case 'updated':
                $inv_search_query->orderByRaw('
                (SELECT updated_at FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id) DESC');
                break;
            case 'created':
                $inv_search_query->orderByRaw('
                (SELECT created_at FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id) DESC');
                break;
            case 'loc':
                $inv_search_query->whereHas('inv_item.inv_loc');
                $inv_search_query->orderByRaw('
                (SELECT bin FROM inv_locs WHERE 
                inv_locs.id = (SELECT inv_loc_id FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id)) ASC,

                (SELECT parent FROM inv_locs WHERE 
                inv_locs.id = (SELECT inv_loc_id FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id)) ASC');
            case 'last_deposit':
                $inv_search_query->orderByRaw('
                (SELECT last_deposit FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id) DESC');
                break;
            case 'last_withdrawal':
                $inv_search_query->orderByRaw('
                (SELECT last_withdrawal FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id) DESC');
                break;
            case 'qty_low':
                $inv_search_query->orderBy('qty');
                break;
            case 'qty_high':
                $inv_search_query->orderByDesc('qty');
                break;
            case 'amt_low':
                $inv_search_query->orderBy('amount_main');
                break;
            case 'amt_high':
                $inv_search_query->orderByDesc('amount_main');
                break;
            case 'alpha':
                $inv_search_query->orderByRaw('
                (SELECT name FROM inv_items 
                WHERE inv_items.id = inv_stocks.inv_item_id) ASC');
                break;

        }

        $inv_stocks = $inv_search_query->paginate($this->perPage);

        return [
            'inv_stocks' => $inv_stocks,
        ];
    }

    public function download()
    {
        // Create a unique token for this download request
        $token = md5(uniqid());

        // Store the token in the session
        session()->put('inv_stocks_token', $token);
        
        // Redirect to a temporary route that will handle the streaming
        return redirect()->route('download.inv-stocks', ['token' => $token]);
    }

    public function resetQuery()
    {
        session()->forget('inv_search_params');
        $this->redirect(route('inventory.items.index'), navigate: true);
    }

    public function loadMore()
    {
        $this->perPage += 24;
    }

    public function updated($property)
    {
        $resetProps = ['q', 'view', 'sort', 'area_ids', 'loc_parent', 'loc_bin', 'tags'];
        if(in_array($property, $resetProps)) {
            $this->reset(['perPage']);
        }

        if ($property == 'loc_parent') {
            $hint = trim($this->loc_parent);
            if ($hint) {
                $hints = InvLoc::where('parent', 'LIKE', '%' . $hint . '%')
                    ->orderBy('parent')
                    ->limit(100)
                    ->get()
                    ->pluck('parent')
                    ->toArray();
                $this->loc_parent_hints = array_unique($hints);
            }
        }

        if ($property == 'loc_bin') {
            $hint = trim($this->loc_bin);
            if ($hint) {
                $hints = InvLoc::where('bin', 'LIKE', '%' . $hint . '%')
                    ->orderBy('bin')
                    ->limit(100)
                    ->get()
                    ->pluck('bin')
                    ->toArray();
                $this->loc_bin_hints = array_unique($hints);
            }
        }
    }

};

?>



<x-slot name="title">{{ __('Cari') . ' — ' . __('Inventaris') }}</x-slot>

<x-slot name="header">
    <x-nav-inventory></x-nav-inventory>
</x-slot>

<div id="content" class="py-6 max-w-8xl mx-auto sm:px-6 lg:px-8 text-neutral-800 dark:text-neutral-200">
    <div wire:key="modals">
        <x-modal name="create-from-code">
            <livewire:inventory.items.create-from-code :$areas lazy />
        </x-modal>
    </div>
    <div class="static lg:sticky top-0 z-10 py-6 ">
        <div class="flex flex-col lg:flex-row w-full bg-white dark:bg-neutral-800 divide-x-0 divide-y lg:divide-x lg:divide-y-0 divide-neutral-200 dark:divide-neutral-700 shadow sm:rounded-lg lg:rounded-full py-0 lg:py-2">
            <div class="flex gap-x-2 items-center px-8 py-2 lg:px-4 lg:py-0">
                <i wire:loading.remove class="fa fa-fw fa-search {{ $q ? 'text-neutral-800 dark:text-white' : 'text-neutral-400 dark:text-neutral-600' }}"></i>
                <i wire:loading class="fa fa-fw relative">
                    <x-spinner class="sm mono"></x-spinner>
                </i>
                <div class="w-full md:w-40">
                    <x-text-input-t wire:model.live="q" id="inv-q" name="inv-q" class="h-9 py-1 placeholder-neutral-400 dark:placeholder-neutral-600"
                        type="search" list="qwords" placeholder="{{ __('Cari...') }}" autofocus autocomplete="inv-q" />
                    <datalist id="qwords">
                        @if (count($qwords))
                            @foreach ($qwords as $qword)
                                <option value="{{ $qword }}">
                            @endforeach
                        @endif
                    </datalist>
                </div>
            </div>

            <div class="flex items-center gap-x-4 p-4 lg:py-0 ">
                <x-inv-loc-selector isQuery="true" class="text-xs font-semibold uppercase" />
            </div>

            <div class="flex items-center gap-x-4 p-4 lg:py-0 ">
                <x-inv-tag-selector isQuery="true" class="text-xs font-semibold uppercase" />
            </div>

            <div class="grow flex items-center gap-x-4 p-4 lg:py-0 ">
                <x-inv-search-filter class="text-xs font-semibold uppercase" />
            </div>

            <div class="flex items-center justify-between gap-x-4 p-4 lg:py-0">
                <x-inv-area-selector class="text-xs font-semibold uppercase" :$areas />
                <div>
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <x-text-button><i class="fa fa-fw fa-ellipsis-h"></i></x-text-button>
                        </x-slot>
                        <x-slot name="content">
                            @can('create', InvItem::class)
                                <x-dropdown-link href="#" x-on:click.prevent="$dispatch('open-modal', 'create-from-code')">
                                    <i class="fa fa-fw fa-plus me-2"></i>{{ __('Barang baru')}}
                                </x-dropdown-link>
                            @else
                            <x-dropdown-link href="#" disabled="true">
                                <i class="fa fa-fw fa-plus me-2"></i>{{ __('Barang baru')}}
                            </x-dropdown-link>
                            @endcan
                            <hr class="border-neutral-300 dark:border-neutral-600" />
                            <x-dropdown-link href="{{ route('inventory.items.summary') }}" wire:navigate>
                                <i class="fa fa-fw fa-line-chart me-2"></i>{{ __('Ringkasan barang')}}
                            </x-dropdown-link>
                            <!-- <x-dropdown-link href="#" disabled="true">
                                <i class="fa fa-fw me-2"></i>{{ __('Perbarui massal')}}
                            </x-dropdown-link> -->
                            <x-dropdown-link href="{{ route('inventory.items.bulk-operation.index') }}" wire:navigate>
                                <i class="fa fa-fw me-2"></i>{{ __('Operasi massal barang')}}
                            </x-dropdown-link>
                            <hr class="border-neutral-300 dark:border-neutral-600" />
                            <!-- <x-dropdown-link href="#" x-on:click.prevent="$dispatch('open-modal', 'raw-stats-info')">
                                <i class="fa fa-fw fa-map-marker-alt me-2"></i>{{ __('Kelola lokasi ')}}
                            </x-dropdown-link>
                            <x-dropdown-link href="#" x-on:click.prevent="$dispatch('open-modal', 'raw-stats-info')">
                                <i class="fa fa-fw fa-tag me-2"></i>{{ __('Kelola tag ')}}
                            </x-dropdown-link>
                            <hr class="border-neutral-300 dark:border-neutral-600" /> -->
                            <x-dropdown-link href="#" wire:click.prevent="resetQuery">
                                <i class="fa fa-fw fa-undo me-2"></i>{{ __('Reset')}}
                            </x-dropdown-link>
                            <hr class="border-neutral-300 dark:border-neutral-600" />
                            <x-dropdown-link href="#" wire:click.prevent="download">
                                <i class="fa fa-fw fa-download me-2"></i>{{ __('Unduh sebagai CSV')}}
                            </x-dropdown-link>
                            <!-- <x-dropdown-link href="#">
                                <i class="fa fa-fw fa-download me-2"></i>{{ __('Unduh sebagai CSV') }}
                            </x-dropdown-link> -->
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>

    <div class="h-auto sm:h-12">
        <div class="flex items-center flex-col gap-y-6 sm:flex-row justify-between w-full h-full px-8">
            <div class="text-center sm:text-left">{{ $inv_stocks->total() . ' ' . __('barang') }}</div>
            <div class="grow flex justify-center sm:justify-end">
                <x-select wire:model.live="sort" class="mr-3">
                    <option value="updated">{{ __('Diperbarui') }}</option>
                    <option value="created">{{ __('Dibuat') }}</option>
                    <option value="loc">{{ __('Lokasi') }}</option>
                    <option value="last_deposit">{{ __('Terakhir ditambah') }}</option>
                    <option value="last_withdrawal">{{ __('Terakhir diambil') }}</option>
                    <option value="qty_low">{{ __('Qty terendah') }}</option>
                    <option value="qty_high">{{ __('Qty tertinggi') }}</option>
                    <option value="amt_low">{{ __('Amount terendah') }}</option>
                    <option value="amt_high">{{ __('Amount tertinggi') }}</option>
                    <option value="alpha">{{ __('Alfabet') }}</option>
                </x-select>
                <div class="btn-group">
                    <x-radio-button wire:model.live="view" value="list" name="view" id="view-list"><i
                            class="fa fa-fw fa-grip-lines text-center m-auto"></i></x-radio-button>
                    <x-radio-button wire:model.live="view" value="content" name="view" id="view-content"><i
                            class="fa fa-fw fa-list text-center m-auto"></i></x-radio-button>
                    <x-radio-button wire:model.live="view" value="grid" name="view" id="view-grid"><i
                            class="fa fa-fw fa-border-all text-center m-auto"></i></x-radio-button>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full  px-1" wire:loading.class="cal-shimmer">
        @if (!$inv_stocks->count())
            @if (count($area_ids))
                <div wire:key="no-match" class="py-20">
                    <div class="text-center text-neutral-300 dark:text-neutral-700 text-5xl mb-3">
                        <i class="fa fa-ghost"></i>
                    </div>
                    <div class="text-center text-neutral-400 dark:text-neutral-600">
                        {{ __('Tidak ada yang cocok') }}
                    </div>
                </div>
            @else
                <div wire:key="no-area" class="py-20">
                    <div class="text-center text-neutral-300 dark:text-neutral-700 text-5xl mb-3">
                        <i class="fa fa-tent relative"><i
                                class="fa fa-question-circle absolute bottom-0 -right-1 text-lg text-neutral-500 dark:text-neutral-400"></i></i>
                    </div>
                    <div class="text-center text-neutral-400 dark:text-neutral-600">{{ __('Pilih area') }}
                    </div>
                </div>
            @endif
        @else
            @switch($view)
                @case('grid')
                    <div wire:key="grid"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 mt-6 px-3 sm:px-0">
                        @foreach ($inv_stocks as $inv_stock)
                            <x-inv-card-grid
                            :url="route('inventory.items.show', ['id' => $inv_stock->inv_item_id, 'stock_id' => $inv_stock->id ])"
                            :name="$inv_stock->inv_item->name" 
                            :desc="$inv_stock->inv_item->desc" 
                            :code="$inv_stock->inv_item->code"
                            :uom="$inv_stock->uom"
                            :loc="$inv_stock->inv_item->inv_loc_id ? ($inv_stock->inv_item->inv_loc->parent . '-' . $inv_stock->inv_item->inv_loc->bin ) : null" 
                            :qty="$inv_stock->qty" 
                            :photo="$inv_stock->inv_item->photo">
                            </x-inv-card-grid>
                        @endforeach
                    </div>
                @break

                @case('list')
                    <div wire:key="list" class="bg-white dark:bg-neutral-800 shadow sm:rounded-lg overflow-auto mt-6">
                        <table class="text-neutral-600 dark:text-neutral-400 w-full table text-sm [&_th]:text-center [&_th]:px-2 [&_th]:py-3 [&_td]:px-2 [&_td]:py-1">
                            <tr class="uppercase text-xs">
                                <th>{{ __('ID') }}</th>
                                <th></th>
                                <th>{{ __('Nama') }}</th>
                                <th>{{ __('Deskripsi') }}</th>
                                <th>{{ __('Kode') }}</th>
                                <th><i class="fa fa-map-marker-alt"></i></th>
                                <th><i class="fa fa-tag"></i></th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('Harga') }}</th>
                                <th>{{ 'Σ (' . InvCurr::find(1)->name . ')' }}</th>
                                <th><i class="fa fa-tent"></i></th>
                            </tr>
                            @foreach($inv_stocks as $inv_stock)
                                <tr class="text-nowrap">
                                    <td class="w-[1%]">{{ $inv_stock->inv_item->id }}</td>
                                    <td class="w-[1%]">
                                        <div class="rounded-sm overflow-hidden relative flex w-12 h-4 bg-neutral-200 dark:bg-neutral-700">
                                            <div class="m-auto">
                                                <svg xmlns="http://www.w3.org/2000/svg"  class="block w-4 h-4 fill-current text-neutral-800 dark:text-neutral-200 opacity-25" viewBox="0 0 38.777 39.793"><path d="M19.396.011a1.058 1.058 0 0 0-.297.087L6.506 5.885a1.058 1.058 0 0 0 .885 1.924l12.14-5.581 15.25 7.328-15.242 6.895L1.49 8.42A1.058 1.058 0 0 0 0 9.386v20.717a1.058 1.058 0 0 0 .609.957l18.381 8.633a1.058 1.058 0 0 0 .897 0l18.279-8.529a1.058 1.058 0 0 0 .611-.959V9.793a1.058 1.058 0 0 0-.599-.953L20 .105a1.058 1.058 0 0 0-.604-.095zM2.117 11.016l16.994 7.562a1.058 1.058 0 0 0 .867-.002l16.682-7.547v18.502L20.6 37.026V22.893a1.059 1.059 0 1 0-2.117 0v14.224L2.117 29.432z" /></svg>
                                            </div>
                                            @if($inv_stock->inv_item->photo)
                                                <img class="absolute w-full h-full object-cover dark:brightness-75 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2" src="{{ '/storage/inv-items/' . $inv_stock->inv_item->photo }}" />
                                            @endif
                                        </div>   
                                    </td>
                                    <td class="max-w-40 truncate font-bold"><x-link href="{{ route('inventory.items.show', [ 'id' => $inv_stock->inv_item_id, 'stock_id' => $inv_stock->id ]) }}" wire:navigate>{{ $inv_stock->inv_item->name }}</x-link></td>
                                    <td class="max-w-40 truncate">{{ $inv_stock->inv_item->desc }}</td>
                                    <td>{{ $inv_stock->inv_item->code ?? '-' }}</td>
                                    <td class="w-[1%]">{{ $inv_stock->inv_item->inv_loc_id ? ($inv_stock->inv_item->inv_loc->parent . '-' .$inv_stock->inv_item->inv_loc->bin) : '-' }}</td>
                                    <td class="w-[1%]">{{ $inv_stock->inv_item->tags_facade() ?: '-' }}</td>
                                    <td>{{ $inv_stock->qty . ' ' . $inv_stock->uom }}</td>
                                    <td>{{ $inv_stock->inv_curr->name . ' ' . $inv_stock->unit_price }}</td>
                                    <td>{{ $inv_stock->amount_main }}</td>
                                    <td class="w-[1%]">{{ $inv_stock->inv_item->inv_area->name }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @break

                @default
                    <div wire:key="content" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-2 mt-6">
                        @foreach ($inv_stocks as $inv_stock)
                            <x-inv-card-content 
                            :url="route('inventory.items.show', ['id' => $inv_stock->inv_item_id, 'stock_id' => $inv_stock->id])"
                            :name="$inv_stock->inv_item->name" 
                            :desc="$inv_stock->inv_item->desc" 
                            :code="$inv_stock->inv_item->code"
                            :curr="$inv_stock->inv_curr->name" 
                            :price="$inv_stock->unit_price" 
                            :uom="$inv_stock->uom"
                            :loc="$inv_stock->inv_item->inv_loc_id ? ($inv_stock->inv_item->inv_loc->parent . '-' . $inv_stock->inv_item->inv_loc->bin ) : null" 
                            :tags="$inv_stock->inv_item->tags_facade() ?? null" 
                            :qty="$inv_stock->qty" 
                            :photo="$inv_stock->inv_item->photo">
                            </x-inv-card-content>
                        @endforeach
                    </div>
            @endswitch
            <div wire:key="observer" class="flex items-center relative h-16">
                @if (!$inv_stocks->isEmpty())
                    @if ($inv_stocks->hasMorePages())
                        <div wire:key="more" x-data="{
                            observe() {
                                const observer = new IntersectionObserver((inv_stocks) => {
                                    inv_stocks.forEach(inv_stock => {
                                        if (inv_stock.isIntersecting) {
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
        @endif
    </div>
</div>
