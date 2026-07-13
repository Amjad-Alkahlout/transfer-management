<?php

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component {
    public $rates = [];
    public $showUpdateForm = false;

    public function showUpdateForm()
    {
        $this->showUpdateForm = true;
    }

    public function hideUpdateForm()
    {
        $this->showUpdateForm = false;
    }

    public function mount()
    {
        $this->rates = ExchangeRate::orderBy('currency')->get();
    }

    public function saveRates()
    {
        $this->validate([
            'rates.*.rate_to_usd' => 'required|numeric|gt:0',
        ]);
        foreach ($this->rates as $rate) {
            ExchangeRate::find($rate['id'])->update([
                'rate_to_usd' => $rate['rate_to_usd'],
            ]);
        }
        $this->rates = ExchangeRate::orderBy('currency')->get()->toArray();
        session()->flash('message', 'Exchange rates updated successfully.');
        $this->showUpdateForm = false;
    }
};
?>

<div>
<h1>Exchange Rates</h1>
    @if(session('message'))
        <div>{{ session('message') }}</div>
    @endif

    <table>
        <thead>
        <tr>
            <th>Currency</th>
            <th>Rate to USD</th>
            <th>Last Updated</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rates as $rate)
            <tr>
                <td>{{ $rate['currency'] }}</td>
                <td>{{ $rate['rate_to_usd'] }}</td>
                <td>{{ $rate->updated_at->format('d/m/Y H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div>
        <button type="button" wire:click="showUpdateForm">Update Rates</button>
    </div>
    @if($showUpdateForm)
        <div>
            <form wire:submit.prevent="saveRates">
                @foreach ($rates as $index => $rate)
                    <div>
                        <label for="rate_{{ $index }}">{{ $rate['currency'] }}:</label>
                        <input type="number" step="0.0001" id="rate_{{ $index }}"
                               wire:model="rates.{{ $index }}.rate_to_usd">
                    </div>
                @endforeach
                <button type="submit">Save Rates</button>
                <button type="button" wire:click="hideUpdateForm">Cancel</button>
            </form>
        </div>
    @endif

</div>
