<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\TripResource\Pages;
use App\Filament\CompanyOwner\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationLabel = 'عرض الرحلات';
    protected static ?string $pluralModelLabel = ' الرحلات';


    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';





    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }




        // مشان يعرضلي فقط الرحلات التابعة لهاد المسار الي تابع لهي الشركة 
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('route', function ($query) {
                // فلترة الرحلات بناءً على شركة المسار
                $query->where('company_id', auth()->user()->company_id);
            });
    }



    


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
            // 1. المسار (من مدينة إلى مدينة)
            Tables\Columns\TextColumn::make('route')
                ->label('المسار')
                ->formatStateUsing(fn ($record) => 
                    "من " . ($record->route->departureCity->name ?? '؟') . 
                    " إلى " . ($record->route->arrivalCity->name ?? '؟')
                )
                ->description(fn ($record) => "سعر التذكرة: " . number_format($record->route->base_price) . " ل.س")

                ->searchable(query: function ($query, string $search) {
                    return $query->whereHas('route', function ($q) use ($search) {
                        $q->whereHas('departureCity', fn($city) => $city->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('arrivalCity', fn($city) => $city->where('name', 'like', "%{$search}%"));
                    });

    })
            ->icon('heroicon-m-map-pin')
            ->color('success')
            ->iconColor('primary'),



            // 2. الباص
            Tables\Columns\TextColumn::make('bus.bus_numbernnn')
                ->label('رقم الباص')
                ->description(fn ($record) => "السائق: " . $record->bus->driver_name)
                ->sortable(),



            // 3. وقت الانطلاق (تنسيق 12 ساعة مع AM/PM)
            Tables\Columns\TextColumn::make('scheduled_time')
                ->label('موعد الانطلاق')
                ->time('h:i A') // بيعرض الوقت مثلاً 09:00 AM
                ->sortable()
                ->color('primary')
                ->weight('bold'),



            // 4. أيام العمل (بتظهر كـ باجات ملونة)
            Tables\Columns\TextColumn::make('days_of_week')
                ->label('أيام الرحلة')
                ->badge()
                ->formatStateUsing(fn (int $state): string => match ($state) {
                    0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
                    4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
                    default => 'غير معروف',
                })
                ->color('success'),


            

            Tables\Columns\TextColumn::make('route.distance')
                ->label('المسافة (كم)')
                ->formatStateUsing(fn ($state) => number_format($state)) 
                ->color('gray'),


            Tables\Columns\TextColumn::make('route.estimated_time')
                ->label(' الوقت التقريبي للرحلة')
                ->color('gray'),



            Tables\Columns\TextColumn::make('route.base_price')
                ->label('سعر التذكرة ')
                ->formatStateUsing(fn ($state) => number_format($state) . " ل.س") 
                ->color('success') // بخلي السعر لونه أخضر
                ->weight('bold'),





            // 5. حالة الرحلة (تبديل فوري من الجدول)
            Tables\Columns\IconColumn::make('is_active')
                ->label('نشطة')
                ->boolean(), // فقط للعرض على حسب الموظف شو مختار


            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ إنشاء الرحلة')
                ->dateTime('Y-m-d H:i') // هون بحدد التنسيق 
                ->sortable() // مشان ترتب الشركات من الأحدث للأقدم
                ->color('success'), // لون اخضر



            ])


            


            ->filters([
                
            // فلتر حسب المسار
            Tables\Filters\SelectFilter::make('route_id')
                ->label('تصفية حسب المسار')
                ->options(function () {
                    // بنجيب المسارات وبنفلترها حسب الشركة
                    $query = \App\Models\Route::query();
                    
                    if (auth()->user()->company_id) {
                        $query->where('company_id', auth()->user()->company_id);
                    }

                    // بنرتبهم وبنطلع النص "من ... إلى ..." كقيمة للعرض
                    return $query->with(['departureCity', 'arrivalCity'])
                        ->get()
                        ->pluck('full_route_name', 'id'); 
                        // ملاحظة: إذا ما عندك attribute اسمه full_route_name، استخدم التعديل اللي تحت
                })
                // إذا ما بدك تعمل attribute بالموديل، استخدم هاي الطريقة المباشرة:
                ->options(function () {
                    $routes = \App\Models\Route::query()
                        ->when(auth()->user()->company_id, fn($q) => $q->where('company_id', auth()->user()->company_id))
                        ->get();

                    return $routes->mapWithKeys(function ($route) {
                        return [$route->id => "من " . ($route->departureCity->name ?? '') . " إلى " . ($route->arrivalCity->name ?? '')];
                    });
                })
                ->searchable()
                ->preload(),

                

            // فلترة حسب اليوم
            Tables\Filters\SelectFilter::make('day')
                    ->label('الفلترة حسب اليوم')
                    ->options([
                        '0' => 'الأحد',
                        '1' => 'الإثنين',
                        '2' => 'الثلاثاء',
                        '3' => 'الأربعاء',
                        '4' => 'الخميس',
                        '5' => 'الجمعة',
                        '6' => 'السبت',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        // التعديل هون: بنستخدم filled بدل empty عشان الصفر ما يضيع
                        if (filled($data['value'])) { 
                            return $query->where(function ($q) use ($data) {
                                $q->whereJsonContains('days_of_week', $data['value'])
                                ->orWhereJsonContains('days_of_week', (int) $data['value']);
                            });
                        }
                        return $query;
                    }),

            ])



            ->actions([
                Tables\Actions\DeleteAction::make(),

                
            ])

            
            ->bulkActions([
                
                Tables\Actions\BulkActionGroup::make([

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
            'index' => Pages\ListTrips::route('/'),
        ];
    }
}
