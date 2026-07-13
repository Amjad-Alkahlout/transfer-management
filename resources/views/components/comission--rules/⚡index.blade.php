<?php

use App\Enums\CurrencyType;
use App\Models\CommissionRule;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {

    public $currency;

    public $rules = [];

    public $newRule = [
        'min_amount' => null,
        'max_amount' => null,
        'commission_amount' => null,
    ];

    public function mount()
    {
        $this->currency = CurrencyType::USD->value;

        $this->loadRules();
    }

    public function updatedCurrency()
    {
        $this->loadRules();
    }

    private function loadRules(): void
    {
        $this->rules = CommissionRule::where('currency', $this->currency)
            ->orderBy('min_amount')
            ->get();
    }

    public function addRule()
    {
        $this->validate([
            'currency' => [
                'required',
                Rule::enum(CurrencyType::class),
            ],

            'newRule.min_amount' => 'required|numeric|min:0',

            'newRule.max_amount' => 'required|numeric|gt:newRule.min_amount',

            'newRule.commission_amount' => 'required|numeric|min:0',
        ]);

        $overlap = CommissionRule::where('currency', $this->currency)
            ->where('min_amount', '<=', $this->newRule['max_amount'])
            ->where('max_amount', '>=', $this->newRule['min_amount'])
            ->exists();

        if ($overlap) {

            $this->addError(
                'newRule.min_amount',
                'This range overlaps with an existing commission rule.'
            );

            return;
        }

        CommissionRule::create([
            'currency' => $this->currency,
            'min_amount' => $this->newRule['min_amount'],
            'max_amount' => $this->newRule['max_amount'],
            'commission_amount' => $this->newRule['commission_amount'],
        ]);

        $this->loadRules();

        $this->newRule = [
            'min_amount' => null,
            'max_amount' => null,
            'commission_amount' => null,
        ];

        session()->flash('message', 'Commission rule added successfully.');
    }
};
?>

<div>

    <h1>Commission Rules</h1>

    @if(session()->has('message'))
        <div>
            {{ session('message') }}
        </div>
    @endif

    <div>
        <label>Currency</label>

        <select wire:model.live="currency">

            @foreach(CurrencyType::cases() as $currency)

                <option value="{{ $currency->value }}">
                    {{ $currency->name }}
                </option>

            @endforeach

        </select>
    </div>

    <br>

    <table>

        <thead>
        <tr>
            <th>From</th>
            <th>To</th>
            <th>Commission</th>
        </tr>
        </thead>

        <tbody>

        @forelse($rules as $rule)

            <tr>
                <td>{{ $rule->min_amount }}</td>
                <td>{{ $rule->max_amount }}</td>
                <td>{{ $rule->commission_amount }}</td>
            </tr>

        @empty

            <tr>
                <td colspan="3">
                    No commission rules found.
                </td>
            </tr>

        @endforelse

        </tbody>

    </table>

    <hr>

    <h3>Add New Rule</h3>

    <form wire:submit.prevent="addRule">

        <div>
            <label>From</label>

            <input
                type="number"
                step="0.01"
                wire:model="newRule.min_amount"
            >

            @error('newRule.min_amount')
            <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label>To</label>

            <input
                type="number"
                step="0.01"
                wire:model="newRule.max_amount"
            >

            @error('newRule.max_amount')
            <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label>Commission</label>

            <input
                type="number"
                step="0.01"
                wire:model="newRule.commission_amount"
            >

            @error('newRule.commission_amount')
            <span>{{ $message }}</span>
            @enderror
        </div>

        <br>

        <button type="submit">
            Add Rule
        </button>

    </form>

</div>
