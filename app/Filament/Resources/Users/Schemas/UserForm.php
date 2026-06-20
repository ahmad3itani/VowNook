<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AccountType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                // The model casts `password` as hashed, so the raw value is
                // hashed automatically on save. Only required when creating; left
                // blank on edit, the existing password is preserved.
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create'),
                DateTimePicker::make('two_factor_confirmed_at')
                    ->disabled(),
                Toggle::make('is_admin')
                    ->required(),
                TextInput::make('plan')
                    ->required()
                    ->default('free'),
                Select::make('current_wedding_id')
                    ->relationship('currentWedding', 'name'),
                Select::make('account_type')
                    ->options(AccountType::class)
                    ->default('couple')
                    ->required(),
                DateTimePicker::make('marketing_consent_at'),
                DateTimePicker::make('plan_comped_until'),
                TextInput::make('referral_code'),
                TextInput::make('referred_by')
                    ->numeric(),
                DateTimePicker::make('referral_rewarded_at'),
                TextInput::make('stripe_customer_id'),
                TextInput::make('stripe_subscription_id'),
                DateTimePicker::make('last_login_at'),
                TextInput::make('last_login_ip'),
                DateTimePicker::make('suspended_at'),
                TextInput::make('suspended_reason'),
            ]);
    }
}
