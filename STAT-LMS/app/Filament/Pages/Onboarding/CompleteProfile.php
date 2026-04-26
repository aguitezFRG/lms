<?php

namespace App\Filament\Pages\Onboarding;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CompleteProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.onboarding.complete-profile';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $slug = 'onboarding';

    protected static bool $shouldRegisterNavigation = false;

    public int $step = 1;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, [
            \App\Enums\UserRole::FACULTY,
            \App\Enums\UserRole::STUDENT,
        ]);
    }

    public function mount(): void
    {
        $user = Auth::user();

        if ($user?->is_profile_complete) {
            $this->redirect('/app', navigate: false);

            return;
        }

        if ($user) {
            if ($user->f_name || $user->l_name) {
                $this->data['f_name'] = $user->f_name ?? '';
                $this->data['m_name'] = $user->m_name ?? '';
                $this->data['l_name'] = $user->l_name ?? '';
            } else {
                $parts = explode(' ', $user->name ?? '', 3);
                $this->data['f_name'] = $parts[0] ?? '';
                $this->data['m_name'] = count($parts) === 3 ? $parts[1] : '';
                $this->data['l_name'] = count($parts) >= 2 ? end($parts) : '';
            }
            $this->data['std_number'] = '';
        }
    }

    protected function getForms(): array
    {
        return ['nameForm', 'studentForm'];
    }

    public function nameForm(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('f_name')
                    ->label('First Name')
                    ->required()
                    ->placeholder('Juan')
                    ->maxLength(100)
                    ->autocomplete('given-name'),

                TextInput::make('m_name')
                    ->label('Middle Name')
                    ->maxLength(100)
                    ->placeholder('Reyes')
                    ->autocomplete('additional-name')
                    ->hint('Optional'),

                TextInput::make('l_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Dela Cruz')
                    ->autocomplete('family-name'),
            ])
            ->statePath('data');
    }

    public function studentForm(Schema $form): Schema
    {
        $userId = Auth::id();

        return $form
            ->schema([
                TextInput::make('std_number')
                    ->label('Student Number')
                    ->placeholder('2020-12345')
                    ->rules(['nullable', "regex:/^\d{4}-\d{5}$/", "unique:users,std_number,{$userId},id"])
                    ->mask('9999-99999')
                    ->autocomplete('off')
                    ->helperText('Format: 2020-12345. Optional, but must be unique if provided.'),
            ])
            ->statePath('data');
    }

    public function nextStep(): void
    {
        $this->nameForm->validate();
        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/app/login', navigate: false);
    }

    public function submit(): void
    {
        $this->studentForm->validate();

        $user = Auth::user();
        $user->f_name = trim($this->data['f_name']);
        $user->m_name = trim($this->data['m_name'] ?? '') ?: null;
        $user->l_name = trim($this->data['l_name']);
        $user->name = trim(implode(' ', array_filter([
            $this->data['f_name'],
            $this->data['m_name'] ?? '',
            $this->data['l_name'],
        ])));
        $user->std_number = $this->data['std_number'] ?: null;
        $user->is_profile_complete = true;
        $user->save();

        session()->regenerate();
        $this->redirect('/app', navigate: false);
    }
}
