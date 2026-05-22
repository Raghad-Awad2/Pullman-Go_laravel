<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
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




        // المسمى في القائمة الجانبية
    protected static ?string $navigationLabel = '  الكشف عن الرحلات';

  
    protected static ?string $pluralModelLabel = ' الرحلات';


    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';





    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
             // 1. اختيار الشركة ( القائمة المنسدلة )
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name') // بيربط تلقائياً وبجيب أسماء الشركات
                ->label('اختر الشركة')
                ->searchable()
                ->preload()    // بحمل الأسماء مسبقاً لسرعة الاختيار
                ->required(),




            
        Forms\Components\Placeholder::make('current_day_display')
                    ->label('تاريخ اليوم الحالي')
                    ->content(now()->translatedFormat('l, d F Y')) // بيعرض مثلاً: الأحد، 19 أبريل 2026
                    ->extraAttributes(['class' => 'text-primary-600 font-bold']), // تلوين التاريخ ليميزه الموظف


            Forms\Components\Section::make('تفاصيل الرحلة الأساسية')
                ->schema([
                    // 1. حقل مخفي لـ company_id بياخذ شركة الموظف اللي مسجل دخول تلقائياً
                    Forms\Components\Hidden::make('company_id')
                        ->default(auth()->user()->company_id),




                    // 2. اختيار المسار
                     Forms\Components\Select::make('route_id')
                        ->label('تحديد المسار (الخط)')


                        // استخدمت options لانو اني الادمن ماعندي شركات
                        ->options(function (Forms\Get $get) {
                            // 1. بنجيب الآي دي تبع الشركة اللي اخترتها من القائمة الأولى
                            $companyId = $get('company_id'); 
                            
                            // 2. إذا مو مختار شركة لسا، بنرجع قائمة فاضية
                            if (! $companyId) return []; 
                            
                            // 3. بنجيب بس مسارات هاي الشركة وبنعرضها بشكل مرتب
                            return \App\Models\Route::where('company_id', $companyId)->get()
                                ->mapWithKeys(fn ($record) => [
                                    $record->id => "من مدينة " . ($record->departureCity->name ?? 'غير معروف') . " إلى مدينة " . ($record->arrivalCity->name ?? 'غير معروف')
                                ]);
                        })

                        ->preload()
                        ->required()
                        // هي بتاخذ سطر كامل لحالها
                        ->columnSpanFull()
                        // هي مشان لون الخط
                        ->native(false)
                        ->placeholder('اختر المسار الذي ستعمل عليه هذه الرحلة ')

                        // خليت خانة اختيار المسار "تراقب" التغيير
                        ->live(), 
                        


            // Placeholder بيعطيني نص عادي بدون حدود 
            // 2. حقل السعر الأساسي (بدون حدود + مع فواصل)
            Forms\Components\Placeholder::make('base_price')

                ->label('السعر الأساسي للتذكرة على هذا المسار ')
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
                // هي بتاخذ سطر كامل لحالها
                ->columnSpanFull()
                ->dehydrated(false), // ما بنخزنه بجدول الرحلات لأنه موجود بالمسارات  
                
                


                // 1. عرض عنوان الانطلاق بالتفصي)
            Forms\Components\Placeholder::make('departure_address')
                ->label('عنوان الانطلاق بالتفصيل')
                ->content(function (Forms\Get $get) {
                    $routeId = $get('route_id'); 
                    
                    if (! $routeId) {
                        return 'لم يتم اختيار مسار بعد';
                    }

                    // بنجيب عنوان الانطلاق من موديل المسار
                    $address = \App\Models\Route::find($routeId)?->departure_address;

                    return $address ?: 'لا يوجد عنوان تفصيلي مسجل';
                })
                ->dehydrated(false),


                
            // 2. عرض عنوان الوصول بالتفصيل (للعرض فقط)
            Forms\Components\Placeholder::make('arrival_address')
                ->label('عنوان الوصول بالتفصيل')
                ->content(function (Forms\Get $get) {
                    $routeId = $get('route_id'); 

                    if (! $routeId) {
                        return 'لم يتم اختيار مسار بعد';
                    }

                    // بنجيب عنوان الوصول من موديل المسار
                    $address = \App\Models\Route::find($routeId)?->arrival_address;

                    return $address ?: 'لا يوجد عنوان تفصيلي مسجل';
                })
                ->dehydrated(false),





            Forms\Components\Placeholder::make('route_stops')
                ->label('استراحات هذا المسار')
                ->content(function (Forms\Get $get) {
                    $routeId = $get('route_id');
                    
                    if (! $routeId) {
                        return 'يُرجى اختيار مسار أولاً';
                    }

                    // منجيب المسار ومنشوف الاستراحات اللي مخزنة جواه
                    $route = \App\Models\Route::find($routeId);
                    $stops = $route?->rest_stops; // تأكد إنو الاسم مطابق لعمود الاستراحات عندك بالداتابيز

                    // إذا ما في استراحات (لأنو مو إجباري متل ما قلت يا قلب)
                    if (empty($stops)) {
                        return 'لا يوجد استراحات مسجلة لهذا المسار (مسار مباشر)';
                    }

                    // إذا فيه استراحات، منعرضهم بشكل قائمة مرتبة
                    $output = "";
                    
                    foreach ($stops as $index => $stop) {
                        // مصفوفة الأسماء الترتيبية
                        $ordinals = [
                            0 => 'الأولى', 1 => 'الثانية', 2 => 'الثالثة', 3 => 'الرابعة', 
                            4 => 'الخامسة', 5 => 'السادسة', 6 => 'السابعة', 7 => 'الثامنة'
                        ];

                        // بنجيب الاسم من المصفوفة، وإذا مو موجود بنحط الرقم عادي
                        $label = $ordinals[$index] ?? ($index + 1);
                        
                        $name = $stop['stop_name'] ?? 'بدون اسم';
                        $location = !empty($stop['stop_location']) ? " ({$stop['stop_location']})" : "";
                        
                        // التنسيق النهائي: الاستراحة الأولى : اسم الاستراحة (الموقع)
                        $output .= "📍 الاستراحة {$label} : {$name}{$location} \n";
                    }

                    return $output;
                })
                // هي السطرين مشان يظهر النص بشكل مرتب تحت بعضه
                ->extraAttributes(['style' => 'white-space: pre-line; color: #fbbf24; font-weight: bold;'])
                // هي بتاخذ سطر كامل لحالها
                ->columnSpanFull()
                ->dehydrated(false),



                
                Forms\Components\Select::make('trip_id')
                    ->label(' الرحلة المحددة')
                    ->options(function (Forms\Get $get) {
                            $routeId = $get('route_id'); 
                            if (! $routeId) return [];
                            
                            return \App\Models\Trip::where('route_id', $routeId)->get()
                                ->mapWithKeys(function ($record) {
                                    $time = \Carbon\Carbon::parse($record->scheduled_time)->format('g:i A');
                                    return [$record->id => "الرحلة رقم {$record->id} | وقت الإنطلاق: {$time}"];
                                });
                        })
                        ->live() 
    // afterStateUpdated هو "مراسل". لما تختار الرحلة، هذا المراسل بيركض فوراً بياخد الوقت من قاعدة البيانات
    // وبيروح بيحطه جوا صندوق "موعد انطلاق الرحلة". بدون هذا المراسل، الصندوق بيبقى فاضي لأنه disabled
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            // 1. منجيب بيانات الرحلة من الداتابيز بناءً على الـ ID اللي اخترناه
                            $trip = \App\Models\Trip::find($state);
                            
                            if ($trip) {
                                // 2. منوزع البيانات على الحقول التانية
                                $set('bus_id', $trip->bus_id);
                                
                                // ملاحظة مهمة: الـ TimePicker بيحتاج الوقت بصيغة (ساعة:دقيقة:ثانية) عشان يظهر
                                $set('scheduled_time', \Carbon\Carbon::parse($trip->scheduled_time)->format('H:i:s'));
                                
                                $set('days_of_week', $trip->days_of_week);
                            } else {
                                // إذا الموظف مسح الاختيار، منصفر كل شي
                                $set('bus_id', null);
                                $set('scheduled_time', null);
                                $set('days_of_week', []);
                            }
                    })

                    // هاد مشان الحقول تتعبى اول ما اضغط على اي رحلة تتعبى تلقائيا على حسب شو ضغطت
                        ->afterStateHydrated(function ($state, Forms\Set $set, $record) {
                            // إذا كنا بصفحة تعديل والـ record موجود
                            if ($record) {
                                // 1. منثبت الاختيار على الـ ID تبع الرحلة اللي فتنا عليها
                                $set('trip_id', $record->id);
                                
                                // 2. منعبّي باقي الحقول فوراً عشان ما تطلع فاضية أول ما تفتح الصفحة
                                $set('bus_id', $record->bus_id);
                                $set('scheduled_time', \Carbon\Carbon::parse($record->scheduled_time)->format('H:i:s'));
                                $set('days_of_week', $record->days_of_week);
                            }
                    })

                    
                    ->preload()
                    ->searchable()
                    // هي بتاخذ سطر كامل لحالها
                    ->columnSpanFull()
                    ->placeholder('اختر الرحلة لعرض تفاصيلها'),







                    // 2. اختيار المسار
                     Forms\Components\Select::make('bus_id')
                        ->label(' الباص المحدد لهذه الرحلة ')

                        ->options(function (Forms\Get $get) {
                            $companyId = $get('company_id');
                            
                            if (! $companyId) return [];
                            
                            return \App\Models\Bus::where('company_id', $companyId)->get()
                                ->mapWithKeys(fn ($record) => [
                                    $record->id => "باص رقم: " . ($record->bus_numbernnn ?? 'غير معروف') . " - موديل: " . ($record->bus_model ?? 'غير معروف')
                                ]);
                        })



                        // هي مشان يتحملو الباصات بسرعة قبل ما اضغط على الحقل
                        ->preload()
                        ->disabled()
                        // هي مشان لون الخط
                        ->native(false)
                        ->placeholder('اختر الباص المخصص لهذه الرحلة')

                      // بيراقب التغيير عشان يجيب بيانات السائق فوراً
                        ->live(),
                        


            // Placeholder بيعطيني نص عادي بدون حدود 
            // 4. تفاصيل الباص والسائق (تعبئة تلقائية للقراءة فقط)
            Forms\Components\Grid::make(3) // قسمتهن 3 أعمدة
                ->schema([
                    
                    
                    // رقم اللوحة
                    Forms\Components\Placeholder::make('bus_number')
                        ->label('رقم اللوحة')
                        ->content(function (Forms\Get $get) {

                            // بجيب رقم لوحة الباص على حسب الباص الي اختاره الموظف
                            $busId = $get('bus_id'); 
                            if (! $busId) return '---';
                            
                            return \App\Models\Bus::find($busId)?->bus_number ?? 'غير متوفر';
                        }),


                     // عدد مقاعد هذا الباص
                    Forms\Components\Placeholder::make('total_seats')
                        ->label('العدد الكلي للمقاعد')
                        ->content(function (Forms\Get $get) {
                            $busId = $get('bus_id');
                            if (! $busId) return '---';
                            
                            return \App\Models\Bus::find($busId)?->total_seats ?? 'غير متوفر';
                        }),


                    // اسم السائق
                    Forms\Components\Placeholder::make('driver_name')
                        ->label('اسم السائق')
                        ->content(function (Forms\Get $get) {
                            $busId = $get('bus_id'); 
                            if (! $busId) return '---';
                            
                            return \App\Models\Bus::find($busId)?->driver_name ?? 'غير متوفر';
                        }),

                    // رقم هاتف السائق
                    Forms\Components\Placeholder::make('driver_phone')
                        ->label('رقم هاتف السائق')
                        ->content(function (Forms\Get $get) {
                            $busId = $get('bus_id'); 
                            if (! $busId) return '---';
                            
                            return \App\Models\Bus::find($busId)?->driver_phone ?? 'غير متوفر';
                        })
                ->dehydrated(false), // ما بنخزنه بجدول الرحلات لأنه موجود بالباصات     
                ])->columnSpanFull(), // عشان تأخذ عرض الصفحة كامل       






                    // 4. وقت الانطلاق
                    Forms\Components\TimePicker::make('scheduled_time')
                        ->label('موعد انطلاق الرحلة ')
                        // اقتراحات سريعة للموظف
                        ->datalist([
                            '08:00', '09:00', '10:00', '11:00', '12:00',
                            '13:00', '14:00', '15:00', '16:00', '17:00',
                        ])
                        ->disabled(),


                    // 5. أيام التكرار
                    Forms\Components\Select::make('days_of_week')
                        ->label('أيام عمل الرحلة')
                        ->multiple()  // بقدر يحدد اكثر من يوم
                        ->options([
                            0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء',
                            3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
                        ])
                        ->disabled(),




                    Forms\Components\Placeholder::make('is_active_display')
                        ->label('حالة الرحلة')
                        
                        // خليتو يقرأ من حقل ال trip_id مباشرة
                        ->content(function (Forms\Get $get) {
                                $tripId = $get('trip_id'); // بنجيب الرحلة المختارة من الفورم
                                if (! $tripId) return 'غير محدد';
                                
                                $trip = \App\Models\Trip::find($tripId);
                                if (!$trip) return 'غير محدد';
                                
                                return $trip->is_active ? '✅ نشطة حالياً' : '❌ متوقفة';
                            }),


                ])->columns(2), // قسمنا الحقول لعمودين عشان الشكل يكون أرتب



            ]);
    }







    public static function table(Table $table): Table
    {
        return $table
            ->columns([


            Tables\Columns\TextColumn::make('company.name')->label('اسم الشركة')
                ->searchable()
                ->weight('bold') // خط عريض
                ->color('primary'),//لون ذهبي 



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
            ])
            
            // هي مشان تطلع احدث الرحلات اول شي 
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
