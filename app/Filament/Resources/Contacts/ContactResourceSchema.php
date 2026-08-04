<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts;

use App\Filament\Support\ItalianOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class ContactResourceSchema
{
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Scheda contatto')->tabs([
                Tab::make('Informazioni personali')->schema([
                    TextInput::make('first_name')->label('Nome')->required()->maxLength(255),
                    TextInput::make('last_name')->label('Cognome')->required()->maxLength(255),
                    DatePicker::make('birth_date')->label('Data di nascita')->maxDate(now()),
                    TextInput::make('birth_place')->label('Luogo di nascita'),
                    TextInput::make('tax_code')->label('Codice fiscale')->maxLength(16)->unique(ignoreRecord: true),
                    TextInput::make('identity_document_type')->label('Tipo documento'),
                    TextInput::make('identity_document_number')->label('Numero documento'),
                    DatePicker::make('identity_document_expires_at')->label('Scadenza documento'),
                    TextInput::make('profession')->label('Professione'),
                    TextInput::make('marital_status')->label('Stato civile'),
                    TextInput::make('children_count')->label('Numero di figli')->numeric()->minValue(0),
                    Textarea::make('residence')->label('Residenza'),
                    Textarea::make('domicile')->label('Domicilio'),
                ])->columns(2),
                Tab::make('Contatti')->schema([
                    TextInput::make('email')->label('Email')->email(),
                    TextInput::make('phone')->label('Telefono')->tel(),
                    TextInput::make('whatsapp')->label('WhatsApp')->tel(),
                    FileUpload::make('photo_path')->label('Foto')->image()->disk('local')->visibility('private')->directory('contact-photos'),
                    Select::make('preferred_communication')->label('Comunicazione preferita')->options(['phone' => 'Telefono', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'in_person' => 'Di persona']),
                    TextInput::make('contact_frequency')->label('Frequenza di contatto'),
                ])->columns(2),
                Tab::make('Informazioni commerciali')->schema([
                    DatePicker::make('first_contact_date')->label('Data primo contatto'),
                    Select::make('source')->label('Provenienza')->options(ItalianOptions::SOURCES)->searchable(),
                    Select::make('referred_by_contact_id')->label('Presentato da')->relationship('referredBy', 'last_name')->searchable()->preload(),
                    Select::make('priority')->label('Priorità')->options(ItalianOptions::PRIORITIES)->default('medium')->required(),
                    TextInput::make('potential_value')->label('Potenziale economico')->numeric()->prefix('€'),
                    TextInput::make('managed_assets')->label('Patrimonio gestito')->numeric()->prefix('€'),
                    TextInput::make('relationship_level')->label('Livello della relazione'),
                    DateTimePicker::make('last_contact_at')->label('Ultimo contatto')->seconds(false),
                    DateTimePicker::make('next_follow_up_at')->label('Prossimo follow-up')->seconds(false),
                ])->columns(2),
                Tab::make('Interessi e obiettivi')->schema([
                    Select::make('interests')->label('Interessi')->options(ItalianOptions::INTERESTS)->multiple()->searchable(),
                    Select::make('personal_goals')->label('Obiettivi personali')->options(ItalianOptions::PERSONAL_GOALS)->multiple()->searchable(),
                ]),
                Tab::make('Profilo relazionale')->schema([
                    TextInput::make('personality_style')->label('Stile o personalità percepita'),
                    TagsInput::make('hobbies')->label('Hobby'),
                    Textarea::make('relationship_notes')->label('Note relazionali')->columnSpanFull(),
                ])->columns(2),
                Tab::make('Famiglia e informazioni da ricordare')->schema([
                    Textarea::make('family_information')->label('Informazioni sulla famiglia'),
                    TagsInput::make('birthdays')->label('Compleanni'),
                    TagsInput::make('anniversaries')->label('Anniversari'),
                    Textarea::make('important_information')->label('Informazioni importanti da ricordare')->columnSpanFull(),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contatto')->schema([
                ImageEntry::make('photo_path')->label('Foto')->disk('local')->circular(),
                TextEntry::make('first_name')->label('Nome'),
                TextEntry::make('last_name')->label('Cognome'),
                TextEntry::make('status')->label('Stato')->badge()->formatStateUsing(fn ($state): string => $state?->value === 'client' ? 'Cliente' : 'Prospect'),
                TextEntry::make('priority')->label('Priorità')->badge()->formatStateUsing(fn ($state): string => ItalianOptions::PRIORITIES[$state?->value] ?? '-'),
                TextEntry::make('phone')->label('Telefono'),
                TextEntry::make('email')->label('Email'),
            ])->columns(3),
            Section::make('Informazioni personali')->schema([
                TextEntry::make('birth_date')->label('Data di nascita')->date('d/m/Y'),
                TextEntry::make('birth_place')->label('Luogo di nascita'),
                TextEntry::make('tax_code')->label('Codice fiscale'),
                TextEntry::make('profession')->label('Professione'),
                TextEntry::make('residence')->label('Residenza'),
            ])->columns(3)->collapsible(),
            Section::make('Informazioni commerciali e relazionali')->schema([
                TextEntry::make('source')->label('Provenienza')->formatStateUsing(fn ($state): string => ItalianOptions::SOURCES[$state?->value] ?? '-'),
                TextEntry::make('potential_value')->label('Potenziale')->money('EUR'),
                TextEntry::make('last_contact_at')->label('Ultimo contatto')->dateTime('d/m/Y H:i'),
                TextEntry::make('next_follow_up_at')->label('Prossimo follow-up')->dateTime('d/m/Y H:i'),
                TextEntry::make('interests')->label('Interessi')->badge()->formatStateUsing(fn (string $state): string => ItalianOptions::INTERESTS[$state] ?? $state),
                TextEntry::make('personal_goals')->label('Obiettivi')->badge()->formatStateUsing(fn (string $state): string => ItalianOptions::PERSONAL_GOALS[$state] ?? $state),
                TextEntry::make('relationship_notes')->label('Note relazionali')->columnSpanFull(),
                TextEntry::make('important_information')->label('Da ricordare')->columnSpanFull(),
            ])->columns(3)->collapsible(),
        ]);
    }
}
