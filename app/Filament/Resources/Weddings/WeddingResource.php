<?php

namespace App\Filament\Resources\Weddings;

use App\Filament\Resources\Weddings\Pages\CreateWedding;
use App\Filament\Resources\Weddings\Pages\EditWedding;
use App\Filament\Resources\Weddings\Pages\ListWeddings;
use App\Filament\Resources\Weddings\Schemas\WeddingForm;
use App\Filament\Resources\Weddings\Tables\WeddingsTable;
use App\Models\Wedding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeddingResource extends Resource
{
    protected static ?string $model = Wedding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WeddingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WeddingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeddings::route('/'),
            'create' => CreateWedding::route('/create'),
            'edit' => EditWedding::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
