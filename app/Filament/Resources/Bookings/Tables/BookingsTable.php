<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Support\Payments\StripeService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inquiry.id')
                    ->searchable(),
                TextColumn::make('offer.id')
                    ->searchable(),
                TextColumn::make('wedding.name')
                    ->searchable(),
                TextColumn::make('vendorProfile.id')
                    ->searchable(),
                TextColumn::make('vendor.name')
                    ->searchable(),
                TextColumn::make('total_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deposit_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('platform_fee_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('stripe_payment_intent_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refund this booking?')
                    ->modalDescription('Fully refunds the couple, reverses the vendor payout and returns the platform fee. This cannot be undone.')
                    ->visible(fn (\App\Models\Booking $record): bool => in_array($record->status, [
                        BookingStatus::DepositPaid,
                        BookingStatus::PaidInFull,
                        BookingStatus::Completed,
                    ], true))
                    ->action(function (\App\Models\Booking $record): void {
                        $stripe = app(StripeService::class);

                        if (! $stripe->isConfigured()) {
                            Notification::make()->title('Stripe is not configured')->danger()->send();

                            return;
                        }

                        try {
                            $cents = $stripe->refundBooking($record);
                            Notification::make()
                                ->title('Refund issued')
                                ->body('$'.number_format($cents / 100, 2).' refunded — the booking cancels once Stripe confirms.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
