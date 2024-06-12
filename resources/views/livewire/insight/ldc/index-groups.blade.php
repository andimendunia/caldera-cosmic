<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use App\Models\InsLdcGroup;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

new class extends Component {

    public $line;
    public $workdate;
    public $style;
    public $material;

    public $sgid;

    public function rules()
    {
        return [
            'line'        => ['required', 'string', 'min:2', 'max:3', 'regex:/^[a-zA-Z]+[0-9]+$/'],
            'workdate'    => ['required', 'date'],
            'style'       => ['required', 'string', 'min:9', 'max:11'],
            'material'    => ['nullable', 'string', 'max:140'],
        ];
    }

    #[On('hide-saved')]
    public function with(): array
    {
        $groups = InsLdcGroup::where('updated_at', '>=', Carbon::now()->subDay())
                     ->orderBy('updated_at', 'desc')
                     ->get();

        // Filter the records to find a specific group and get the IDs
        $sgid = $groups->filter(function ($group) {
            return $group->line == $this->line &&
                $group->workdate == $this->workdate &&
                $group->style == $this->style &&
                $group->material == $this->material;
        })->first();

        if ($sgid) {
            $this->sgid = $sgid->id;
        }

        return [
            'groups' => $groups
        ];
    }

    public function clean($string): string
    {
        return trim(strtoupper($string));
    }

    public function setGroup()
    {
        $this->line = $this->clean($this->line);
        $this->style = $this->clean($this->style);
        $this->material = $this->clean($this->material);
        $this->validate();
        $this->sgid = 0;
        $this->selectGroup();
        $this->js('window.dispatchEvent(escKey)'); 
        $this->js('notyfSuccess("' . __('Grup diterapkan') . '")');
        $this->dispatch('updated');
    }

    public function selectGroup()
    {
        $data = [
                'line'      => $this->line,
                'workdate'  => $this->workdate,
                'style'     => $this->style,
                'material'  => $this->material
            ];
        $this->dispatch('group-selected', $data);
    }

    public function updated($property)
    {
        if ($property == 'sgid') {

            $group = InsLdcGroup::find($this->sgid);
            if ($group) {
                $this->line     = $group->line;
                $this->workdate = $group->workdate;
                $this->style    = $group->style;
                $this->material = $group->material;
            }

            $this->selectGroup();
        }
    }

    #[On('hide-load')]
    public function hideLoad($data)
    {
        $group = InsLdcGroup::find($data['ins_ldc_group_id']);
        if ($group) {
            $this->line     = $group->line;
            $this->workdate = $group->workdate;
            $this->style    = $group->style;
            $this->material = $group->material;
        }
        
        $this->selectGroup();

    }

};

?>

<div class="flex gap-x-2 items-stretch whitespace-nowrap text-nowrap text-sm min-h-20">
    <x-secondary-button type="button" class="transform translate-y-1 hover:translate-y-0 duration-200 ease-in-out" x-data=""
    x-on:click.prevent="$dispatch('open-modal', 'group-set')"><i class="fa fa-plus"></i></x-secondary-button>
    <x-modal name="group-set" maxWidth="sm">
        <form wire:submit="setGroup" class="p-6">
            <div class="flex justify-between items-start">
                <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">
                    {{ __('Grup baru') }}
                </h2>
                <x-text-button type="button" x-on:click="$dispatch('close')"><i class="fa fa-times"></i></x-text-button>
            </div>
            <div class="mb-6">
                <div class="mt-6">
                    <label for="gs-hide-workdate"
                        class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Workdate') }}</label>
                    <x-text-input id="gs-hide-workdate" wire:model="workdate" type="date" />
                    @error('workdate')
                        <x-input-error messages="{{ $message }}" class="px-3 mt-2" />
                    @enderror
                </div>
                <div class="mt-6">
                    <label for="gs-hide-style"
                        class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Style') }}</label>
                    <x-text-input id="gs-hide-style" wire:model="style" type="text" />
                    @error('style')
                        <x-input-error messages="{{ $message }}" class="px-3 mt-2" />
                    @enderror
                </div>
                <div class="mt-6">
                    <label for="gs-hide-line"
                        class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Line') }}</label>
                    <x-text-input id="gs-hide-line" wire:model="line" type="text" />
                    @error('line')
                        <x-input-error messages="{{ $message }}" class="px-3 mt-2" />
                    @enderror
                </div>
                <div class="mt-6">
                    <label for="gs-hide-material"
                        class="block px-3 mb-2 uppercase text-xs text-neutral-500">{{ __('Material') }}</label>
                    <x-text-input id="gs-hide-material" wire:model="material" type="text" />
                    @error('material')
                        <x-input-error messages="{{ $message }}" class="px-3 mt-2" />
                    @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
    <div wire:key="sgid-0" class="{{ $sgid === 0 ? 'block' : 'hidden' }}">
        <input type="radio" name="sgid" id="sgid-0" value="0" wire:model.live="sgid"
            class="peer hidden [&:checked_+_label_svg]:block" />
        <label for="sgid-0"
            class="block h-full transform translate-y-1 hover:translate-y-0 duration-200 ease-in-out cursor-pointer rounded-lg border bg-white shadow border-transparent dark:bg-neutral-800 px-4 py-2 peer-checked:border-caldy-500 peer-checked:ring-1 peer-checked:ring-caldy-500">
            <div class="flex items-center justify-between text-xl">
                <div>{{ $line }} <span class="text-xs uppercase ml-1 mr-2">{{ Carbon::parse($workdate)->format('d M') }}</span></div>
                <svg class="hidden h-6 w-6 text-caldy-600" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="mt-1">{{ $style }}</div>
            <div class="mt-1 max-w-40 truncate text-xs">{{ $material }}</div>
        </label>
    </div>
    @foreach($groups as $group)
    <div wire:key="sgid-{{ $loop->iteration }}">
        <input type="radio" name="sgid" id="sgid-{{ $loop->iteration }}" value="{{ $group->id }}" wire:model.live="sgid" :checked={{ $group->id == $sgid ? 'true' : 'false'}}
            class="peer hidden [&:checked_+_label_svg]:block" />
        <label for="sgid-{{ $loop->iteration }}"
            class="block h-full transform translate-y-1 hover:translate-y-0 duration-200 ease-in-out cursor-pointer rounded-lg border bg-white shadow border-transparent dark:bg-neutral-800 px-4 py-2 peer-checked:border-caldy-500 peer-checked:ring-1 peer-checked:ring-caldy-500">
            <div class="flex items-center justify-between text-xl">
                <div>{{ $group->line }} <span class="text-xs uppercase ml-1 mr-2">{{ Carbon::parse($group->workdate)->format('d M') }}</span></div>
                <svg class="hidden h-6 w-6 text-caldy-600" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="mt-1">{{ $group->style }}</div>
            <div class="my-1 max-w-40 truncate text-xs">{{ $group->material }}</div>
        </label>
    </div>
    @endforeach
</div>