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
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => true,
        ], $this->remember)) {

            $this->addError(
                'email',
                'The provided credentials are incorrect or the account is inactive.'
            );

            return;
        }

        session()->regenerate();

        return $this->redirectRoute('dashboard');
    }
};
?>

<div>
    <form wire:submit="login">

        <div>
            <label>Email</label>

            <input
                type="email"
                wire:model="email"
            >

            @error('email')
            <p>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label>Password</label>

            <input
                type="password"
                wire:model="password"
            >

            @error('password')
            <p>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label>
                <input
                    type="checkbox"
                    wire:model="remember"
                >

                Remember me
            </label>
        </div>

        <button type="submit">
            Login
        </button>

    </form>
</div>
