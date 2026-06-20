<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('two_factor_confirmed_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_admin')
                    ->boolean(),
                TextColumn::make('plan')
                    ->searchable(),
                TextColumn::make('currentWedding.name')
                    ->searchable(),
                TextColumn::make('account_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('marketing_consent_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('plan_comped_until')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('referral_code')
                    ->searchable(),
                TextColumn::make('referred_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('referral_rewarded_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('stripe_customer_id')
                    ->searchable(),
                TextColumn::make('stripe_subscription_id')
                    ->searchable(),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_login_ip')
                    ->searchable(),
                TextColumn::make('suspended_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('suspended_reason')
                    ->searchable(),
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
