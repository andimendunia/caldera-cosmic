<div x-data="{ 
        filter: @entangle('filter').live,
        get filter_name() {
         switch (this.filter) {
            case 'no-code':
               return `{{ __('Tanpa kode') }}`;
            case 'no-photo':
               return `{{ __('Tanpa foto') }}`;
            case 'no-location':
               return `{{ __('Tanpa lokasi') }}`;
            case 'no-tags':
               return `{{ __('Tanpa tag') }}`;
            case 'inactive':
               return `{{ __('Barang nonaktif') }}`;
            default:
                return '';
            }
        }
    }" class="flex items-center px-4">
    <x-text-button {{ $attributes->merge(['class' => '']) }} type="button" x-on:click.prevent="$dispatch('open-modal', 'search-filter')" ::class="filter ? 'text-neutral-800 dark:text-white' : 'text-neutral-400 dark:text-neutral-600'"><i class="fa fa-fw fa-filter me-3"></i><span x-text="filter ? filter_name : '{{ __('Filter') }}'"></span></x-text-button>
    <x-modal name="search-filter" maxWidth="sm">
        <div class="p-6 flex flex-col gap-y-6">
            <div class="flex justify-between items-start">
                <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
                    <i class="fa fa-fw fa-filter me-3"></i>{{ __('Filter tambahan') }}
                </h2>
                <x-text-button type="button" x-on:click="$dispatch('close')">
                    <i class="fa fa-times"></i>
                </x-text-button>
            </div>
            <div>
                <x-radio x-model="filter" id="filter-no-code" name="filter-no-code" value="no-code">{{ __('Tanpa kode') }}</x-radio>
                <x-radio x-model="filter" id="filter-no-photo" name="filter-no-photo" value="no-photo">{{  __('Tanpa foto') }}</x-radio>
                <x-radio x-model="filter" id="filter-no-location" name="filter-no-location" value="no-location">{{ __('Tanpa lokasi') }}</x-radio>
                <x-radio x-model="filter" id="filter-no-tags" name="filter-no-tags" value="no-tags">{{ __('Tanpa tag') }}</x-radio>
                <x-radio x-model="filter" id="filter-inactive" name="filter-inactive" value="inactive">{{ __('Barang nonaktif') }}</x-radio>
            </div>            
            <div class="flex justify-end">
                <x-text-button class="text-xs uppercase font-semibold" type="button" x-on:click="filter = ''; $dispatch('close');" x-show="filter"><span class="text-red-500"><div class="px-1">{{ __('Hapus filter tambahan') }}</div></span></x-text-button>
            </div>
        </div>
    </x-modal>   
</div>