<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Booking\PTBookingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\PTBookingRequest;
use App\Http\Resources\PTBookingResource;
use App\Models\PTBooking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PTBookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function myBookings(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = PTBooking::with(['trainer.avatar', 'member.avatar']);

        if ($user->isTrainer()) {
            $query->where('trainer_id', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $bookings = $query->latest('start_time')->paginate(15);

        return PTBookingResource::collection($bookings);
    }

    public function book(PTBookingRequest $request): JsonResponse
    {
        $dto = PTBookingDTO::fromRequest($request, $request->user()->id);
        $booking = $this->bookingService->bookPT($dto);

        return response()->json([
            'message' => 'Đặt lịch tập với huấn luyện viên thành công.',
            'booking' => new PTBookingResource($booking->load(['trainer', 'member'])),
        ], 201);
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $booking = $this->bookingService->cancelPTBooking(
            $id,
            $request->user()->id,
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Hủy lịch tập PT thành công.',
            'booking' => new PTBookingResource($booking),
        ]);
    }

    public function review(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = $this->bookingService->reviewPTBooking(
            $id,
            $request->user()->id,
            (int) $request->input('rating'),
            $request->input('notes')
        );

        return response()->json([
            'message' => 'Đánh giá buổi tập thành công. Cảm ơn phản hồi của bạn!',
            'booking' => new PTBookingResource($booking),
        ]);
    }
}
