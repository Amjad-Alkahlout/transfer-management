<div class="space-y-3">

    @if(session('success'))

        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            role="status"
            aria-live="polite"
        >
            <x-ui.alert color="success">
                {{ session('success') }}
            </x-ui.alert>
        </div>

    @endif

    @if(session('error'))

        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            role="alert"
            aria-live="assertive"
        >
            <x-ui.alert color="danger">
                {{ session('error') }}
            </x-ui.alert>
        </div>

    @endif

    @if(session('warning'))

        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            role="status"
            aria-live="polite"
        >
            <x-ui.alert color="warning">
                {{ session('warning') }}
            </x-ui.alert>
        </div>

    @endif

</div>
