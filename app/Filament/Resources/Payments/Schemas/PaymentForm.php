<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_id')
                    ->relationship('booking', 'id')
                    ->required(),
                Select::make('type')
                    ->options(PaymentType::class)
                    ->required(),
                TextInput::make('amount_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('application_fee_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(PaymentStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('cad'),
                TextInput::make('stripe_session_id'),
                TextInput::make('stripe_payment_intent_id'),
            ]);
    }
}
