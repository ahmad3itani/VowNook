<?php

namespace App\Filament\Resources\Weddings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WeddingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                DatePicker::make('event_date'),
                TextInput::make('timezone')
                    ->required()
                    ->default('UTC'),
                Textarea::make('settings')
                    ->columnSpanFull(),
            ]);
    }
}
