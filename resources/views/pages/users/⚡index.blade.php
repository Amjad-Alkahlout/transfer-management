<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')]
class extends Component {
    use WithPagination;

    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role;
    public $showAddForm = false;

    public ?User $editingUser = null;
    public $editName;
    public $editEmail;
    public $editRole;
    public $editIsActive;
    public $showEditForm = false;

    public ?User $passwordUser = null;
    public $new_password;
    public $new_password_confirmation;
    public $showPasswordForm = false;

    public ?User $deletingUser = null;
    public $showDeleteConfirm = false;

    public function mount()
    {
        Gate::authorize('manage-users');
    }

    #[Computed]
    public function users()
    {
        return User::query()->orderBy('name')->paginate(10);
    }

    #[Computed]
    public function activeAdminsCount()
    {
        return User::query()
            ->where('role', UserRole::ADMIN)
            ->where('is_active', true)
            ->count();
    }

    public function openAddForm()
    {
        Gate::authorize('manage-users');
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role']);
        $this->showAddForm = true;
    }

    public function closeAddForm()
    {
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role']);
        $this->showAddForm = false;
    }

    public function addUser()
    {
        Gate::authorize('manage-users');

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'is_active' => true,
        ]);

        $this->closeAddForm();
        $this->resetPage();
        unset($this->users);
        unset($this->activeAdminsCount);

        session()->flash('success', __('users.messages.created'));
    }

    public function openEditForm(int $userId)
    {
        Gate::authorize('manage-users');
        $this->resetValidation();

        $this->editingUser = User::findOrFail($userId);
        $this->editName = $this->editingUser->name;
        $this->editEmail = $this->editingUser->email;
        $this->editRole = $this->editingUser->role->value;
        $this->editIsActive = $this->editingUser->is_active ? '1' : '0';

        $this->showEditForm = true;
    }

    public function closeEditForm()
    {
        $this->reset(['editingUser', 'editName', 'editEmail', 'editRole', 'editIsActive']);
        $this->resetValidation();
        $this->showEditForm = false;
    }

    public function updateUser()
    {
        Gate::authorize('manage-users');

        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|max:255|unique:users,email,' . $this->editingUser->id,
            'editRole' => ['required', Rule::enum(UserRole::class)],
        ]);

        $wasActiveAdmin = $this->editingUser->role === UserRole::ADMIN && $this->editingUser->is_active;
        $willStillBeActiveAdmin = $this->editRole === UserRole::ADMIN->value && $this->editIsActive === '1';

        if ($wasActiveAdmin && ! $willStillBeActiveAdmin && $this->activeAdminsCount <= 1) {
            $this->addError('editRole', __('users.errors.last_admin'));
            return;
        }

        $this->editingUser->update([
            'name' => $this->editName,
            'email' => $this->editEmail,
            'role' => $this->editRole,
            'is_active' => $this->editIsActive === '1',
        ]);

        $this->closeEditForm();
        unset($this->users);
        unset($this->activeAdminsCount);

        session()->flash('success', __('users.messages.updated'));
    }

    public function toggleActive(int $userId)
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', __('users.errors.cannot_modify_self'));
            return;
        }

        if ($user->role === UserRole::ADMIN && $user->is_active && $this->activeAdminsCount <= 1) {
            session()->flash('error', __('users.errors.last_admin'));
            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        unset($this->users);
        unset($this->activeAdminsCount);

        session()->flash(
            'success',
            $user->is_active ? __('users.messages.activated') : __('users.messages.deactivated')
        );
    }

    public function openPasswordForm(int $userId)
    {
        Gate::authorize('manage-users');
        $this->resetValidation();

        $this->passwordUser = User::findOrFail($userId);
        $this->reset(['new_password', 'new_password_confirmation']);

        $this->showPasswordForm = true;
    }

    public function closePasswordForm()
    {
        $this->reset(['passwordUser', 'new_password', 'new_password_confirmation']);
        $this->resetValidation();
        $this->showPasswordForm = false;
    }

    public function setPassword()
    {
        Gate::authorize('manage-users');

        $this->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $this->passwordUser->update([
            'password' => $this->new_password,
        ]);

        $this->closePasswordForm();

        session()->flash('success', __('users.messages.password_updated'));
    }

    public function confirmDelete(int $userId)
    {
        Gate::authorize('manage-users');
        $this->deletingUser = User::findOrFail($userId);
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete()
    {
        $this->reset(['deletingUser']);
        $this->showDeleteConfirm = false;
    }

    public function deleteUser()
    {
        Gate::authorize('manage-users');

        if ($this->deletingUser->id === auth()->id()) {
            session()->flash('error', __('users.errors.cannot_modify_self'));
            $this->cancelDelete();
            return;
        }

        if (
            $this->deletingUser->role === UserRole::ADMIN
            && $this->deletingUser->is_active
            && $this->activeAdminsCount <= 1
        ) {
            session()->flash('error', __('users.errors.last_admin'));
            $this->cancelDelete();
            return;
        }

        try {
            $this->deletingUser->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            $this->cancelDelete();
            session()->flash('error', __('users.errors.cannot_delete_has_history'));
            return;
        }

        $this->cancelDelete();
        $this->resetPage();
        unset($this->users);
        unset($this->activeAdminsCount);

        session()->flash('success', __('users.messages.deleted'));
    }
};
?>

<div>
    <x-ui.page-header
        :title="__('users.page.title')"
        :description="__('users.page.description')"
    >
        <x-slot:actions>
            <x-ui.button wire:click="openAddForm">
                {{ __('users.buttons.add') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash/>


    <div class="mb-6">
        <x-ui.button :href="route('dashboard')" variant="secondary">
            {{ app()->getLocale() === 'ar' ? '→' : '←' }}
            {{ __('capital_accounts.buttons.back') }}
        </x-ui.button>
    </div>

    <x-ui.card
        :title="__('users.table.title')"
        :description="__('users.table.description')"
    >
        <x-ui.table>
            <x-ui.table-header>
                <x-ui.table-row>
                    <x-ui.table-head>{{ __('users.table.name') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('users.table.email') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('users.table.role') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('users.table.status') }}</x-ui.table-head>
                    <x-ui.table-head>{{ __('users.table.actions') }}</x-ui.table-head>
                </x-ui.table-row>
            </x-ui.table-header>
            <x-ui.table-body>
                @foreach($this->users as $user)
                    <x-ui.table-row>
                        <x-ui.table-cell>
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="text-xs text-gray-400">({{ __('users.labels.you') }})</span>
                            @endif
                        </x-ui.table-cell>
                        <x-ui.table-cell>{{ $user->email }}</x-ui.table-cell>
                        <x-ui.table-cell>{{ $user->role->label() }}</x-ui.table-cell>
                        <x-ui.table-cell>
                            @if($user->is_active)
                                <x-ui.badge color="green">{{ __('users.status.active') }}</x-ui.badge>
                            @else
                                <x-ui.badge color="red">{{ __('users.status.inactive') }}</x-ui.badge>
                            @endif
                        </x-ui.table-cell>
                        <x-ui.table-cell>
                            <div class="flex flex-wrap gap-2">

                                <x-ui.button
                                    variant="secondary"
                                    wire:click="openEditForm({{ $user->id }})"
                                    class="justify-center"
                                >
                                    {{ __('users.buttons.edit') }}
                                </x-ui.button>

                                <x-ui.button
                                    variant="secondary"
                                    wire:click="openPasswordForm({{ $user->id }})"
                                    class="justify-center"
                                >
                                    {{ __('users.buttons.set_password') }}
                                </x-ui.button>

                                @if($user->id !== auth()->id())

                                    <x-ui.button
                                        variant="secondary"
                                        wire:click="toggleActive({{ $user->id }})"
                                        class="justify-center"
                                    >
                                        @if($user->is_active)
                                            {{ __('users.buttons.deactivate') }}
                                        @else
                                            {{ __('users.buttons.activate') }}
                                        @endif
                                    </x-ui.button>

                                    <x-ui.button
                                        variant="danger"
                                        wire:click="confirmDelete({{ $user->id }})"
                                        class="justify-center"
                                    >
                                        {{ __('users.buttons.delete') }}
                                    </x-ui.button>

                                @endif

                            </div>
                        </x-ui.table-cell>
                    </x-ui.table-row>
                @endforeach
            </x-ui.table-body>
        </x-ui.table>

        <x-slot:footer>
            {{ $this->users->links() }}
        </x-slot:footer>
    </x-ui.card>

    @if($showAddForm)
        <x-ui.card
            :title="__('users.forms.add.title')"
            :description="__('users.forms.add.description')"
            class="mt-6"
        >
            <form wire:submit.prevent="addUser" class="space-y-6">

                <x-ui.input :label="__('users.fields.name')" name="name" wire:model="name" />

                <x-ui.input :label="__('users.fields.email')" name="email" type="email" wire:model="email" />

                <x-ui.input :label="__('users.fields.password')" name="password" type="password" wire:model="password" />

                <x-ui.input :label="__('users.fields.password_confirmation')" name="password_confirmation" type="password" wire:model="password_confirmation" />

                <x-ui.select :label="__('users.fields.role')" name="role" wire:model="role">
                    <option value="">{{ __('users.placeholders.select_role') }}</option>
                    @foreach(UserRole::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </x-ui.select>

                <div class="flex justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" wire:click="closeAddForm">
                        {{ __('general.buttons.cancel') }}
                    </x-ui.button>
                    <x-ui.button type="submit">
                        {{ __('users.buttons.add') }}
                    </x-ui.button>
                </div>

            </form>
        </x-ui.card>
    @endif

    @if($showEditForm)
        <x-ui.card
            :title="__('users.forms.edit.title')"
            :description="__('users.forms.edit.description')"
            class="mt-6"
        >
            <form wire:submit.prevent="updateUser" class="space-y-6">

                <x-ui.input :label="__('users.fields.name')" name="editName" wire:model="editName" />

                <x-ui.input :label="__('users.fields.email')" name="editEmail" type="email" wire:model="editEmail" />

                <x-ui.select :label="__('users.fields.role')" name="editRole" wire:model="editRole">
                    @foreach(UserRole::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select :label="__('users.fields.status')" name="editIsActive" wire:model="editIsActive">
                    <option value="1">{{ __('users.status.active') }}</option>
                    <option value="0">{{ __('users.status.inactive') }}</option>
                </x-ui.select>

                <div class="flex justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" wire:click="closeEditForm">
                        {{ __('general.buttons.cancel') }}
                    </x-ui.button>
                    <x-ui.button type="submit">
                        {{ __('users.buttons.update') }}
                    </x-ui.button>
                </div>

            </form>
        </x-ui.card>
    @endif

    @if($showPasswordForm)
        <x-ui.card
            :title="__('users.forms.password.title')"
            :description="__('users.forms.password.description', ['user' => $passwordUser?->name])"
            class="mt-6"
        >
            <form wire:submit.prevent="setPassword" class="space-y-6">

                <x-ui.input :label="__('users.fields.new_password')" name="new_password" type="password" wire:model="new_password" />

                <x-ui.input :label="__('users.fields.new_password_confirmation')" name="new_password_confirmation" type="password" wire:model="new_password_confirmation" />

                <div class="flex justify-end gap-3">
                    <x-ui.button type="button" variant="secondary" wire:click="closePasswordForm">
                        {{ __('general.buttons.cancel') }}
                    </x-ui.button>
                    <x-ui.button type="submit">
                        {{ __('users.buttons.save_password') }}
                    </x-ui.button>
                </div>

            </form>
        </x-ui.card>
    @endif

    <x-ui.modal :show="$showDeleteConfirm" :title="__('users.modals.delete.title')" maxWidth="md">

        <p class="text-sm text-gray-600">
            {{ __('users.modals.delete.message', ['user' => $deletingUser?->name]) }}
        </p>

        <x-slot:footer>
            <div class="flex justify-end gap-3">
                <x-ui.button type="button" variant="secondary" wire:click="cancelDelete">
                    {{ __('general.buttons.cancel') }}
                </x-ui.button>
                <x-ui.button type="button" variant="danger" wire:click="deleteUser">
                    {{ __('users.buttons.confirm_delete') }}
                </x-ui.button>
            </div>
        </x-slot:footer>

    </x-ui.modal>

</div>
