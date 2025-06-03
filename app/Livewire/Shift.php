<?php

namespace App\Livewire;

use App\Models\Shift as ModelsShift;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Shift extends Component
{
    public $open_shift = false;
    public $initial_cash;
    public function mount()
    {
        $shift = ModelsShift::latest()->first();
        if ($shift && $shift->status == 'open') {
            return redirect()->route('pos');
        }
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('pos');

    }
    public function openShiftModal()
    {
        $this->open_shift = true;
    }
    public function open_shift_date()
    {
        ModelsShift::create([
            'initial_cash' => $this->initial_cash,
            'user_id' => Auth::id(),
            'start_time' => now(),
        ]);
        return redirect()->route('pos');
    }
    public function render()
    {
        return view('livewire.shift');
    }
}
