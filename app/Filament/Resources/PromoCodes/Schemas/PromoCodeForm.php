<?php

namespace App\Filament\Resources\PromoCodes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('kind')
                    ->required()
                    ->default('comp_plan'),
                TextInput::make('plan')
                    ->required()
                    ->default('premium'),
                TextInput::make('duration_days')
                    ->required()
                    ->numeric()
                    ->default(30),
                TextInput::make('max_redemptions')
                    ->numeric(),
                TextInput::make('redeemed_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('expires_at'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('note'),
            ]);
    }
}
