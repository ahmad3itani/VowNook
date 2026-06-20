<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicketStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('subject')
                    ->required(),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('category')
                    ->required()
                    ->default('general'),
                Select::make('status')
                    ->options(SupportTicketStatus::class)
                    ->default('open')
                    ->required(),
                TextInput::make('source')
                    ->required()
                    ->default('contact'),
                TextInput::make('assigned_to')
                    ->numeric(),
                DateTimePicker::make('last_reply_at'),
                DateTimePicker::make('closed_at'),
            ]);
    }
}
