<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticket_name')
                    ->label('Nama Tiket')
                    ->required()
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->step(1000),

                DatePicker::make('event_date')
                    ->label('Tanggal Event')
                    ->required()
                    ->minDate(now()->addDay()), // Minimal besok

                TextInput::make('location')
                    ->label('Lokasi')
                    ->required()
                    ->maxLength(255),

                TextInput::make('quantity_available')
                    ->label('Kuantitas Tersedia')
                    ->required()
                    ->numeric()
                    ->minValue(1),

                TextInput::make('quantity_sold')
                    ->label('Kuantitas Terjual')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'completed' => 'Selesai'
                    ])
                    ->default('active')
                    ->required(),

                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull()
                    ->rows(3),

                FileUpload::make('photo')
                    ->disk('public')
                    ->directory('tickets')
                    ->label('Nama File Gambar')
                    ->nullable(),
                Select::make('Session')
                    ->label('Sesi')
                    ->options([
                        'Pagi-Siang' => 'Pagi-Siang',
                        'Siang-Sore' => 'Siang-Sore',
                        'Malam' => 'Malam',
                    ])
                    ->nullable(),

                // Alternatif file upload jika ingin upload gambar
                // FileUpload::make('image')
                //     ->label('Upload Gambar')
                //     ->image()
                //     ->directory('tickets')
                //     ->visibility('public'),
            ]);
    }
}
