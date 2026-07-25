<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'email' => [
                'required',
                'email:rfc',
            ],
            'password' => ['required'],
        ]);

        if (!Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => true,
        ], $this->remember)) {

            $this->addError(
                'email',
                __('auth.errors.invalid_credentials')
            );

            return;
        }

        session()->regenerate();

        return $this->redirectRoute('dashboard');
    }
};
?>

<x-ui.card
    :title="__('auth.pages.login.title')"
    :description="__('auth.pages.login.description')"
>

    <form
        wire:submit="login"
        class="space-y-6"
    >

        <x-ui.input
            :label="__('auth.pages.login.email')"
            name="email"
            type="email"
            wire:model="email"
        />

        <x-ui.input
            :label="__('auth.pages.login.password')"
            name="password"
            type="password"
            wire:model="password"
        />

        <div class="flex items-center gap-2">

            <input
                id="remember"
                type="checkbox"
                wire:model="remember"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >

            <label
                for="remember"
                class="text-sm text-gray-700"
            >
                {{ __('auth.pages.login.remember') }}
            </label>

        </div>

        <x-ui.button
            type="submit"
            class="w-full"
        >
            {{ __('auth.pages.login.button') }}
        </x-ui.button>

    </form>
</x-ui.card>
