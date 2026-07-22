<div class="space-y-3">

    @if(session('success'))

        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
        >
            <x-ui.alert color="success">
                {{ session('success') }}
            </x-ui.alert>
        </div>

    @endif

    @if(session('error'))

        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
        >
            <x-ui.alert color="danger">
                {{ session('error') }}
            </x-ui.alert>
        </div>

    @endif

    @if(session('warning'))

        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
        >
            <x-ui.alert color="warning">
                {{ session('warning') }}
            </x-ui.alert>
        </div>

    @endif

    @error('general')

    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
    >
        <x-ui.alert color="red">
            {{ $message }}
        </x-ui.alert>
    </div>

    @enderror

</div>
