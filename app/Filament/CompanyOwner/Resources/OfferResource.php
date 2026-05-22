<?php

namespace App\Filament\CompanyOwner\Resources;

use App\Filament\CompanyOwner\Resources\OfferResource\Pages;
use App\Filament\CompanyOwner\Resources\OfferResource\RelationManagers;
use App\Models\Offer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;



                public static function canCreate(): bool
        {
            return false; // إخفاء زر "إضافة سعر" نهائياً
        }


                public static function canDeleteAny(): bool
        {
            return false; // منع الحذف
        }




    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    // المسمى في القائمة الجانبية
    protected static ?string $navigationLabel = ' صفحة العروض';

    // المسمى عند عرض كل الرسائل
    protected static ?string $pluralModelLabel = ' العروض';

    // المسمى عند إنشاء رسالة واحدة
    protected static ?string $modelLabel = 'عرض';





    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }


    // العروض تابعة لشركة وحدة فقط
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
    }



    public static function table(Table $table): Table
    {
        return $table

            ->columns([

                // تبويب صفحة الاسعار

                // مدينة المغادرة
                Tables\Columns\TextColumn::make('route.departureCity.name')
                    ->label('من مدينة')
                    ->color('info')
                    ->icon('heroicon-m-map-pin'),

                // مدينة الوصول
                Tables\Columns\TextColumn::make('route.arrivalCity.name')
                    ->label('إلى مدينة')
                    ->color('info')
                    ->icon('heroicon-m-map-pin'),




                Tables\Columns\TextColumn::make('trip_id')
                    ->label('الرحلات المشمولة')
                    ->badge() // عشان يطلعوا بشكل باجات مثل أيام الأسبوع
                    ->color('success') // اللون الأخضر اللي طلبته
                    
                    // استخدمنا getStateUsing عشان نبني الداتا على كيفنا قبل ما تنعرض
                    ->getStateUsing(function ($record) {
                        
                        // 1. بنجيب القيمة المحفوظة بالداتا بيز
                        $rawState = $record->trip_id;

                        // 2. حماية قوية: بنحول القيمة لمصفوفة نظيفة (عشان نتفادى الخطأ اللي طلعلك)
                        $tripIds = is_string($rawState) ? json_decode($rawState, true) : $rawState;
                        if (!is_array($tripIds)) {
                            $tripIds = $tripIds ? [$tripIds] : []; // إذا كان رقم قديم بيحوله لمصفوفة، وإذا فاضي بيخليه فاضي
                        }

                        // 3. المنطق الذكي تبعك:
                        if (empty($tripIds)) {
                            // إذا المصفوفة فاضية (يعني الموظف اختار بس المسار)
                            // بنروح بنجيب "كل" الرحلات التابعة لهاد المسار
                            $trips = \App\Models\Trip::where('route_id', $record->route_id)->get();
                        } else {
                            // إذا الموظف مختار رحلات معينة، بنجيبهم هم بس
                            $trips = \App\Models\Trip::whereIn('id', $tripIds)->get();
                        }

                        // 4. بننسق كل رحلة بالشكل اللي طلبته (الرحلة رقم X | وقت الإنطلاق: Y)
                        return $trips->map(function ($trip) {
                            $time = \Carbon\Carbon::parse($trip->scheduled_time)->format('g:i A');
                            return "الرحلة رقم {$trip->id} | وقت الإنطلاق: {$time}";
                        })->toArray(); // بنرجعهم كـ Array عشان Filament يعرض كل وحدة بـ باج لحالها
                    }),




                Tables\Columns\TextColumn::make('days_of_week')
                    ->label('أيام العرض المشمولة')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        $days = $record->days_of_week; // بنجيب المصفوفة من الداتا بيز
                        
                        // إذا مافي أيام محددة
                        if (empty($days) || !is_array($days)) {
                            return null;
                        }

                        // خريطة أسماء الأيام
                        $daysNames = [
                            0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 
                            3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'
                        ];

                        $formattedDays = [];
                        foreach ($days as $item) {
                            // بنفصل النص من عند إشارة (_)
                            $parts = explode('_', $item);
                            
                            if (count($parts) == 2) {
                                $tripId = $parts[0]; // الرقم الأول هو الرحلة
                                $dayIndex = $parts[1]; // الرقم الثاني هو اليوم
                                $dayName = $daysNames[$dayIndex] ?? '';
                                
                                // التنسيق اللي طلبته بالضبط بنحطه جوا المصفوفة
                                $formattedDays[] = "الرحلة رقم {$tripId} ({$dayName})";
                            }
                        }
                        
                        // بنرجع المصفوفة الجديدة، و Filament لحاله رح يعرض كل عنصر كباج منفصل
                        return $formattedDays;
                    }),






                // السعر الحالي (الأساسي)
                // ملاحظة: إذا كان السعر بجدول المسار، استدعيه هيك: 'route.base_price'
                Tables\Columns\TextColumn::make('route.base_price') 
                    ->label('السعر الحالي')
                    ->formatStateUsing(fn ($state) => "<s>" . number_format($state) . " ل.س</s>")  // بيطلع السعر القديم مشطوب

                // حطيت ال  HTML هون لانو في عندي وسم الشطب فوق 
                    ->html()
                    ->color('gray')
                    ->weight('bold'),




            // تبويب صفحة العروض  

            Tables\Columns\TextColumn::make('offer_price')
                ->label(' السعر بعد الخصم (العرض)')
                ->formatStateUsing(fn ($state) => number_format($state) . " ل.س") 
                ->color('success')
                ->weight('bold'),



            Tables\Columns\TextColumn::make('start_date')
                ->label('تاريخ بدء العرض')
                ->date('Y/m/d')
                ->sortable(),


            Tables\Columns\TextColumn::make('end_date')
                ->label('تاريخ انتهاء العرض')
                ->date('Y/m/d')
                ->sortable(),


                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('باقي من العرض')
                    // هون بنعمل الحسبة الذكية باستخدام Carbon تبع لارافيل

                // بتخليلي الحقل يظهر في الريسلورسيس ولكن بحقل وهمي بالنسبة للداتا بيز
                ->getStateUsing(function ($record) {
                    $startDate = \Carbon\Carbon::parse($record->start_date)->startOfDay();
                    $endDate = \Carbon\Carbon::parse($record->end_date)->startOfDay();
                    $now = \Carbon\Carbon::now()->startOfDay();

                    // 1. إذا العرض لسا ما بدأ: اعرض مدة العرض كاملة
                    if ($now->isBefore($startDate)) {
                        return $startDate->diffInDays($endDate) . ' يوم (لم يبدأ)';
                    }

                    // 2. إذا انتهى العرض
                    if ($now->isAfter($endDate)) {
                        return 'انتهى العرض';
                    }

                    // 3. إذا إحنا حالياً جوا فترة العرض: احسب الباقي من اليوم للنهاية
                    $days = $now->diffInDays($endDate);

                    return $days == 0 ? 'ينتهي اليوم' : $days . ' يوم';
                })

                    // شوية لمسات جمالية عشان يطلع بشكل (باج) ملون
                    ->badge()
                    ->color(function ($record) {
                        $endDate = \Carbon\Carbon::parse($record->end_date);
                        
                        if ($endDate->isPast()) {
                            return 'danger'; // لون أحمر إذا انتهى
                        }
                        
                        if (\Carbon\Carbon::now()->diffInDays($endDate) <= 2) {
                            return 'warning'; // لون أصفر إذا باقي يومين أو أقل
                        }
                        
                        return 'success'; // لون أخضر إذا العرض شغال وباقي وقت
                    }),



            Tables\Columns\IconColumn::make('is_active')
                ->label('حالة العرض')
                ->boolean(),

                


            ])




            ->filters([

            
            // فلتر العروض المفعلة/غير المفعلة
            Tables\Filters\TernaryFilter::make('is_active')
                ->label('حالة العرض')
                ->placeholder('كل العروض')
                ->trueLabel('المفعلة فقط')
                ->falseLabel('المتوقفة فقط'),


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

            ])
            

            ->actions([


            Tables\Actions\Action::make('edit_price')
                ->label('تعديل سعر العرض')
                ->icon('heroicon-m-pencil-square')
                ->visible(fn ($livewire) => $livewire->activeTab === 'active_offers')
                ->color('success')
                ->form([
                    Forms\Components\Section::make('تفاصيل العرض الحالي')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    // لازم نستخدم $record للتأكد من وصول البيانات
                                    Forms\Components\Placeholder::make('departure_city')
                                        ->label('من مدينة')
                                        // الوصول للمدينة من خلال علاقة route
                                        ->content(fn ($record) => $record->route?->departureCity?->name ?? '-'),

                                    Forms\Components\Placeholder::make('arrival_city')
                                        ->label('إلى مدينة')
                                        ->content(fn ($record) => $record->route?->arrivalCity?->name ?? '-'),
                                    Forms\Components\Placeholder::make('start_date')
                                        ->label('بداية العرض')
                                        ->content(fn ($record) => $record->start_date ?? '-'),

                                    Forms\Components\Placeholder::make('end_date')
                                        ->label('نهاية العرض')
                                        ->content(fn ($record) => $record->end_date ?? '-'),
                                ]),
                        ]),
                        
                                    Forms\Components\TextInput::make('offer_price')
                                        ->label('سعر العرض الجديد')
                                        ->required()
                                        ->formatStateUsing(fn ($record) => number_format($record->offer_price) . ' ل.س')
                                        ->helperText('أدخل القيمة الجديدة للمسار خلال فترة العرض'),
                ])


                        // تعبئة الحقل بالسعر القديم تلقائياً عند فتح النافذة
                        ->mountUsing(fn (Forms\ComponentContainer $form, $record) => $form->fill([
                            'offer_price' => $record->offer_price,
                        ]))

                        
                        ->action(function ($record, array $data) {
                            $record->update([
                                'offer_price' => $data['offer_price'],
                            ]);


                                    \Filament\Notifications\Notification::make()
                                        ->title('تم تحديث السعر بنجاح')
                                        ->success()
                                        ->send();
                    

                }),

             Tables\Actions\DeleteAction::make(),



            ])

           



            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            // مشان تطلع احدث العروض اول شي 
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListOffers::route('/'),

        ];
    }
}
