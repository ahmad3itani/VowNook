<?php

namespace App\Filament\Resources\VendorProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('business_name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable(),
                TextColumn::make('tagline')
                    ->searchable(),
                TextColumn::make('logo_path')
                    ->searchable(),
                TextColumn::make('cover_path')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('region')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('service_area')
                    ->searchable(),
                TextColumn::make('base_price_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_unit')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('rating_avg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rating_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('stripe_account_id')
                    ->searchable(),
                IconColumn::make('is_accepting_bookings')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('response_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('response_count')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('stripe_charges_enabled')
                    ->boolean(),
                IconColumn::make('stripe_details_submitted')
                    ->boolean(),
                IconColumn::make('is_founding')
                    ->boolean(),
                TextColumn::make('featured_until')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('video_url')
                    ->searchable(),
                TextColumn::make('brochure_path')
                    ->searchable(),
                TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('agreement_accepted_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
