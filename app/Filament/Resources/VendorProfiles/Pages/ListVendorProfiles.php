<?php

namespace App\Filament\Resources\VendorProfiles\Pages;

use App\Filament\Resources\VendorProfiles\VendorProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorProfiles extends ListRecords
{
    protected static string $resource = VendorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
