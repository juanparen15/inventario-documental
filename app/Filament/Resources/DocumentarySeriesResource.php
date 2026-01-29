<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentarySeriesResource\Pages;
use App\Models\DocumentarySeries;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentarySeriesResource extends Resource
{
    protected static ?string $model = DocumentarySeries::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Clasificación Documental';

    protected static ?string $modelLabel = 'Serie Documental';

    protected static ?string $pluralModelLabel = 'Series Documentales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('retention_years')
                            ->label('Años de Retención')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\Select::make('final_disposition')
                            ->label('Disposición Final')
                            ->options([
                                'CT' => 'Conservación Total',
                                'E' => 'Eliminación',
                                'S' => 'Selección',
                                'M' => 'Microfilmación',
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('retention_years')
                    ->label('Años Retención')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_disposition')
                    ->label('Disposición')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'CT' => 'Conservación Total',
                        'E' => 'Eliminación',
                        'S' => 'Selección',
                        'M' => 'Microfilmación',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('documentary_subseries_count')
                    ->label('Subseries')
                    ->counts('documentarySubseries')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('final_disposition')
                    ->label('Disposición Final')
                    ->options([
                        'CT' => 'Conservación Total',
                        'E' => 'Eliminación',
                        'S' => 'Selección',
                        'M' => 'Microfilmación',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListDocumentarySeries::route('/'),
            'create' => Pages\CreateDocumentarySeries::route('/create'),
            'edit' => Pages\EditDocumentarySeries::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
