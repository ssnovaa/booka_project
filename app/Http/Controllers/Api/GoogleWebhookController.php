<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\DeviceToken; // ᐊ===== ДОДАЙТЕ ЦЕ
use App\Services\Subscriptions\GooglePlayVerifier;
use App\Services\FcmService; // ᐊ===== ДОДАЙТЕ ЦЕ
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleWebhookController extends Controller
{
    /**
     * Обробка сповіщень RTDN від Google Pub/Sub.
     * * ‼️ МИ ДОДАЛИ FcmService ДЛЯ СПОВІЩЕННЯ КЛІЄНТА
     */
    public function handleRtdn(Request $request, GooglePlayVerifier $verifier, FcmService $fcmService)
    {
        // 1. Декодуємо повідомлення від Google.
        $payload = json_decode(base64_decode($request->input('message.data')), true);

        if (!$payload) {
            Log::warning('RTDN: Недійсний Pub/Sub payload', ['body' => $request->all()]);
            return response()->json(['status' => 'bad_request'], 400); 
        }

        // 2. Витягуємо токен покупки
        $notification = $payload['subscriptionNotification'] ?? $payload['testNotification'] ?? null;
        $purchaseToken = $notification['purchaseToken'] ?? null;
        $productId = $notification['subscriptionId'] ?? null; 

        if (!$purchaseToken) {
            Log::info('RTDN: Отримано сповіщення без purchaseToken (можливо, тестове)', ['payload' => $payload]);
            return response()->json(['status' => 'ok_no_token']); 
        }
        
        $notificationType = $notification['notificationType'] ?? 'N/A';
        Log::info('RTDN: Отримано сповіщення', ['token' => $purchaseToken, 'type' => $notificationType]);

        // 3. Знаходимо підписку (і користувача) в нашій БД
        $subscription = Subscription::where('purchase_token', $purchaseToken)->first();

        if (!$subscription || !$subscription->user) {
            Log::error('RTDN: Отримано токен, якого немає в нашій БД', ['token' => $purchaseToken]);
            return response()->json(['status' => 'subscription_not_found']);
        }

        $user = $subscription->user;
        $wasPaid = $user->is_paid; // ᐊ===== Запам'ятовуємо старий статус

        // 4. ✅ ВИКОРИСТОВУЄМО ІСНУЮЧИЙ ВЕРИФІКАТОР
        try {
            $verifier->verifyAndUpsert($user, [
                'purchaseToken' => $purchaseToken,
                'productId'     => $productId ?? $subscription->product_id,
                'packageName'   => $subscription->package_name,
            ]);

            $subscription->refresh();
            $user->refresh(); // ᐊ===== Оновлюємо користувача, щоб отримати новий статус
            
            Log::info('RTDN: Підписку успішно оновлено', [
                'user_id' => $user->id, 
                'token' => $purchaseToken, 
                'new_status' => $subscription->status
            ]);

            // 5. ‼️ НОВИЙ КРОК: НАДСИЛАЄМО ТИХИЙ PUSH, ЯКЩО СТАТУС ЗМІНИВСЯ
            if ($wasPaid && !$user->is_paid) {
                Log::info('RTDN: Статус змінився (був платний -> став безкоштовний). Надсилаємо push.', ['user_id' => $user->id]);
                
                $tokens = DeviceToken::where('user_id', $user->id)
                                     ->pluck('token')
                                     ->all();

                if (!empty($tokens)) {
                    // Надсилаємо "тихий" push (data-only)
                    // Клієнт (Flutter) має бути налаштований на отримання
                    // повідомлень з полем 'type' = 'status_update'
                    $fcmService->sendPush(
                        $tokens, 
                        null,  // title = null
                        null,  // body = null
                        ['type' => 'status_update'] // ᐊ===== Тільки дані
                    );
                }
            }

        } catch (\Throwable $e) {
            Log::error('RTDN: Помилка під час оновлення підписки', [
                'user_id' => $user->id,
                'msg'     => $e->getMessage(),
            ]);
            return response()->json(['status' => 'server_error'], 500);
        }

        // 6. Відповідаємо 200 OK
        return response()->json(['status' => 'ok']);
    }
}