<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        // 1. جلب العروض مع كافة العلاقات المرتبطة بها (الشركة، المسار، مدن الانطلاق والوصول)
        $offers = Offer::with(['company', 'route.departureCity', 'route.arrivalCity'])
                      ->where('is_active', 1)
                      ->get();

        // 2. تعديل البيانات المرجعة ديناميكياً لإضافة السعر القديم (مأخوذ من base_price)
        $formattedOffers = $offers->map(function ($offer) {
            return [
                'id'           => $offer->id,
                'route_id'     => $offer->route_id,
                'offer_price'  => $offer->offer_price, // السعر الجديد بعد العرض
                
                // تم التعديل هنا ليقرأ الحقل الصحيح 'base_price' من جدول الـ routes
                'old_price'    => $offer->route ? $offer->route->base_price : null, 
                
                'start_date'   => $offer->start_date,
                'end_date'     => $offer->end_date,
                'is_active'    => $offer->is_active,
                'created_at'   => $offer->created_at,
                'updated_at'   => $offer->updated_at,
                
                // الحفاظ على العلاقات كاملة كما هي لتطبيق الـ Flutter
                'company'      => $offer->company,
                'route'        => $offer->route,
            ];
        });

        // 3. إرجاع البيانات المعدلة على شكل JSON
        return response()->json($formattedOffers);
    }


    
    public function getOfferDetails($id)
{
    $offer = Offer::with(['route'])->findOrFail($id);
    
    // هنا سنقوم بجلب التواريخ المتاحة بناءً على العرض
    // والرحلات المرتبطة بالمسار
    return response()->json([
        'start_date' => $offer->start_date,
        'end_date'   => $offer->end_date,
        // هذه الدالة ستحتاج لجلب الرحلات (Trips) التي تتبع هذا الـ route
        'trips'      => \App\Models\Trip::where('route_id', $offer->route_id)->get() 
    ]);
}
}