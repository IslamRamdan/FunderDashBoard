<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Filament\Resources\PropertyResource\RelationManagers;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $activeNavigationIcon = 'heroicon-s-building-storefront';

    protected static ?string $navigationGroup = 'Funder Property';

    public function gosoldout($id)
    {
        $property = Property::find($id);
        $property->status = "sold out";
        $property->save();
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make("Property")
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->columnSpanFull()
                            ->required()
                            ->relationship('category', 'name')
                            ->prefixIcon('heroicon-o-tag'),
                        Forms\Components\TextInput::make('location_string')
                            ->columnSpanFull()
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make("Choose images")
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->columnSpanFull()
                            ->required()
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->imagePreviewHeight('250')
                            ->loadingIndicatorPosition('left')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->panelLayout('grid')
                        ,
                    ]),
                Forms\Components\Section::make("Property Price")
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->columnSpanFull()
                            ->required()
                            ->suffix('EGP')
                            ->numeric(),
                        Forms\Components\TextInput::make('property_price_total')
                            ->columnSpanFull()
                            ->required()
                            ->suffix('EGP')
                            ->numeric(),
                        Forms\Components\TextInput::make('property_price')
                            ->columnSpanFull()
                            ->required()
                            ->suffix('EGP')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('discount')
                            ->columnSpanFull()
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(255),
                    ])->columns(3),
                Forms\Components\Section::make("Property Rent")
                    ->schema([
                        Forms\Components\TextInput::make('rental_income')
                            ->columnSpanFull()
                            ->suffix('EGP')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('current_rent')
                            ->columnSpanFull()
                            ->required()
                            ->suffix('EGP')
                            ->numeric(),
                        Forms\Components\TextInput::make('percent')
                            ->columnSpanFull()
                            ->required()
                            ->suffix('%')
                            ->numeric(),
                    ])->columns(3),
                Forms\Components\DatePicker::make('funded_date')
                    ->columnSpanFull()
                ,
                Forms\Components\TextInput::make('funder_count')
                    ->columnSpanFull()
                    ->required()
                    ->numeric(),

                Forms\Components\TextInput::make('current_evaluation')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('service_charge')
                    ->columnSpanFull()
                    ->required()
                    ->numeric(),
                Forms\Components\Section::make("Estimated Annualised")
                    ->schema([
                        Forms\Components\TextInput::make('estimated_annualised_return')
                            ->columnSpanFull()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('estimated_annual_appreciation')
                            ->columnSpanFull()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('estimated_projected_gross_yield')
                            ->columnSpanFull()
                            ->required()
                            ->maxLength(255),
                    ])->columns(3),
                Forms\Components\ToggleButtons::make('status')
                    ->columnSpanFull()
                    ->options([
                        "funded" => "Funded",
                        "rented" => "Rented",
                        "sold out" => "Sold Out",

                    ])
                    ->icons([
                        "funded" => "heroicon-s-identification",
                        "rented" => "heroicon-s-identification",
                        "sold out" => "heroicon-s-identification",
                    ])
                    ->columns(3)
                    ->required()
                    ->default(null),
                Forms\Components\DateTimePicker::make('approved')
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')

                    ->searchable(),
                Tables\Columns\ImageColumn::make('images')
                    ->searchable(),
                Tables\Columns\TextColumn::make('funded_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->numeric()
                    ->suffix(' EGP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('funder_count')

                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rental_income')
                    ->suffix(' EGP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_rent')
                    ->suffix(' EGP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location_string')
                    ->searchable(),
                Tables\Columns\TextColumn::make('property_price_total')
                    ->suffix(' EGP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property_price')
                    ->suffix(' EGP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_evaluation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_annualised_return')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_annual_appreciation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimated_projected_gross_yield')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_charge')
                    ->suffix(' EGP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approved')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('sold out')
                    ->label('SOLD OUT')
                    ->button()
                    ->action(function (Property $record) {
                        // Execute the gosoldout logic here
                        $property = Property::find($record->id);
                        $property->status = "sold out";
                        $property->save();
                    })
                    ->requiresConfirmation()
                    ->color('warning')
                    ->visible(fn(Property $record) => $record->status !== 'sold out')
                    ->icon('heroicon-s-currency-pound'),
                Tables\Actions\EditAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }


}