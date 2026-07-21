<?php

namespace App\Livewire\App;

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfilePage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public bool $editing = false;

    public bool $success = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
    }

    public function toggleEdit(): void
    {
        $this->editing = ! $this->editing;
        $this->resetErrorBag();
        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        if (! $this->editing) {
            $user = auth()->user();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
        }
    }

    public function save(): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'currentPassword' => ['nullable', 'string'],
            'newPassword' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($this->newPassword !== '') {
            if ($this->currentPassword === '') {
                $this->addError('currentPassword', __('app.current_password_required'));

                return;
            }
            if (! Hash::check($this->currentPassword, $user->password)) {
                $this->addError('currentPassword', __('app.current_password_incorrect'));

                return;
            }
        }

        $user->name = $this->name;
        $user->email = $this->email;
        $user->phone = $this->phone ?: null;

        if ($this->newPassword !== '') {
            $user->password = \Illuminate\Support\Facades\Hash::make($this->newPassword);
        }

        $user->save();

        $this->editing = false;
        $this->success = true;
        $this->successMessage = $this->newPassword !== ''
            ? __('app.password_changed')
            : __('app.profile_updated');
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
    }

    public function render()
    {
        return view('livewire.app.profile', [
            'user' => auth()->user()->load('company'),
        ]);
    }
}
