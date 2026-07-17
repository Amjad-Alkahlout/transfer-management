<?php

use App\Enums\CurrencyType;
use App\Models\CommissionRule;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {

    public $currency;
    public $showAddRuleForm = false;

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

    public function openAddRuleForm()
    {
        $this->resetValidation();
        $this->showAddRuleForm = true;

    }

    public function closeAddRuleForm()
    {
        $this->reset('newRule');
        $this->resetValidation();
        $this->showAddRuleForm = false;
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

        $this->closeAddRuleForm();
        session()->flash('message', 'Commission rule added successfully.');
    }
    public function deleteRule(int $id): void
    {
        $rule = CommissionRule::findOrFail($id);

        if ($rule->currency->value !== $this->currency) {
            abort(403);
        }

        $rule->delete();

        $this->loadRules();

        session()->flash(
            'message',
            'Commission rule deleted successfully.'
        );
    }
};
?>

<div>

    <x-ui.page-header
        title="Commission Rules"
        description="Manage commission rules by currency."
    >
        <x-slot:actions>

            <x-ui.button
                wire:click="openAddRuleForm"
            >
                Add Rule
            </x-ui.button>

        </x-slot:actions>

    </x-ui.page-header>

    @if(session()->has('message'))

        <x-ui.alert color="success">
            {{ session('message') }}
        </x-ui.alert>

    @endif
    <x-ui.card
        title="Currency Filter"
    >

        <x-ui.select
            label="Currency"
            name="currency"
            wire:model.live="currency"
        >

            @foreach(CurrencyType::cases() as $currency)

                <option value="{{ $currency->value }}">
                    {{ $currency->name }}
                </option>

            @endforeach

        </x-ui.select>

    </x-ui.card>


    <x-ui.card
        title="Commission Rules"
        description="Configured commission ranges."
    >

        <x-ui.table>

        <x-UI.table-header>
        <x-UI.table-row>
            <x-UI.table-head>From</x-UI.table-head>
            <x-UI.table-head>To</x-UI.table-head>
            <x-UI.table-head>Commission (AED)</x-UI.table-head>
            <x-UI.table-head>Actions</x-UI.table-head>
        </x-UI.table-row>
        </x-UI.table-header>

        <x-UI.table-body>

        @forelse($rules as $rule)

            <x-UI.table-row>
                <x-UI.table-cell>{{ $rule->min_amount }}</x-UI.table-cell>
                <x-UI.table-cell>{{ $rule->max_amount }}</x-UI.table-cell>
                <x-UI.table-cell>{{ $rule->commission_amount }} AED</x-UI.table-cell>
                <x-UI.table-cell>
                    <x-ui.button
                        variant="danger"
                        wire:confirm="Delete this commission rule?"
                        wire:click="deleteRule({{ $rule->id }})"
                    >
                        Delete
                    </x-ui.button>
                </x-UI.table-cell>
            </x-UI.table-row>

        @empty

                <x-ui.table-row>

                    <x-ui.table-cell
                        colspan="4"
                        class="p-0"
                    >

                        <x-ui.empty-state
                            title="No commission rules"
                            description="Create your first commission rule."
                        />

                    </x-ui.table-cell>

                </x-ui.table-row>

        @endforelse

        </x-UI.table-body>

    </x-ui.table>
    </x-ui.card>


    @if($showAddRuleForm)

        <x-ui.card
            title="Add Commission Rule"
            description="Create a new commission range."
        >

            <form
                wire:submit.prevent="addRule"
                class="space-y-6"
            >

                <x-ui.input
                    label="From"
                    name="newRule.min_amount"
                    type="number"
                    step="0.01"
                    wire:model="newRule.min_amount"
                />

                <x-ui.input
                    label="To"
                    name="newRule.max_amount"
                    type="number"
                    step="0.01"
                    wire:model="newRule.max_amount"
                />

                <x-ui.input
                    label="Commission (AED)"
                    name="newRule.commission_amount"
                    type="number"
                    step="0.01"
                    wire:model="newRule.commission_amount"
                />



                <div class="flex justify-end gap-3">

                    <x-ui.button
                        type="button"
                        variant="secondary"
                        wire:click="closeAddRuleForm"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                    >
                        Save Rule
                    </x-ui.button>

                </div>

    </form>
    </x-ui.card>
    @endif
</div>
