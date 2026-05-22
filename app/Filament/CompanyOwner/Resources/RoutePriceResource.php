<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\RoutePriceResource\Pages;
use App\Filament\CompanyOwner\Resources\RoutePriceResource\RelationManagers;
use App\Models\RoutePrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class RoutePriceResource extends Resource
{
    protected static ?string $model = \App\Models\Route::class;


            public static function canCreate(): bool
        {
            return false; // إخفاء زر "إضافة سعر" نهائياً
        }


                public static function canDeleteAny(): bool
        {
            return false; // منع الحذف
        }





    
    protected static ?string $navigationLabel = ' صفحة الأسعار';

    // المسمى عند عرض كل الرسائل
    protected static ?string $pluralModelLabel = ' أسعار المسارات';

    // المسمى عند إنشاء رسالة واحدة
    protected static ?string $modelLabel = 'عرض';



    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form

    {


        return $form
            ->schema([

 // ما بنحتاج نختار المسار لأنه إحنا أصلاً عم نعدل مسار محدد ضغطنا عليه
            Forms\Components\Section::make('تفاصيل المسار')

                ->schema([
                    
        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),


                    // للعرض فقط
                    Forms\Components\TextInput::make('departure_display')
                        ->label('من مدينة')
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record->departureCity->name))
                        ->disabled(),

                    Forms\Components\TextInput::make('arrival_display')
                        ->label('إلى مدينة')
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record->arrivalCity->name))
                        ->disabled(),

                ])->columns(2),



            Forms\Components\TextInput::make('base_price')
                ->label('حدد السعر الجديد')
                
                ->formatStateUsing(fn ($state) => number_format($state) . " ل.س") 
                ->required()
                ->columnSpanFull(),

                

            ]);


    }






    public static function getEloquentQuery(): Builder
{
    // بنجيب المسارات اللي تابعة لشركة المستخدم الحالي فقط
    return parent::getEloquentQuery()->where('company_id', auth()->user()->company->id);
}









    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // تبويب صفحة الاسعار

                Tables\Columns\TextColumn::make('departureCity.name')
                ->label('من مدينة')
                ->color('info') // لون أزرق
                ->icon('heroicon-m-map-pin')
                ->sortable(),


            Tables\Columns\TextColumn::make('arrivalCity.name')
                ->label('إلى مدينة')
                ->color('info') // لون أزرق
                ->icon('heroicon-m-map-pin')
                ->sortable(),

                

            Tables\Columns\TextColumn::make('base_price')
                ->label('السعر الحالي')
                ->formatStateUsing(fn ($state) => number_format($state) . " ل.س") 
                ->color('success') // بخلي السعر لونه أخضر
                ->weight('bold'),


            Tables\Columns\TextColumn::make('price_updated_at')
                ->label('تاريخ آخر تعديل للسعر')
                ->dateTime('Y/m/d H:i')
                ->sortable()
                ->color('gray'),
            ])



            ->filters([
                //
            ])




        ->actions([



            // 2. زر تعديل السعر الأساسي (يظهر في التبويبات الأخرى)
            Tables\Actions\EditAction::make('edit_base_price') // اعطيتو اسم فريد
                ->label('تعديل السعر')
                ->modalHeading('تعديل سعر المسار')
                

                
            ->action(function ($record, array $data) {
                $record->update([
                    'base_price' => $data['base_price'],
                    'price_updated_at' => now(), // تحديث الوقت فوراً
                ]);

                \Filament\Notifications\Notification::make()
                ->title('تم تحديث السعر بنجاح')
                ->success()
                ->send();


            })

        ])

        
        

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoutePrices::route('/'),
        ];
    }
}
