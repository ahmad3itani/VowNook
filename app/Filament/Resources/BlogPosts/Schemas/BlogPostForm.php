<?php

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('excerpt'),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('cover_image_path')
                    ->image(),
                Select::make('category')
                    ->options(BlogCategory::class)
                    ->default('planning_tips')
                    ->required(),
                TextInput::make('author_name')
                    ->required()
                    ->default('VowNook'),
                TextInput::make('meta_title'),
                TextInput::make('meta_description'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('published_at'),
                TextInput::make('cover_alt'),
            ]);
    }
}
