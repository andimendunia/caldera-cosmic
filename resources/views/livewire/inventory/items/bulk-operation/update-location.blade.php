<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\InvArea;
use App\Models\InvItem;
use App\Models\User;
use App\Models\InvLoc;
use Carbon\Carbon;


new #[Layout('layouts.app')]
class extends Component
{
   public array $items = 
      [
         [
            'id' => 1,
            'code' => 'XXX10-19001',
            'location' => 'Z9-99-99',
         ]
      ];

   public array $areas = [];
   public int $area_id = 0;

   public int $count = 0;

   public array $result = [
      'items' => [],
      'success' => 0,
      'failure' => 0,
   ];

   public function mount()
   {
      $area_ids = [];
      $user = User::find(Auth::user()->id);

      // superuser uses id 1
      if ($user->id === 1) {
         $area_ids = InvArea::all()->pluck('id');

      } else {
         $areas = $user->inv_areas;

         foreach ($areas as $area) {
            $item = new InvItem;
            $item->inv_area_id = $area->id;
            $response = Gate::inspect('download', $item);

            if ($response->allowed()) {
               $area_ids[] = $area->id;
            }
         }
      }

      $this->areas = InvArea::whereIn('id', $area_ids)->get()->toArray();
   }
    
   public function apply(bool $is_confirmed = false)
   {
      if ($is_confirmed) {

         $this->validate([
            'area_id' => ['required', 'exists:inv_areas,id']
         ]);

         foreach ($this->items as $item) {
            $this->result['items'][] = $this->updateItem($item);
         }         

         $this->reset(['items']);
         $this->js('$dispatch("editor-reset")');

         if($this->result['failure']) {
            $this->js('toast("' . __('Unduhan dimulai...') . '", { type: "success" })');        
               
            // CSV download
            $filename = __('Hasil operasi massal (perbarui lokasi)') . ' ' . Carbon::now()->format('Y-m-d His') . '.csv';
            $handle = fopen('php://temp', 'r+');
            $headers = [
               'id', 'code', 'location',
               __('Tindakan'), __('Status'), __('Pesan')
            ];
            
            fputcsv($handle, $headers);
            
            foreach ($this->result['items'] as $item) {
               $row = [
                     ($item['id'] ?? '') == 0 ? '' : $item['id'],
                     $item['code']        ?? '',
                     $item['location']    ?? '',
   
                     $item['action']      ?? '',
                     $item['status']      ?? '',
                     $item['message']     ?? ''
               ];            
               fputcsv($handle, $row);
            }
            
            rewind($handle);
            
            $csv = stream_get_contents($handle);
            fclose($handle);
            
            return response()->streamDownload(function () use ($csv) {
               echo $csv;
            }, $filename, [
               'Content-Type' => 'text/csv',
            ]);
         }

      } else {

         if(count($this->items) > 3000) {
            $this->js('toast("' . __('Hanya maksimal 100 entri yang diperbolehkan') . '", { type: "danger" })');
            return;
   
         }
   
         $this->reset(['count', 'area_id', 'result']);

         foreach ($this->items as $key => $item) {

            // Iterate over the children of $item
            foreach ($item as $childKey => $childValue) {
               // Trim all child items
               $this->items[$key][$childKey] = trim($childValue);
      
               // Convert specific keys to uppercase
               if (in_array($childKey, ['code', 'location'])) {
                  $this->items[$key][$childKey] = strtoupper($this->items[$key][$childKey]);
               }

               // Cast value with key 'id' to integer
               if ($childKey === 'id') {
                  $this->items[$key][$childKey] = (int)$this->items[$key][$childKey];
               }
            }
        
            // Increment update or create count after cleaning
            $this->count++;
        }
   
         $this->js('$dispatch("open-modal", "apply-confirm")');
      }

   }

   private function updateItem(array $item)
   {      
      try {

         $inv_item = null;

         if ($item['id'] ?? false) {
            $inv_item = InvItem::find($item['id']);

         } elseif ($item['code']) {
            $inv_item = InvItem::where('code', $item['code'])
               ->where('inv_area_id', $this->area_id)
               ->first();

            if(!$inv_item) {
               throw new Exception(__('Barang dengan kode item ini tidak ditemukan di area yang dipilih'));

            }
         }

         if($inv_item) {
            if($inv_item->inv_area_id !== $this->area_id) {
               throw new Exception(__('Barang dengan ID ini bukan untuk area yang dipilih'));
            }

         } else {
            throw new Exception(__('Barang tidak ditemukan'));

         }

         $response = Gate::inspect('store', $inv_item);
         if($response->denied()) {
            throw new Exception ($response->message());
         }

         $parts = explode('-', $item['location'], 2);
         $item['loc_parent'] = $parts[0];
         $item['loc_bin'] = isset($parts[1]) ? $parts[1] : '';

         $validator = Validator::make(
            $item,
            [
               'loc_parent'   => ['required_with:loc_bin', 'alpha_num','max:3'],
               'loc_bin'      => ['required_with:loc_parent', 'alpha_dash','max:7'],
            ]
         );

         if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorMessage = implode(', ', $errors);
            throw new Exception($errorMessage);
         }

         $loc_id = null;
         if ($item['loc_parent'] && $item['loc_bin']) {
            $loc_id = InvLoc::firstOrCreate([
               'parent' => $item['loc_parent'],
               'bin'    => $item['loc_bin'],
            ])->id;
         }

         $inv_item->inv_loc_id = $loc_id;
         $inv_item->save();
         
         $status  = __('Berhasil');
         $message = __('Lokasi barang diperbarui');

         $this->result['success']++;

      } catch (\Throwable $th) {
         $status  = __('Gagal');
         $message = $th->getMessage();

        $this->result['failure']++;
      }

      $action = __('Perbarui lokasi');
      $item['action']   = $action;
      $item['status']   = $status;
      $item['message']  = $message;

      return $item;
   }

   public function downloadItems($area_id)
   {
       // Create a unique token for this download request
       $token = md5(uniqid());

       // Store the token in the session
       session()->put('inv_items_backup_token', $token);
       
       $this->js('toast("' . __('Unduhan dimulai...') . '", { type: "success" })');
       // Redirect to a temporary route that will handle the streaming
       return redirect()->route('download.inv-items-backup', ['token' => $token, 'area_id' => $area_id]);
   }

};

?>

<x-slot name="title">{{ __('Perbarui lokasi') . ' — ' . __('Inventaris') }}</x-slot>

<x-slot name="header">
    <x-nav-inventory-sub>{{ __('Operasi massal barang') }}</x-nav-inventory-sub>
</x-slot>

<div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 text-neutral-700 dark:text-neutral-200">
   @if (count($areas))
      <div wire:key="modals">
         <x-modal name="warning">
            <div class="p-6 space-y-4 text-sm">
               <div class="flex justify-between items-start">
                  <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
                     <i class="icon-triangle-alert mr-2 text-yellow-600"></i>{{ __('Teralu banyak') }}
                  </h2>
                  <x-text-button type="button" x-on:click="$dispatch('close')">
                     <i class="icon-x"></i>
                  </x-text-button>
               </div>
               <div>
                  {{ __('Entri yang dimasukkan melebihi 100, harap kurangi entri sebelum melanjutkan.') }}
               </div>
               <div class="flex items-center justify-end">
                  <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Paham') }}</x-secondary-button>
               </div>
            </div>
         </x-modal>
         <x-modal name="apply-confirm">
            <div class="p-6 space-y-4 text-sm">
               <div class="flex justify-between items-start">
                  <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">{{ __('Ringkasan') }}
                  </h2>
                  <x-text-button type="button" x-on:click="$dispatch('close')">
                     <i class="icon-x"></i>
                  </x-text-button>
               </div>
               @if(count($result['items']))
                  @if($result['success'] || $result['failure'])
                  <div class="p-6 border border-neutral-200 dark:border-neutral-700 rounded-lg">
                     <div class="flex items-center space-x-2 mb-2">
                        <h2 class="font-bold text-xl">{{ __('Hasil pembaruan') }}</h2>
                     </div>
                     @if($result['success'])
                     <div>
                        <x-pill color="green">{{ $result['success'] }}</x-pill>{{ ' ' . __('lokasi barang diperbarui.') }}                     
                     </div>
                     @endif
                     @if($result['failure'])
                     <div>
                        <x-pill color="red">{{ $result['failure'] }}</x-pill>{{ ' ' . __('lokasi barang gagal diperbarui.') }}                     
                     </div>
                     @endif
                  </div>
                  @endif
                  @if($result['failure'])
                  <div class="p-4 text-xs text-neutral-800 dark:text-neutral-400 rounded-lg bg-neutral-200 dark:bg-neutral-900">
                     <i class="icon-info me-2"></i>{{ __('Alasan mengapa gagal dapat dilihat pada 3 kolom terakhir (Tindakan, Status, dan Pesan) pada CSV yang terunduh.') }}
                  </div>
                  @endif
                  <div class="flex items-center justify-end">
                     <x-primary-button type="button" x-on:click="$dispatch('close')">{{ __('Selesai') }}</x-secondary-button>
                  </div>

               @else
                  <div>
                     <x-pill>{{ $count }}</x-pill>{{ ' ' . __('lokasi barang akan diperbarui.') }}
                  </div>
                  <div>
                     {{ __('Di area mana lokasi barang tersebut akan diperbarui?') }}
                  </div>
                  <div>
                     <label for="area_id"
                     class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Area') }}</label>
                     <x-select wire:model="area_id" class="w-full">
                        <option value=""></option>
                        @foreach($areas as $area)
                           <option value="{{ $area['id'] }}">{{ $area['name'] }}</option>
                        @endforeach
                     </x-select>
                     <x-input-error :messages="$errors->get('area_id')" class="mt-2" />
                  </div>
                  <div class="flex items-center justify-end">
                     <x-secondary-button type="button" wire:click="apply(true)">{{ __('Lanjut') }}<i class="icon-chevron-right ml-2"></i></x-secondary-button>
                  </div>
               @endif

            </div>
            <x-spinner-bg wire:loading.class.remove="hidden" wire:target="apply"></x-spinner-bg>
            <x-spinner wire:loading.class.remove="hidden" wire:target="apply" class="hidden"></x-spinner>
         </x-modal>
         <x-modal name="guide" maxWidth="lg">
            <div x-data="{ backup: false }" class="p-6 space-y-4 text-sm">
               <div class="flex justify-between items-start">
                  <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
                     {{ __('Panduan') }}
                  </h2>
                  <x-text-button type="button" x-on:click="$dispatch('close')">
                     <i class="icon-x"></i>
                  </x-text-button>
               </div>

               <div x-show="!backup" class="p-6 border border-neutral-200 dark:border-neutral-700 rounded-lg">
                  <div class="flex items-center space-x-2 mb-2">
                     <i class="icon-clipboard text-neutral-500"></i>
                     <h2 class="font-bold text-xl">{{ __('Salin dan tempel') }}</h2>
                  </div>
                  <p class="leading-relaxed">
                     {{ __('Tempel daftar barang yang hendak kamu perbarui lokasinya di kotak editor. Maksimal 100 entri dalam sekali operasi.') }}
                  </p>
               </div>

               <div x-show="backup" class="p-6 border border-neutral-200 dark:border-neutral-700 rounded-lg">
                  <div class="flex items-center space-x-2 mb-2">
                     <i class="fas icon-download text-neutral-500"></i>
                     <h2 class="font-bold text-xl">{{ __('Unduh backup') }}</h2>
                  </div>
                  <p class="leading-relaxed">
                     {{ __('Kamu bisa mengunduh daftar lengkap barang dari suatu area sebagai tindakan pencegahan bila terjadi kesalahan.') }}
                  </p>
               </div>
               
               <!-- Section 4: Unduh backup -->
               <div x-show="backup" class="grid grid-cols-1 gap-y-2 p-6">
                  @foreach ($areas as $area)
                     <div>
                        <x-text-button type="button" wire:click="downloadItems({{ $area['id'] }})"><i class="icon-download mr-3"></i>{{ $area['name'] }}</x-text-button>
                     </div>
                  @endforeach
               </div>

               <div class="flex items-center justify-between">
                  <x-secondary-button x-show="backup" x-on:click="backup = false" type="button"><i class="icon-chevron-left mr-2"></i>{{ __('Kembali') }}</x-secondary-button>
                  <x-text-button x-show="!backup" x-on:click="backup = true" type="button" class="uppercase tracking-wide font-bold text-xs">{{ __('Unduh backup') }}</x-text-button>
                  <x-primary-button x-show="!backup" type="button" x-on:click="$dispatch('close')">{{ __('Paham') }}</x-primary-button>
               </div>
            </div>
            <x-spinner-bg wire:loading.class.remove="hidden" wire:target="download"></x-spinner-bg>
            <x-spinner wire:loading.class.remove="hidden" wire:target="download" class="hidden"></x-spinner>
         </x-modal>
      </div>
      <div 
      x-data="editorData()"
      x-init="editorInit()">
         <div class="flex flex-col sm:flex-row gap-y-6 justify-between px-6 mb-8">
            <h1 class="text-2xl text-neutral-900 dark:text-neutral-100"><i class="icon-map-pin mr-3"></i>{{ __('Perbarui lokasi') }}</h1>
            <div class="flex gap-x-2">
               <div class="px-2 my-auto">
                  <span x-text="rowCount"></span><span class="">{{ ' ' . __('baris') }}</span>
               </div>
               <div class="btn-group">
                  <x-secondary-button type="button" x-on:click="editorDownload"><i class="icon-download"></i></x-secondary-button>
                  <x-secondary-button type="button" x-on:click="editorReset" class="rounded-none"><i class="icon-rotate-cw"></i></x-secondary-button>
                  <x-secondary-button type="button" x-on:click="$dispatch('open-modal', 'guide')"><i class="far icon-circle-help"></i></x-secondary-button>
               </div>
               <x-secondary-button type="button" x-on:click="editorApply">
                  <div class="relative">
                     <span wire:loading.class="opacity-0" wire:target="apply"><i class="icon-circle-check mr-2"></i>{{ __('Terapkan') }}</span>
                     <x-spinner wire:loading.class.remove="hidden" wire:target="apply" class="hidden sm mono"></x-spinner>                
                  </div>                
               </x-secondary-button>
            </div>
         </div>
         <div class="bg-white dark:bg-neutral-800 shadow rounded-lg text-sm" id="editor-table" wire:ignore></div>
      </div>

   @else
      <div class="text-center w-72 py-20 mx-auto">
         <i class="icon-octagon-minus text-5xl mb-8 text-neutral-400 dark:text-neutral-600"></i>
         <div class="text-neutral-500">{{ __('Kamu tidak memiliki wewenang untuk mengelola barang di area manapun.') }}</div>
      </div>

   @endif

</div>

@script
<script type="module">   
   Alpine.data('editorData', () => ({
         table: null,
         items: @entangle('items'),
         itemsDefault: null,
         rowCount: 0,            
         
         editorInit() {
            const columns = [
               { title: 'id', field: 'id', width: 50 }, 
               { title: 'code', field: 'code', width: 110 }, 
               { title: 'location', field: 'location', width: 80 },
            ];
            
            this.itemsDefault = this.itemsDefault ? this.itemsDefault : this.items,

            // Initialize Tabulator
            this.table = new Tabulator("#editor-table", {
               
               data: this.itemsDefault,
               layout: "fitColumns",
               columns: columns,
               height: "calc(100vh - 19rem)",

               //enable range selection
               selectableRange: 1,
               selectableRangeColumns: true,
               selectableRangeRows: true,
               selectableRangeClearCells: true,

               //change edit trigger mode to make cell navigation smoother
               editTriggerEvent:"dblclick",

               //configure clipboard to allow copy and paste of range format data
               clipboard: true,
               clipboardCopyStyled:false,
               clipboardCopyConfig:{
                  rowHeaders:false,
                  columnHeaders:false,
               },
               clipboardCopyRowRange:"range",
               clipboardPasteParser:"range",
               clipboardPasteAction:"replace",

               rowHeader:{resizable: false, frozen: true, width:40, hozAlign:"center", formatter: "rownum", cssClass:"range-header-col", editor:false},
               columnDefaults:{
                  headerSort:false,
                  headerHozAlign:"center",
                  resizable:"header",
                  editor: "input"
               }
            });      
            
            this.table.on("dataLoaded", (data) => {

               if (data.length > 100) {
                  $dispatch('open-modal', 'warning');
               }

               // Check if the last row exists and is empty (all properties are empty strings)
               if (data.length > 0) {
                  const lastRow = data[data.length - 1];
                  const isLastRowEmpty = Object.values(lastRow).every(value => value === "");

                  // If the last row is empty, remove it
                  if (isLastRowEmpty) {
                     data.pop(); // Remove the last row from the data array
                     this.table.setData(data);
                  }
               }

               this.rowCount = data.length; // Update the row count
            });

            this.table.on("dataChanged", (data) => {             
               this.rowCount = data.length; // Update the row count
            });
            
            document.addEventListener('editor-reset', event => {
               this.table.destroy();
               this.editorInit();
            });
         },
         
         editorApply() {
            this.items = this.table.getData();
            $wire.apply();
         },

         editorReset() {
            Livewire.navigate("{{ route('inventory.items.bulk-operation.update-location') }}");
         },

         editorDownload() {
            this.table.download("csv", "operasi-massal-perbarui-lokasi.csv"); 
         },
   }));
</script>
@endscript