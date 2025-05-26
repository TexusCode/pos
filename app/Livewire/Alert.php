<?php

namespace App\Livewire;

use Livewire\Component;

class Alert extends Component
{
    public $message;

    public function render()
    {
        return view('livewire.alert');
    }
}
