<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\OfferResource\Pages;
use App\Filament\Staff\Resources\OfferResource\RelationManagers;
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



    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    // المسمى في القائمة الجانبية
    protected static ?string $navigationLabel = ' إضافة عروض';

    // المسمى عند عرض كل الرسائل
    protected static ?string $pluralModelLabel = ' العروض';

    // المسمى عند إنشاء رسالة واحدة
    protected static ?string $modelLabel = 'عرض';






    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            Forms\Components\Placeholder::make('current_day_display')
                    ->label('تاريخ اليوم الحالي')
                    ->content(now()->translatedFormat('l, d F Y')) // بيعرض مثلاً: الأحد، 19 أبريل 2026
                    ->extraAttributes(['class' => 'text-primary-600 font-bold']), // تلوين التاريخ ليميزه الموظف

                    
        Forms\Components\Hidden::make('company_id')
            ->default(auth()->user()->company_id),




            Forms\Components\Section::make('معلومات العروض الأساسية')
                ->description('أدخل بيانات العرض التابع لشركتك .')

                ->schema([

                    Forms\Components\Select::make('route_id')
                        ->label('تحديد المسار (الخط)')
                        ->relationship(
                            name: 'route', 
                            titleAttribute: 'id', // تأكد إن هذا اسم الحقل اللي فيه اسم المسار عندك

                            // مشان  الموظف يشوف بس مسارات الشركة التابع الها (modifyQueryUsing)
                            modifyQueryUsing: fn (Builder $query) => $query->where('company_id', auth()->user()->company_id), 
                        )


                        // بيعرض من مدينة كذا الى مدينة كذا في القائمة
                        ->getOptionLabelFromRecordUsing(fn ($record) => 

                        // 'غير معروف مشان ما يعطيني خطأ 
                            "من مدينة " . ($record->departureCity->name ?? 'غير معروف') . 
                            " إلى مدينة " . ($record->arrivalCity->name ?? 'غير معروف')
                        )


                        ->preload()
                        // هي مشان لون الخط
                        ->native(false)
                        ->placeholder('اختر المسار الذي ستعمل عليه العرض ')

                        // خليت خانة اختيار المسار "تراقب" التغيير
                        ->live()
                        ->required(),
                        

                        
            // Placeholder بيعطيني نص عادي بدون حدود 
            // 2. حقل السعر الأساسي (بدون حدود + مع فواصل)
            Forms\Components\Placeholder::make('base_price')
                ->label('السعر الأساسي لهذا المسار')
                ->content(function (Forms\Get $get) {
                    // بنجيب قيمة المسار اللي اختاره الموظف فوراً
                    $routeId = $get('route_id'); 

                    // إذا مو مختار مسار، بنعرض صفر
                    if (! $routeId) {
                        return '0 ل.س';
                    }

                    // ببحث عن المسار وبجيب سعره
                    $price = \App\Models\Route::find($routeId)?->base_price ?? 0;

                    // تنسيق الرقم مع الفواصل
                    return number_format($price) . ' ل.س';
                })
                ->dehydrated(false), // ما بنخزنه بجدول العروض لأنه موجود بالمسارات   
                


        

                    // 2. اختيار الرحلة
                Forms\Components\Select::make('trip_id')
                    ->label('تحديد الرحلة (اختياري)')
                    
                // --- الإضافة الأولى: السماح باختيار أكثر من رحلة ---
                    ->multiple() 
                    
                    // --- التعديل الأساسي: استخدمنا options بدل relationship لأننا بنخزن مصفوفة JSON ---
                    ->options(function (Forms\Get $get) {
                        $routeId = $get('route_id'); // بنجيب المسار اللي اختاره الموظف (كودك الأصلي)
                        
                        // إذا مو مختار مسار، بنرجع قائمة فاضية
                        if (! $routeId) {
                            return [];
                        }

                        // بنجيب الرحلات وبنفلتره)
                        return \App\Models\Trip::where('company_id', auth()->user()->company_id)
                            ->where('route_id', $routeId)
                            ->get()
                            ->mapWithKeys(function ($record) {
                                // التعديل الثالث : تنسيق الوقت ليظهر بشكل أنيق
                                // هي مكتبةCarbon
                                $time = \Carbon\Carbon::parse($record->scheduled_time)->format('g:i A');
                                return [$record->id => "الرحلة رقم {$record->id} | وقت الإنطلاق: {$time}"];
                            });
                    })

                        ->preload()
                        // هي مشان لون الخط
                        ->native(false)
                        ->placeholder('اختر الرحلة (أو اتركه فارغاً لتطبيق العرض على كامل رحلات هذا المسار)')

                      // بيراقب التغيير عشان يجيب بيانات السائق فوراً
                        ->live()



                        // -------------------------------------------------------------------------
                        // --- الإضافة الجديدة (والوحيدة) المطلوبة لربط الأيام بالرحلة: ---
                        // -------------------------------------------------------------------------
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $tripIds = is_array($state) ? $state : (is_string($state) ? json_decode($state, true) : []);
                            
                            if (empty($tripIds)) {
                                $routeId = $get('route_id');
                                $trips = $routeId ? \App\Models\Trip::where('route_id', $routeId)->get() : [];
                            } else {
                                $trips = \App\Models\Trip::whereIn('id', $tripIds)->get();
                            }

                            $allDays = [];
                            foreach ($trips as $trip) {
                                // بنجيب الأيام من جدول الرحلات
                                $days = $trip->days_of_week ?? []; 
                                if (is_string($days)) {
                                    $days = json_decode($days, true) ?? [];
                                }
                                foreach ($days as $day) {
                                    // بنجهزها بصيغة (رقم الرحلة_رقم اليوم)
                                    $allDays[] = "{$trip->id}_{$day}";
                                }
                            }
                            // بنبعتها لحقل days_of_week عشان يعرضها فوراً
                            $set('days_of_week', $allDays); 
                        })
                        // -------------------------------------------------------------------------



                        // اذالموظف حدد فقط مسار بدون رحلات : حقل الرحلات بالداتا بيز بصير null يعني طبق على رحلات المسار كلو
                        ->dehydrateStateUsing(function ($state, Forms\Get $get) {
                                // إذا الموظف ما اختار رحلات (المصفوفة فاضية)
                                if (empty($state)) {
                                    $routeId = $get('route_id'); // بنجيب المسار المختار
                                    
                                    if ($routeId) {
                                        // بنروح على جدول الرحلات، بنجيب كل الـ IDs التابعة لهاد المسار وبنرجعها كمصفوفة
                                        return \App\Models\Trip::where('route_id', $routeId)
                                            ->pluck('id')
                                            ->map(fn($id) => (string) $id) // بنحولهم لنصوص عشان الـ JSON
                                            ->toArray();
                                    }
                                    return null;
                                }

                                
                                // إذا الموظف مختار رحلات معينة، بنحفظهم مثل ما هم
                                return $state;
                            })
                        // ممكن يكون فاضي
                        ->nullable(),




            // هاد الحقل بتجيه بيانات ايام الرحلات من جدول الرحلات لهون
            Forms\Components\Select::make('days_of_week')
                ->label('أيام العرض المشمولة')
                ->multiple() // بيسمح بحذف واختيار أيام متعددة
                ->options(function (Forms\Get $get) {
                    // 1. بنجيب الرحلات المختارة
                    $tripIds = $get('trip_id') ?? [];
                    
                    // 2. تأكيد إنها مصفوفة (عشان نتجنب أي خطأ برمي)
                    $tripIds = is_array($tripIds) ? $tripIds : (is_string($tripIds) ? json_decode($tripIds, true) : []);

                    // 3. بنجيب بيانات الرحلات من الداتا بيز (سواء رحلات محددة أو كل رحلات المسار)
                    if (empty($tripIds)) {
                        $routeId = $get('route_id');
                        $trips = $routeId ? \App\Models\Trip::where('route_id', $routeId)->get() : [];
                    } else {
                        $trips = \App\Models\Trip::whereIn('id', $tripIds)->get();
                    }

                    // 4. خريطة أسماء الأيام
                    $daysNames = [
                        0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 
                        3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'
                    ];

                    $options = [];
                    foreach ($trips as $trip) {
                        // بنجيب أيام عمل الرحلة الأصلية
                        $tripDays = $trip->days_of_week ?? [];
                        if (is_string($tripDays)) {
                            $tripDays = json_decode($tripDays, true) ?? [];
                        }

                        foreach ($tripDays as $day) {
                            // المفتاح (Key) هو (رقم الرحلة_رقم اليوم) والاسم (Label) هو (الرحلة - اليوم)
                            $options["{$trip->id}_{$day}"] = "الرحلة رقم {$trip->id} - " . ($daysNames[$day] ?? 'يوم غير معروف');
                        }
                    }
                    return $options;
                })
                ->required()
                ->live() // عشان يتحدث فوراً لما نغير الرحلات

                ->placeholder('سيتم تعبئة الأيام تلقائياً عند اختيار الرحلة'),





            Forms\Components\TextInput::make('offer_price')
                ->label('سعر العرض')
                ->numeric()
                ->required()
                ->prefix('ل.س')
                //بشيل الـ .00 من المربع بالفورم
                ->formatStateUsing(fn ($state) => $state ? (int) $state : null) 
                ->dehydrateStateUsing(fn ($state) => (int) $state),       




            Forms\Components\DatePicker::make('start_date')
                ->label('تاريخ بدء العرض')
                ->required()
                ->native(false)
                ->default(now())
                // 1. يمنع اختيار أيام قديمة من التقويم
                ->minDate(now()->startOfDay()) 
                // 2. تفعيل رسالة الخطأ إذا الموظف عدل التاريخ يدوياً لشي قديم
                ->rules(['after_or_equal:today'])
                ->validationMessages([
                    'after_or_equal' => 'يجب ان يكون تاريخ بداية العرض كتاريخ اليوم او اكبر',
                ]),



            Forms\Components\DatePicker::make('end_date')
                ->label('تاريخ انتهاء العرض')
                ->required()
                ->native(false)
                // التأكد إنه تاريخ النهاية مش قبل تاريخ البداية
                ->minDate(fn (Forms\Get $get) => $get('start_date') ?: now())
                ->rules([
                    'after_or_equal:today',
                    fn (Forms\Get $get) => 'after_or_equal:start_date'
                ])
                ->validationMessages([
                    'after_or_equal' => 'يجب ان يكون تاريخ نهاية العرض كتاريخ اليوم او أكبر من تاريخ البدء',
                ]),


            Forms\Components\Toggle::make('is_active')
                    ->label('تفعيل العرض')
                    ->default(true),


                        ])

                        
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
                
                Tables\Columns\TextColumn::make('route')
                ->label(' المسار')
                ->formatStateUsing(fn ($record) => "من {$record->route->departureCity->name} إلى {$record->route->arrivalCity->name}")

                ->searchable(query: function (Builder $query, string $search): Builder {
        return $query->whereHas('route.departureCity', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                     ->orWhereHas('route.arrivalCity', fn ($q) => $q->where('name', 'like', "%{$search}%"));
    })
                ->icon('heroicon-m-map-pin')
                ->color('success')
                ->iconColor('primary'),





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






            Tables\Columns\TextColumn::make('route.base_price')
                ->label('السعر قبل الخصم (الأساسي)')

                //  number_format($state) إضافة الفواصل تلقائياً كل 3 أرقام.
                // <s>...</s>:بتعمل السطر المشطوب
                ->formatStateUsing(fn ($state) => "<s>" . number_format($state) . " ل.س</s>")  // بيطلع السعر القديم مشطوب

                // حطيت ال  HTML هون لانو في عندي وسم الشطب فوق 
                ->html()
                ->color('gray'),  




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



                // حقل وهمي مابينحفظ بالداتا بيز

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])


            ->bulkActions([
                
                Tables\Actions\BulkActionGroup::make([ // للحذف الجماعي
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            
            // هي مشان تطلع احدث العروض اول شي 
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
            'create' => Pages\CreateOffer::route('/create'),
            'edit' => Pages\EditOffer::route('/{record}/edit'),
        ];
    }
}
