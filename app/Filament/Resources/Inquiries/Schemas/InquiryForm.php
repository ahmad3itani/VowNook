<?php

namespace App\Filament\Resources\Inquiries\Schemas;

use App\Enums\InquiryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('wedding_id')
                    ->relationship('wedding', 'name')
                    ->required(),
                Select::make('couple_user_id')
                    ->relationship('coupleUser', 'name')
                    ->required(),
                Select::make('vendor_profile_id')
                    ->relationship('vendorProfile', 'id')
                    ->required(),
                Select::make('vendor_service_id')
                    ->relationship('vendorService', 'name'),
                DatePicker::make('event_date'),
                TextInput::make('guest_count')
                    ->numeric(),
                TextInput::make('budget_cents')
                    ->numeric(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(InquiryStatus::class)
                    ->default('requested')
                    ->required(),
                DateTimePicker::make('first_response_at'),
            ]);
    }
}
