<?php

declare(strict_types=1);

namespace App\Filament\Resources\Clients;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\RelationManagers\AppointmentsRelationManager;
use App\Filament\RelationManagers\CompaniesRelationManager;
use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\RelationManagers\PracticesRelationManager;
use App\Filament\RelationManagers\TimelineEventsRelationManager;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Contacts\Concerns\HasContactGlobalSearch;
use App\Filament\Resources\Contacts\ContactResourceSchema;
use App\Filament\Resources\Contacts\ContactResourceTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClientResource extends Resource
{
    use HasContactGlobalSearch;

    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Relazioni';

    protected static ?string $navigationLabel = 'Clienti';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clienti';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ContactResourceSchema::form($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactResourceSchema::infolist($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactResourceTable::configure($table, false)
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->clients();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getRelations(): array
    {
        return [CompaniesRelationManager::class, AppointmentsRelationManager::class, ActivitiesRelationManager::class, PracticesRelationManager::class, DocumentsRelationManager::class, NotesRelationManager::class, TimelineEventsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
