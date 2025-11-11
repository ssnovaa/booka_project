<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\AndroidPublisher;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionsController extends Controller
{
    /**
     * Верификация покупки Google Play (Subscriptions V2).
     *
     * Роут:
     *   Route::post('/subscriptions/play/verify', [SubscriptionsController::class, 'verifyGooglePlay']);
     *
     * Поддерживает оба варианта ключей из клиента:
     *   - camelCase: purchaseToken, productId, packageName (или без него)
     *   - snake_case: purchase_token, product_id, package_name (или без него)
     */
    public function verifyGooglePlay(Request $request)
    {
        // Достаём значения из любого формата
        $purchaseToken = $request->input('purchaseToken', $request->input('purchase_token'));
        $productId     = $request->input('productId',     $request->input('product_id'));
        $packageFromRq = $request->input('packageName',   $request->input('package_name'));

        // Валидируем минимум
        if (empty($purchaseToken) || empty($productId)) {
            return response()->json([
                'error'   => 'Validation failed',
                'details' => 'purchaseToken/productId is required'
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();

        // Имя пакета и путь к ключу берём из .env (как у тебя в проверках)
        $packageName = $packageFromRq ?: env('GOOGLE_PLAY_PACKAGE', 'com.booka_app');
        $credentialsPath = env('GOOGLE_PLAY_KEY_FILE'); // напр. C:/project/booka_project/storage/keys/service-account.json

        if (empty($credentialsPath) || !is_file($credentialsPath)) {
            Log::error('Google Play key file is missing or invalid', [
                'user_id' => $user?->id,
                'path'    => $credentialsPath,
            ]);
            return response()->json(['error' => 'Server configuration error'], 500);
        }

        try {
            // 1) Авторизация Google API
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(AndroidPublisher::ANDROIDPUBLISHER);
            $publisher = new AndroidPublisher($client);

            // 2) Вызов V2: purchases.subscriptionsv2.get(packageName, token)
            $purchase = $publisher->purchases_subscriptionsv2->get($packageName, $purchaseToken);

            // 3) Разбор ответа V2
            // Основные поля:
            //   subscriptionState: 'SUBSCRIPTION_STATE_ACTIVE' | ...
            //   lineItems[0].productId, lineItems[0].expiryTime (ISO)
            $state     = $purchase->getSubscriptionState();    // строка
            $lineItems = $purchase->getLineItems() ?: [];
            $first     = $lineItems[0] ?? null;

            $productFromGoogle = $first ? ($first->getProductId() ?? null) : null;
            $expiryIso         = $first ? ($first->getExpiryTime() ?? null) : null;

            Log::info('Google verify (V2) response', [
                'user_id' => $user?->id,
                'state'   => $state,
                'product' => $productFromGoogle,
                'expiry'  => $expiryIso,
                'req'     => ['productId' => $productId, 'packageName' => $packageName],
            ]);

            // Доп.проверка соответствия товара (на случай, если клиент прислал не тот id)
            if (!empty($productFromGoogle) && $productFromGoogle !== $productId) {
                Log::warning('ProductId mismatch between client and Google response', [
                    'user_id' => $user?->id,
                    'client_product' => $productId,
                    'google_product' => $productFromGoogle,
                ]);
                // Не валим сразу ошибкой — у Google могут быть заменённые/сконфигуренные планы.
                // При желании можно вернуть 400.
            }

            // 4) Проверка статуса
            if ($state === 'SUBSCRIPTION_STATE_ACTIVE') {
                $user->is_paid = true;

                if (!empty($expiryIso)) {
                    // expiryTime в V2 уже ISO8601, парсим как Carbon
                    $user->paid_until = Carbon::parse($expiryIso);
                }

                $user->save();

                // Возвращаем как в /auth/me (для обновления клиента)
                $user->load('favorites', 'listens.book', 'listens.chapter');
                $user->append('credits');

                return response()->json([
                    'success' => true,
                    'state'   => $state,
                    'product' => $productFromGoogle ?: $productId,
                    'expiry'  => $expiryIso,
                    'user'    => $user,
                ]);
            }

            // Не активна/приостановлена/просрочена
            return response()->json([
                'success' => false,
                'state'   => $state,
                'message' => 'Subscription not active',
            ], 400);

        } catch (\Google\Service\Exception $e) {
            // Ошибка со стороны Google API
            Log::error('Google API error during verification (V2)', [
                'user_id' => $user?->id,
                'code'    => $e->getCode(),
                'msg'     => $e->getMessage(),
            ]);
            return response()->json([
                'error'   => 'Google API validation failed',
                'details' => $e->getMessage(),
            ], 400);

        } catch (\Throwable $e) {
            // Общая ошибка сервера
            Log::error('General error during verification (V2)', [
                'user_id' => $user?->id,
                'msg'     => $e->getMessage(),
            ]);
            return response()->json([
                'error'   => 'Server error during verification',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Текущий статус подписки пользователя (как и прежде).
     */
    public function status(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $user->load('favorites', 'listens.book', 'listens.chapter');
        $user->append('credits');

        return response()->json($user);
    }
}
