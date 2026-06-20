<?php

namespace App\Filament\Resources\VendorProfiles\Schemas;

use App\Enums\VendorCategory;
use App\Enums\VendorProfileStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('business_name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('category')
                    ->options(VendorCategory::class)
                    ->required(),
                TextInput::make('tagline'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('logo_path'),
                TextInput::make('cover_path'),
                TextInput::make('city'),
                TextInput::make('region'),
                TextInput::make('country'),
                TextInput::make('service_area'),
                TextInput::make('base_price_cents')
                    ->numeric(),
                TextInput::make('price_unit'),
                TextInput::make('website')
                    ->url(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                Textarea::make('socials')
                    ->columnSpanFull(),
                TextInput::make('rating_avg')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('rating_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(VendorProfileStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('stripe_account_id'),
                Toggle::make('is_accepting_bookings')
                    ->required(),
                TextInput::make('response_hours')
                    ->numeric(),
                TextInput::make('response_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('stripe_charges_enabled')
                    ->required(),
                Toggle::make('stripe_details_submitted')
                    ->required(),
                Toggle::make('is_founding')
                    ->required(),
                DateTimePicker::make('featured_until'),
                TextInput::make('video_url')
                    ->url(),
                TextInput::make('brochure_path'),
                DateTimePicker::make('verified_at'),
                DateTimePicker::make('agreement_accepted_at'),
            ]);
    }
}
