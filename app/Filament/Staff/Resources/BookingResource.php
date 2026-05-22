<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\BookingResource\Pages;
use App\Filament\Staff\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

use Filament\Tables\Columns\TextColumn;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    

// الفلامينت عم بدور على علاقة بين الحجوزات والشركات بس اني ربطت الحجوزات بالرحلات وهيا مربوطة بالباصات والمسارات والشركات
    protected static bool $isScopedToTenant = false;

    

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    
    // اسم التبويب باللوحة
    protected static ?string $navigationLabel = 'إدارة الحجوزات';
    protected static ?string $modelLabel = 'حجز';
    protected static ?string $pluralModelLabel = 'الحجوزات';





    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                
            ]);
    }






// جيبلي رقم الموظف الي مسجل دخول حاليا 
// اذا كان ادمن اعرضلو كلشي حجوزات للشركات 
// واذا كان موظف او صاحب شركة اعرض حجوزات الرحلة الي تابعة لشركة الموظف

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

// دالة موجودة بموديل المستخدمين
        if ($user->isAdmin()) {
            return $query;
        }

        // 2. إذا كان موظف أو صاحب شركة، جيب الحجوزات اللي رحلتها تابعة لشركته فقط
        return $query->whereHas('trip', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        });
    }








public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                TextColumn::make('reference_number')
                    ->label('رقم الحجز')
                    ->searchable()
                    ->badge()
                    ->color('primary'),



                // 2. اسم صاحب الحساب (اللي حجز)
                TextColumn::make('user.name')
                    ->label('اسم صاحب الحجز (المستخدم)')
                    ->searchable()
                    ->icon('heroicon-o-user'),



                TextColumn::make('user.phone')
                    ->label('رقم الهاتف')
                    ->searchable(),



                // 3. المسار (من جدول الـ Route عن طريق الـ Trip)
                // هاد الحقل الوهمي بالنسبة لل db وفلامينت بشوفه حقيقيroute_full_name
                TextColumn::make('trip.route.route_full_name')
                    ->label('المسار')
                    ->sortable()
                    ->searchable(),



                // 4. موعد وتاريخ الرحلة (من جدول الـ Trip) الي حددو المسافر 
                TextColumn::make('travel_date')
                    ->label('تاريخ حجز الرحلة')
                    ->date('Y-m-d')
                    ->sortable(),



                TextColumn::make('trip.scheduled_time')
                    ->label('موعد انطلاق الرحلة')
                    ->time('h:i A'), // عشان يطبع AM / PM
                    


                TextColumn::make('seats_count')
                    ->label('عدد المقاعد المحجوزة')
                    ->numeric()
                    ->alignCenter(),



                TextColumn::make('seats.passenger_name')
                    ->label('أسماء الركاب')
                    ->color('info'),



                // 5. عدد المقاعد وأرقامها (من علاقة الـ seats)
                // الفلامنت ذكي، رح يجيب كل أرقام المقاعد ويحطها كـ باجات (أزرار صغيرة) جنب بعض
                TextColumn::make('seats.seat_number')
                    ->label('رقم المقعد')
                    ->badge()
                    ->color('info'),



                // 6. السعر الإجمالي
                TextColumn::make('total_price')
                    ->label('السعر الإجمالي ')
                    ->formatStateUsing(fn ($state) => number_format($state)   . "  ل.س")
                    ->color('success') 
                    ->sortable(),

                    

                // 7. حالة الدفع (مع تلوين حسب الحالة)
                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    }),
            ])
            // بتحكم بعدد الاعمدة
            ->filtersFormColumns(4)





->filters([

    // 1. فلتر ذكي ومدمج (المسار + الرحلة) عشان يتفاعلوا مع بعض Live
    Tables\Filters\Filter::make('route_and_trip')
        ->form([
            Forms\Components\Select::make('route_id')
                ->label('تصفية حسب المسار')
                ->options(fn () => \App\Models\Route::where('company_id', auth()->user()->company_id)
                    ->get()
                    ->pluck('route_full_name', 'id')
                )
                ->searchable()
                ->live()
                ->columnSpan(1)    // بياخد مساحة خانة وحدة
                ->afterStateUpdated(fn (Forms\Set $set) => $set('trip_id', null)), // تصفير الرحلة لما نغير المسار




            Forms\Components\Select::make('trip_id')
                ->label('تصفية حسب الرحلة')

                // مشان رقم الباص واسم السائق يبينو عند اختياري للرحلة
                ->live()
                ->options(function (Forms\Get $get) {
                    $routeId = $get('route_id');
                    $query = \App\Models\Trip::where('company_id', auth()->user()->company_id);

                    // إذا الموظف اختار مسار، جيب بس رحلات هاد المسار
                    if ($routeId) {
                        $query->where('route_id', $routeId);
                    }

                    return $query->get()->mapWithKeys(function ($trip) {
                        // تنسيق الوقت (صباحاً/مساءً) مع رقم الرحلة والتاريخ
                        $time = $trip->scheduled_time ? \Carbon\Carbon::parse($trip->scheduled_time)->format('h:i A') : 'غير محدد';
                        $date = $trip->trip_date ? \Carbon\Carbon::parse($trip->trip_date)->format('Y-m-d') : '';
                        $dayName = $trip->trip_date ? \Carbon\Carbon::parse($trip->trip_date)->translatedFormat('l') : '';  // بجيب اسم اليوم حرف lبجيب اسم اليوم كامل 
                        
                        return [$trip->id => "رحلة رقم {$trip->id} | {$dayName} | الانطلاق: {$time} | بتاريخ: {$date}"];
                    });
                })
                
                ->searchable()
                ->columnSpan(2),  // بيوخذ مساحة خانتين



                Forms\Components\Placeholder::make('bus_details')
                    ->label('بيانات الحافلة والسائق')
                    ->content(function (Forms\Get $get) {
                        $tripId = $get('trip_id'); // بنجيب رقم الرحلة اللي اختارها الموظف
                        
                        if (! $tripId) return 'يرجى اختيار رحلة أولاً';

                        // بنجيب بيانات الرحلة ومعها الباص المرتبط فيها
                        $trip = \App\Models\Trip::with('bus')->find($tripId);
                        $bus = $trip?->bus;

                        if (! $bus) return 'لا يوجد باص مرتبط بهذه الرحلة';

                        return "باص رقم: {$bus->bus_numbernnn} | السائق: {$bus->driver_name} | هاتف: {$bus->driver_phone}";
                    })
                    ->columnSpanFull(), //  ياخد العرض كامل
        ])

                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['route_id'], fn ($q, $routeId) => $q->whereHas('trip', fn($t) => $t->where('route_id', $routeId)))
                        ->when($data['trip_id'], fn ($q, $tripId) => $q->where('trip_id', $tripId));
                })


                ->columns(3) // تقسيم داخلي لـ 3 أعمدة
                ->columnSpanFull(),



                
            // 2. فلتر حسب حالة الدفع
            Tables\Filters\SelectFilter::make('payment_status')
                ->label('حالة الدفع')
                ->options([
                    'paid' => 'مدفوع',
                    'unpaid' => 'غير مدفوع',
                    'cancelled' => 'ملغي',
                ])
                ->columnSpan(1),



            // 3. فلتر التاريخ (من - إلى)
            Tables\Filters\Filter::make('travel_date')
                ->label('تاريخ السفر')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                    Forms\Components\DatePicker::make('until')->label('إلى تاريخ'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('travel_date', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('travel_date', '<=', $date));
                })

                ->columns(2)
                ->columnSpan(2),

        ], layout: Tables\Enums\FiltersLayout::AboveContent)  // هاد السطر مكانه الصح هون عشان يظهر الفلتر فوق الجدول
                    




            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
