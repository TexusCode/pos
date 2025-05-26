<?php

namespace App\Livewire;

use Livewire\Component;

class Pos extends Component
{
    public function loading()
    {
        sleep(5);
    }
    public function mount()
    {
        // dd(100 - 30);
    }
    public function render()
    {
        return view('livewire.pos');
    }
}
