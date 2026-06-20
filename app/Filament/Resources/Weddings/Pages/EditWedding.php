<?php

namespace App\Filament\Resources\Weddings\Pages;

use App\Filament\Resources\Weddings\WeddingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWedding extends EditRecord
{
    protected static string $resource = WeddingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
