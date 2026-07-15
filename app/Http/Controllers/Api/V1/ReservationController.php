<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReservationStatus;
use App\Http\Requests\Api\ReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $reservations = Reservation::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('date'), fn ($q, $d) => $q->whereDate('date', $d))
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(ReservationResource::collection($reservations));
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return $this->respond(new ReservationResource($reservation));
    }

    public function store(ReservationRequest $request): JsonResponse
    {
        $reservation = Reservation::create(
            $request->validated() + ['status' => $request->validated('status') ?? ReservationStatus::PENDING]
        );

        return $this->respondCreated(new ReservationResource($reservation), 'Reservation created');
    }

    public function update(ReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $reservation->update($request->validated());

        return $this->respond(new ReservationResource($reservation->fresh()), 'Reservation updated');
    }

    public function checkIn(Reservation $reservation): JsonResponse
    {
        if ($reservation->status !== ReservationStatus::PENDING) {
            abort(422, 'Only pending reservations can be checked in');
        }

        $reservation->update(['status' => ReservationStatus::CHECKED_IN]);

        return $this->respond(new ReservationResource($reservation->fresh()), 'Guest checked in');
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->update(['status' => ReservationStatus::CANCELLED]);

        return $this->respond(new ReservationResource($reservation->fresh()), 'Reservation cancelled');
    }
}
