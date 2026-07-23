<?php

use App\Models\ExchangeRate;
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
                'rate_to_usd' => (float) $rate->rate_to_usd,
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
        session()->flash('success', 'Exchange rates updated successfully.');
        $this->hideUpdateForm();
    }
};
?>

<div>
    <x-ui.page-header
        title="Exchange Rates"
        description="Manage currency exchange rates."
    >
        <x-slot:actions>

            <x-ui.button
                wire:click="openUpdateForm"
            >
                Update Rates
            </x-ui.button>

        </x-slot:actions>

    </x-ui.page-header>
    <x-ui.flash/>

    <x-ui.card
        title="Exchange Rates"
        description="Current exchange rates against USD."
        class="mb-4"
    >

        <x-ui.table>
        <x-ui.table-header>
        <x-ui.table-row>
            <x-ui.table-head>Currency</x-ui.table-head>
            <x-ui.table-head>Rate to USD</x-ui.table-head>
            <x-ui.table-head>Last Updated</x-ui.table-head>
        </x-ui.table-row>
            </x-ui.table-header>
        <x-ui.table-body>
        @foreach ($rates as $rate)
                <x-ui.table-row>
                <x-ui.table-cell>{{ $rate['currency'] }}</x-ui.table-cell>
                <x-ui.table-cell>{{ number_format((float) $rate['rate_to_usd'], 4) }}</x-ui.table-cell>
                <x-ui.table-cell>{{ $rate['updated_at']->format('d/m/Y H:i') }}</x-ui.table-cell>
            </x-ui.table-row>
        @endforeach
        </x-ui.table-body>
        </x-ui.table>
    </x-ui.card>

    @if($showUpdateForm)

        <x-ui.card
            title="Update Exchange Rates"
            description="Update exchange rates relative to USD."
        >

            <form
                wire:submit.prevent="saveRates"
                class="space-y-6"
            >
                @foreach ($rates as $index => $rate)
                    <x-ui.input
                        :label="$rate['currency']"
                        :name="'rates.'.$index.'.rate_to_usd'"
                        type="number"
                        step="0.0001"
                        wire:model.live="rates.{{ $index }}.rate_to_usd"
                    />
                @endforeach
                    <div class="flex justify-end gap-3">

                        <x-ui.button
                            type="button"
                            variant="secondary"
                            wire:click="hideUpdateForm"
                        >
                            Cancel
                        </x-ui.button>

                        <x-ui.button
                            type="submit"
                        >
                            Save Rates
                        </x-ui.button>

                    </div>
            </form>
        </x-ui.card>
    @endif

</div>
