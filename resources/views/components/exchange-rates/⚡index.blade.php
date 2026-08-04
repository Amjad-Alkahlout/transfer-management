<?php

use App\Models\ExchangeRate;
use App\Support\LocalTime;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $rates = [];
    public $showUpdateForm = false;

    public function openUpdateForm()
    {
        $this->loadRates();
        $this->showUpdateForm = true;
    }

    public function hideUpdateForm()
    {
        $this->showUpdateForm = false;
    }

    private function loadRates(): void
    {
        $this->rates = ExchangeRate::orderBy('currency')->get()->map(function ($rate) {
            return [
                'id' => $rate->id,
                'currency' => $rate->currency,
                'rate_to_usd' => (float)$rate->rate_to_usd,
                'updated_at' => $rate->updated_at,
            ];
        })->toArray();
    }

    public function mount()
    {
        Gate::authorize('manage-exchange-rates');
        $this->loadRates();
    }

    public function saveRates()
    {
        $this->validate([
            'rates.*.rate_to_usd' => 'required|numeric|gt:0',
        ]);
        DB::transaction(function () {
            foreach ($this->rates as $rate) {
                ExchangeRate::find($rate['id'])->update([
                    'rate_to_usd' => $rate['rate_to_usd'],
                ]);
            }
        });
        $this->loadRates();
        session()->flash(
            'success',
            __('exchange_rates.messages.updated')
        );
        $this->hideUpdateForm();
    }
};
?>

<div>
    <x-ui.page-header
        :title="__('exchange_rates.page.title')"
        :description="__('exchange_rates.page.description')"
    >
        <x-slot:actions>

            <x-ui.button
                wire:click="openUpdateForm"
            >
                {{ __('exchange_rates.buttons.update') }}
            </x-ui.button>

        </x-slot:actions>

    </x-ui.page-header>
    <div class="mb-6">

        <x-ui.button
            :href="route('dashboard')"
            variant="secondary"
        >
            {{ app()->getLocale() === 'ar' ? '→' : '←' }}
            {{ __('exchange_rates.buttons.back') }}
        </x-ui.button>

    </div>
    <x-ui.flash/>

    <x-ui.card
        :title="__('exchange_rates.table.title')"
        :description="__('exchange_rates.table.description')"
        class="mb-4"
    >

        <x-ui.table>
            <x-ui.table-header>
                <x-ui.table-row>
                    <x-ui.table-head>{{ __('exchange_rates.table.currency') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('exchange_rates.table.rate_to_usd') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('exchange_rates.table.last_updated') }}</x-ui.table-head>
                </x-ui.table-row>
            </x-ui.table-header>
            <x-ui.table-body>
                @foreach ($rates as $rate)
                    <x-ui.table-row>
                        <x-ui.table-cell>{{  $rate['currency']->label()  }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ number_format((float) $rate['rate_to_usd'], 8) }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ LocalTime::format($rate['updated_at']) }}</x-ui.table-cell>
                    </x-ui.table-row>
                @endforeach
            </x-ui.table-body>
        </x-ui.table>
    </x-ui.card>

    @if($showUpdateForm)

        <x-ui.card
            :title="__('exchange_rates.form.title')"

            :description="__('exchange_rates.form.description')"
        >

            <form
                wire:submit.prevent="saveRates"
                class="space-y-6"
            >
                @foreach ($rates as $index => $rate)
                    <x-ui.input
                        :label="$rate['currency']->label()"
                        :name="'rates.'.$index.'.rate_to_usd'"
                        type="number"
                        step="any"
                        wire:model.live="rates.{{ $index }}.rate_to_usd"
                    />
                @endforeach
                <div class="flex justify-end gap-3">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="hideUpdateForm"
                    >
                        {{ __('exchange_rates.buttons.cancel') }}
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                    >
                        {{ __('exchange_rates.buttons.save') }}
                    </x-ui.button>

                </div>
            </form>
        </x-ui.card>
    @endif

</div>
