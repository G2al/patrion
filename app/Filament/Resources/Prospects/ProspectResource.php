<?php

declare(strict_types=1);

namespace App\Filament\Resources\Prospects;

use App\Actions\ConvertProspectToClient;
use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\RelationManagers\AppointmentsRelationManager;
use App\Filament\RelationManagers\CompaniesRelationManager;
use App\Filament\RelationManagers\DocumentsRelationManager;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\RelationManagers\PracticesRelationManager;
use App\Filament\RelationManagers\TimelineEventsRelationManager;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Contacts\Concerns\HasContactGlobalSearch;
use App\Filament\Resources\Contacts\ContactResourceSchema;
use App\Filament\Resources\Contacts\ContactResourceTable;
use App\Filament\Resources\Prospects\Pages\CreateProspect;
use App\Filament\Resources\Prospects\Pages\EditProspect;
use App\Filament\Resources\Prospects\Pages\ListProspects;
use App\Filament\Resources\Prospects\Pages\ViewProspect;
use App\Models\Contact;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProspectResource extends Resource
{
    use HasContactGlobalSearch;

    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Relazioni';

    protected static ?string $navigationLabel = 'Prospect';

    protected static ?string $modelLabel = 'prospect';

    protected static ?string $pluralModelLabel = 'prospect';

    protected static ?int $navigationSort = 20;

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
        return ContactResourceTable::configure($table, true)
            ->recordActions([ViewAction::make(), EditAction::make(), static::convertAction()]);
    }

    public static function convertAction(): Action
    {
        return Action::make('convert_to_client')
            ->label('Converti in cliente')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Convertire il prospect in cliente?')
            ->action(function (Contact $record) {
                $contact = app(ConvertProspectToClient::class)->handle($record, auth()->id());
                Notification::make()->success()->title('Prospect convertito in cliente')->send();

                return redirect(ClientResource::getUrl('view', ['record' => $contact]));
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->prospects();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->where('next_follow_up_at', '<', now())->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getRelations(): array
    {
        return [CompaniesRelationManager::class, AppointmentsRelationManager::class, ActivitiesRelationManager::class, PracticesRelationManager::class, DocumentsRelationManager::class, NotesRelationManager::class, TimelineEventsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProspects::route('/'),
            'create' => CreateProspect::route('/create'),
            'view' => ViewProspect::route('/{record}'),
            'edit' => EditProspect::route('/{record}/edit'),
        ];
    }
}
