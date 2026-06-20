<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inquiry_id')
                    ->relationship('inquiry', 'id')
                    ->required(),
                Select::make('offer_id')
                    ->relationship('offer', 'id')
                    ->required(),
                Select::make('wedding_id')
                    ->relationship('wedding', 'name')
                    ->required(),
                Select::make('vendor_profile_id')
                    ->relationship('vendorProfile', 'id')
                    ->required(),
                Select::make('vendor_id')
                    ->relationship('vendor', 'name'),
                TextInput::make('total_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('deposit_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('platform_fee_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(BookingStatus::class)
                    ->default('pending_payment')
                    ->required(),
                TextInput::make('stripe_payment_intent_id'),
            ]);
    }
}
