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
        Gate::authorize('manage-commission-rules');
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
                __('commission_rules.errors.overlap')
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
        session()->flash(
            'success',
            __('commission_rules.messages.created')
        );
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
            'success',
            __('commission_rules.messages.deleted')
        );
    }
};
?>

<div>

    <x-ui.page-header
        :title="__('commission_rules.page.title')"
        :description="__('commission_rules.page.description')"
    >
        <x-slot:actions>

            <x-ui.button
                wire:click="openAddRuleForm"
            >
                {{ __('commission_rules.buttons.add') }}
            </x-ui.button>

        </x-slot:actions>

    </x-ui.page-header>

    <div class="mb-6">

        <x-ui.button
            :href="route('dashboard')"
            variant="secondary"
        >
            {{ app()->getLocale() === 'ar' ? '→' : '←' }}
            {{ __('commission_rules.buttons.back') }}
        </x-ui.button>

    </div>

    <x-ui.flash/>
    <x-ui.card
        :title="__('commission_rules.filter.title')"
        class="mb-2 mt-2"
    >

        <x-ui.select
            :label="__('commission_rules.fields.currency')"
            name="currency"
            wire:model.live="currency"
        >

            @foreach(CurrencyType::cases() as $currency)

                <option value="{{ $currency->value }}">
                    {{ $currency->label() }}
                </option>

            @endforeach

        </x-ui.select>

    </x-ui.card>


    <x-ui.card
        :title="__('commission_rules.table.title')"

        :description="__('commission_rules.table.description')"
        class="mb-2 mt-2"
    >

        <x-ui.table>

        <x-ui.table-header>
        <x-ui.table-row>
            <x-ui.table-head>{{ __('commission_rules.table.from') }}</x-ui.table-head>
            <x-ui.table-head>{{ __('commission_rules.table.to') }}</x-ui.table-head>
            <x-ui.table-head>{{ __('commission_rules.table.commission') }}</x-ui.table-head>
            <x-ui.table-head>{{ __('commission_rules.table.actions') }}</x-ui.table-head>
        </x-ui.table-row>
        </x-ui.table-header>

        <x-ui.table-body>

        @forelse($rules as $rule)

                <x-ui.table-row wire:key="commission-rule-{{ $rule->id }}">
                    <x-ui.table-cell>{{ $rule->min_amount }}</x-ui.table-cell>
                    <x-ui.table-cell>{{ $rule->max_amount }}</x-ui.table-cell>
                    <x-ui.table-cell>{{ $rule->commission_amount }}
                        {{ \App\Enums\CurrencyType::AED->symbol() }}</x-ui.table-cell>
                    <x-ui.table-cell>
                        <x-ui.button
                            variant="danger"
                            wire:confirm="{{ __('commission_rules.confirmations.delete') }}"
                            wire:click="deleteRule({{ $rule->id }})"
                        >
                            {{ __('commission_rules.buttons.delete') }}
                        </x-ui.button>
                    </x-ui.table-cell>
                </x-ui.table-row>

        @empty

                <x-ui.table-row>

                    <x-ui.table-cell
                        colspan="4"
                        class="p-0"
                    >

                        <x-ui.empty-state
                            :title="__('commission_rules.empty_state.title')"

                            :description="__('commission_rules.empty_state.description')"
                        />

                    </x-ui.table-cell>

                </x-ui.table-row>

        @endforelse

        </x-ui.table-body>

    </x-ui.table>
    </x-ui.card>


    @if($showAddRuleForm)

        <x-ui.card
            :title="__('commission_rules.form.title')"

            :description="__('commission_rules.form.description')"
        >

            <form
                wire:submit.prevent="addRule"
                class="space-y-6"
            >

                <x-ui.input
                    :label="__('commission_rules.fields.from')"
                    name="newRule.min_amount"
                    type="number"
                    step="0.01"
                    wire:model="newRule.min_amount"
                />

                <x-ui.input
                    :label="__('commission_rules.fields.to')"
                    name="newRule.max_amount"
                    type="number"
                    step="0.01"
                    wire:model="newRule.max_amount"
                />

                <x-ui.input
                    :label="__('commission_rules.fields.commission')"
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
                        {{ __('commission_rules.buttons.cancel') }}
                    </x-ui.button>

                    <x-ui.button
                        type="submit"
                    >
                        {{ __('commission_rules.buttons.save') }}
                    </x-ui.button>

                </div>

    </form>
    </x-ui.card>
    @endif
</div>
