<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\BusResource\Pages;
use App\Filament\CompanyOwner\Resources\BusResource\RelationManagers;
use App\Models\Bus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusResource extends Resource
{



// عشان company_id ينزل تلقائياً بدون ما صاحب الشركة يختاره
    protected static bool $isScopedToTenant = true;




    protected static ?string $model = Bus::class;

    protected static ?string $navigationIcon = 'heroicon-s-truck'; 

// اسم القائمة على اليمين
    protected static ?string $navigationLabel = ' باصات الشركة';

    // عنوان الصفحة من الداخل
    protected static ?string $pluralModelLabel = 'الباصات';

    // اسم الزر عند إضافة باص جديد
    protected static ?string $modelLabel = 'باص';



    

public static function form(Form $form): Form
{
    return $form
        ->schema([

            Forms\Components\Section::make('معلومات الباص الأساسية')
                ->description('أدخل بيانات الباص التابع لشركتك .')

                ->schema([

                    Forms\Components\Select::make('route_id')
                        ->label('تحديد المسار (الخط)')
                        ->relationship(
                            name: 'route', 
                            titleAttribute: 'id', // تأكد إن هذا اسم الحقل اللي فيه اسم المسار عندك

                            // مشان صاحب الشركة يشوف بس مساراته (modifyQueryUsing)
                            modifyQueryUsing: fn (Builder $query) => $query->where('company_id', auth()->user()->company_id), 
                        )


                        // بيعرض من مدينة كذا الى مدينة كذا في القائمة
                        ->getOptionLabelFromRecordUsing(fn ($record) => 

                        // 'غير معروف مشان ما يعطيني خطأ 
                            "من مدينة " . ($record->departureCity->name ?? 'غير معروف') . 
                            " إلى مدينة " . ($record->arrivalCity->name ?? 'غير معروف')
                        )


                        ->searchable()
                        ->preload()
                        ->required()
                        ->placeholder('اختر المسار الذي سيعمل عليه هذا الباص'),




                    Forms\Components\Grid::make(2) // تقسيم لعمودين
                        ->schema([
                            // 1. رقم اللوحة
                            Forms\Components\TextInput::make('bus_number')
                                ->label('رقم اللوحة  ')
                                ->required()
                                 ->placeholder('أدخل رقم لوحة الباص')
                                ->unique(ignoreRecord: true),


                            //   رقم الباص  
                            Forms\Components\TextInput::make('bus_numbernnn')
                                ->label('رقم الباص  ')
                                ->required()
                                ->placeholder(''),


                            // 3. عدد المقاعد
                            Forms\Components\TextInput::make('total_seats')
                                ->label('عدد المقاعد')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                                


                            // 4. موديل الباص
                            Forms\Components\TextInput::make('bus_model')
                                ->label('موديل الباص')
                                ->required(),
                                
                                

                            // 2. اسم السائق
                            Forms\Components\TextInput::make('driver_name')
                                ->label('اسم السائق')
                                ->required()
                                ->placeholder('الاسم الكامل للسائق'),


                            Forms\Components\TextInput::make('driver_phone')
                                ->label('رقم هاتف السائق')
                                ->numeric() // 1. الخاصية اللي تمنع الأحرف والرموز نهائياً
                                ->placeholder('أدخل رقم هاتف السائق')
                                ->extraInputAttributes([
        'style' => 'direction: ltr !important; text-align: left;', // 2. يخلي الأرقام تظهر بالإنجليزية حصراً
    ])
                                ->maxLength(15), // احتياطاً عشان ما يدخل رقم طويل جداً


                            // 5. حالة الباص
                            Forms\Components\Select::make('status')
                                ->label('حالة الباص')
                                ->options([
                                    'active' => 'يعمل (متاح)',
                                    'maintenance' => 'في الصيانة',
                                    'inactive' => 'خارج الخدمة',

                                ])


                                ->default('active')
                                ->required(),

                        ]),

                ]),

        ]);


}




    // الباصات تابعة لشركة وحدة فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
    }






    public static function table(Table $table): Table
    {
        return $table

            ->columns([

            Tables\Columns\TextColumn::make('bus_numbernnn')
                ->label('رقم الباص')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->color('success')
                ->copyable(), // ميزة حلوة ينسخ الرقم بكبسة وحدة

// رقم الباص بخط عريض
            Tables\Columns\TextColumn::make('bus_number')
                ->label('رقم اللوحة')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->copyable(), // ميزة حلوة ينسخ الرقم بكبسة وحدة


            // اسم السائق
            Tables\Columns\TextColumn::make('driver_name')
                ->label('السائق')
                ->icon('heroicon-m-user')
                ->color('success')
                ->searchable(),


            // تعديل عمود هاتف السائق ليظهر بالإنجليزية وبدون فواصل
            Tables\Columns\TextColumn::make('driver_phone')
                ->label('هاتف السائق')
                ->searchable()
                // الحل السحري: بنجبر النص يكون من اليسار لليمين وبدون تنسيق أرقام (numeric)
                ->extraAttributes([
                    'style' => 'direction: ltr !important; text-align: right;', 
                ])
                ->copyable() //  : بصير تقدر تضغط على الرقم عشان ينسخه فوراً
                ->color('success'),

                

            // عدد المقاعد مع أيقونة صغيرة
            Tables\Columns\TextColumn::make('total_seats')
                ->label('المقاعد')
                ->color('warning')
                ->icon('heroicon-m-users')
                ->sortable(),


            // العمود الجديد للمسار
            Tables\Columns\TextColumn::make('route')
                ->label('المسار (الخط)')
                ->formatStateUsing(function ($record) {
                    // منجيب بيانات المسار عن طريق العلاقة
                    $route = $record->route;
                    
                    if (!$route) return 'غير محدد';

                    // بنادي أسماء المدن من علاقات مودل المسار
                    return "من " . ($route->departureCity->name ?? '؟') . 
                           " إلى " . ($route->arrivalCity->name ?? '؟');
                })
                ->color('info') //  لون أزرق خفيف  
                ->icon('heroicon-m-map-pin'), // أيقونة لوكيشن صغيرة



            // الحالة ملونة (اخضر، اصفر، احمر)
            Tables\Columns\TextColumn::make('status')
                ->label('الحالة')
                ->badge() // بيعملها مثل كبسولة ملونة
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',      // أخضر
                    'maintenance' => 'warning',  // أصفر
                    'inactive' => 'danger',     // أحمر
                    default => 'gray',
                })


                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'active' => 'يعمل',
                    'maintenance' => 'صيانة',
                    'inactive' => 'متوقف',
                    default => $state,
                }),
        ])





            ->filters([

                // فلتر سريع حسب الحالة
            Tables\Filters\SelectFilter::make('status')
                ->label('تصفية حسب الحالة')
                ->options([
                    'active' => 'يعمل',
                    'maintenance' => 'صيانة',
                    'inactive' => 'متوقف',
                ]),


            ])




            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // صاحب الشركة مسموح له يحذف باصه
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
            'index' => Pages\ListBuses::route('/'),
            'create' => Pages\CreateBus::route('/create'),
            'edit' => Pages\EditBus::route('/{record}/edit'),
        ];
    }
}
