<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Login extends Component
{
    public $phone;
    public $password;
    public $message;
    public function login()
    {
        $user = User::where('phone', $this->phone)->first();
        if (!$user) {
            $this->message = 'Аккаунт не найдено!';
            return;
        }
        if (!Hash::check($this->password, $user->password)) {
            $this->message = 'Пароль не верный!';
            return;
        }
        Auth::login($user, true);
        if (Auth::user()->role == 'audit') {
            return redirect()->route('audit');
        } else {
            return redirect()->route('shift');
        }
    }
    public function render()
    {
        return view('livewire.login');
    }
}
