<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Filament\Resources\CityResource\RelationManagers;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CityResource extends Resource
{
    protected static ?string $model = City::class;



        protected static ?string $navigationIcon = 'heroicon-o-map-pin'; // أيقونة دبوس الخريطة
        protected static ?string $navigationLabel = 'إدارة المدن/المحافظات';
        protected static ?string $pluralModelLabel = 'المدن';
        protected static ?string $modelLabel = 'مدينة';
        protected static ?string $navigationGroup = 'الإعدادات العامة'; // حطها بجروب لحالها عشان الترتيب







    public static function form(Form $form): Form
    {
        return $form


            ->schema([

              Forms\Components\Card::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المدينة/المحافظة')
                    ->unique(ignoreRecord: true) // عشان ما يتكرر الاسم
                    ->required(),

                
                Forms\Components\Toggle::make('is_active')
                    ->label('حالة المدينة (نشطة)')
                    ->default(true),
            ])


            ]);
    }







    public static function table(Table $table): Table
    {
        return $table

            ->columns([

            Tables\Columns\TextColumn::make('name')->label('اسم المدينة')->searchable()
            ->color('info'), // لون أزرق

            Tables\Columns\IconColumn::make('is_active')

                ->label('نشطة')
                ->boolean(), // بيظهر إشارة صح أو خطأ
                

            Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإضافة')->dateTime('Y-m-d')
            ->color('success'), // لون اخضر
            
                    ])




                    
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
