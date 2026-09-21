<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Payment\PaymentWebhookDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Payment\PaymentWebhookRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function createPayment(CreatePaymentRequest $request): JsonResponse
    {
        $packageId = (int) $request->validated('package_id');
        $method = (string) ($request->validated('payment_method') ?? 'vietqr');

        $result = $this->paymentService->createPayment(
            $request->user()->id,
            $packageId,
            $method
        );

        return response()->json([
            'message' => 'Tạo giao dịch thanh toán thành công.',
            'transaction' => new TransactionResource($result['transaction']),
            'payment_url' => $result['payment_url'],
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'transaction_code' => $result['transaction_code'],
            'instructions' => $result['instructions'],
        ], 201);
    }

    public function webhook(PaymentWebhookRequest $request): JsonResponse
    {
        $dto = PaymentWebhookDTO::fromRequest($request);
        $result = $this->paymentService->handleWebhook($dto);

        return response()->json($result);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $transactions = Transaction::with('subscription.package')
            ->where('user_id', $request->user()->id)
            ->latest('transaction_date')
            ->paginate(15);

        return TransactionResource::collection($transactions);
    }
}
