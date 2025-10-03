<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\ImageEntry;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ticket_name'),
                TextEntry::make('price')
                    ->money(),
                TextEntry::make('event_date')
                    ->date(),
                TextEntry::make('quantity_available')
                    ->numeric(),
                TextEntry::make('location'),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                ImageEntry::make('photo')
                    ->disk('public')
                    ->label('Foto')
                    ->placeholder('Tidak ada foto'),
                // TextEntry::make('Session')
                //     ->label('Sesi'),

            ]);
    }
}
