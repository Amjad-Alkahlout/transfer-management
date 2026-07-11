<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')]
class extends Component {
    //
};
?>
<div>
    <h1>Dashboard </h1>
    <p>Welcome, {{ auth()->user()->name }}</p>
    <a href="{{ route('bank-accounts.index') }}">Bank Accounts</a>
    <div>
        <a href="{{ route('transfers.index') }}">All Transfers</a>
    </div>


</div>
