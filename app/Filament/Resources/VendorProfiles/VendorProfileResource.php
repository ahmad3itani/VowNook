<?php

namespace App\Filament\Resources\VendorProfiles;

use App\Filament\Resources\VendorProfiles\Pages\CreateVendorProfile;
use App\Filament\Resources\VendorProfiles\Pages\EditVendorProfile;
use App\Filament\Resources\VendorProfiles\Pages\ListVendorProfiles;
use App\Filament\Resources\VendorProfiles\Schemas\VendorProfileForm;
use App\Filament\Resources\VendorProfiles\Tables\VendorProfilesTable;
use App\Models\VendorProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorProfileResource extends Resource
{
    protected static ?string $model = VendorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return VendorProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorProfilesTable::configure($table);
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
            'index' => ListVendorProfiles::route('/'),
            'create' => CreateVendorProfile::route('/create'),
            'edit' => EditVendorProfile::route('/{record}/edit'),
        ];
    }
}
