<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    // 1. الجواسيس اللي رح تستقبل البيانات من الكنترولر وتوديها للتصميم

    public $otp; // 1. تعريف المتغير اللي رح يحمل الرمز
    public $mailSubject; // جاسوس العنوان
    public $bodyText;    // جاسوس النص الداخلي




    // 2. الاستقبال وقت الاستدعاء من ملف الكونترولر
    public function __construct($otp, $mailSubject, $bodyText)
    {
        $this->otp = $otp;
        $this->mailSubject = $mailSubject;
        $this->bodyText = $bodyText;
    }




    // 3. تحديد عنوان الإيميل (اللي بيظهر كإشعار على الموبايل)
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject, // استخدمنا جاسوس العنوان هون
        );
    }



    // 4. ربط البيانات مع ملف التصميم (واجهة الفيو)
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp', // (مجلد emails ثم ملف otp) 
        );
    }


    // هون اذا بدي ابعث صور على الايميل
    public function attachments(): array
    {
        return [];
    }
}