<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Booking\BookClassScheduleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\ClassBookingRequest;
use App\Http\Resources\ClassBookingResource;
use App\Models\ClassBooking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassBookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function myBookings(Request $request): AnonymousResourceCollection
    {
        $bookings = ClassBooking::with(['schedule.gymClass.coverImage', 'schedule.trainer.avatar'])
            ->where('user_id', $request->user()->id)
            ->latest('booked_at')
            ->paginate(15);

        return ClassBookingResource::collection($bookings);
    }

    public function book(ClassBookingRequest $request): JsonResponse
    {
        $dto = BookClassScheduleDTO::fromRequest($request, $request->user()->id);
        $booking = $this->bookingService->bookClass($dto);

        return response()->json([
            'message' => 'Đặt chỗ lớp học thành công.',
            'booking' => new ClassBookingResource($booking->load('schedule.gymClass')),
        ], 201);
    }

    public function cancel(int $scheduleId, Request $request): JsonResponse
    {
        $this->bookingService->cancelClass($scheduleId, $request->user()->id);

        return response()->json([
            'message' => 'Hủy đặt chỗ lớp học thành công.',
        ]);
    }
}
