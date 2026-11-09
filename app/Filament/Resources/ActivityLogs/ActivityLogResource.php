<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'log aktivitas';

    protected static ?string $pluralModelLabel = 'log aktivitas';

    protected static ?int $navigationSort = 90;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::categoryLabel($state))
                    ->color(fn (?string $state): string => self::categoryColor($state))
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->placeholder('Sistem')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Aksi')
                    ->formatStateUsing(fn (?string $state): string => self::actionLabel($state))
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '-' : class_basename($state))
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('ID')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Kategori')
                    ->options(self::categoryOptions())
                    ->multiple(),
                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diubah',
                        'deleted' => 'Dihapus',
                    ]),
                SelectFilter::make('causer_id')
                    ->label('Pelaku')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $state): Builder => filled($state['value'] ?? null)
                        ? $query
                            ->where('causer_type', (new User)->getMorphClass())
                            ->where('causer_id', $state['value'])
                        : $query),
                Filter::make('created_at')
                    ->label('Rentang waktu')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->modalHeading(fn (Activity $record): string => self::actionLabel($record->description))
                    ->modalWidth('2xl')
                    ->schema(fn (Schema $schema): Schema => $schema->components([
                        Section::make('Ringkasan')->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('created_at')
                                    ->label('Waktu')
                                    ->dateTime('d M Y H:i:s'),
                                TextEntry::make('log_name')
                                    ->label('Kategori')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => self::categoryLabel($state))
                                    ->color(fn (?string $state): string => self::categoryColor($state)),
                                TextEntry::make('description')
                                    ->label('Aksi')
                                    ->formatStateUsing(fn (?string $state): string => self::actionLabel($state)),
                                TextEntry::make('causer.name')
                                    ->label('Pelaku')
                                    ->placeholder('Sistem'),
                                TextEntry::make('subject')
                                    ->label('Model')
                                    ->state(fn (Activity $record): string => $record->subject_type === null
                                        ? '-'
                                        : class_basename($record->subject_type).' #'.$record->subject_id),
                            ]),
                        ]),
                        Section::make('Rincian Perubahan')->schema([
                            ViewEntry::make('properties')
                                ->label('')
                                ->view('filament.infolists.activity-properties'),
                        ]),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function categoryOptions(): array
    {
        return [
            'transaksi' => 'Transaksi',
            'barang' => 'Barang',
            'kategori' => 'Kategori',
            'lokasi' => 'Lokasi',
            'audit' => 'Audit',
            'pengguna' => 'Pengguna',
            'autentikasi' => 'Autentikasi',
        ];
    }

    private static function categoryLabel(?string $state): string
    {
        return self::categoryOptions()[$state] ?? ucfirst((string) $state);
    }

    private static function categoryColor(?string $state): string
    {
        return match ($state) {
            'transaksi' => 'primary',
            'barang' => 'success',
            'kategori', 'lokasi' => 'info',
            'audit' => 'warning',
            'pengguna' => 'gray',
            'autentikasi' => 'danger',
            default => 'gray',
        };
    }

    private static function actionLabel(?string $state): string
    {
        return match ($state) {
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            null => '-',
            default => ucfirst($state),
        };
    }
}
