<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\Category;
use App\Services\CarAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Public fleet endpoints delegate every date decision to CarAvailabilityService. */
class CarController extends Controller
{
    /** Receives the shared availability service used by every public date endpoint. */
    public function __construct(private CarAvailabilityService $availability) {}

    /** Lists active, operational cars with optional filters, dates, sorting, and pagination. */
    public function index(Request $request)
    {
        $query = Car::query()
            ->with(['category', 'images'])
            ->where('is_active', true)
            ->where('operational_status', 'available')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($search) => $search
                ->where('brand', 'like', '%'.$request->search.'%')
                ->orWhere('model', 'like', '%'.$request->search.'%')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('fuel_type'), fn ($q) => $q->where('fuel_type', $request->fuel_type))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->transmission))
            ->when($request->filled('seats'), fn ($q) => $q->where('seats', '>=', $request->integer('seats')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('daily_price', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('daily_price', '<=', $request->float('max_price')));

        if ($request->filled('start_date') || $request->filled('end_date')) {
            if (! $request->filled('start_date') || ! $request->filled('end_date')) {
                throw ValidationException::withMessages(['dates' => 'Veuillez sélectionner une date de début et une date de fin.']);
            }
            [$start, $end] = $this->availability->normalizeRange((string) $request->start_date, (string) $request->end_date);
            $this->availability->applyAvailableForRange($query, $start, $end);
        }

        $sortColumn = $request->sort === 'newest' ? 'created_at' : 'daily_price';
        $sortDirection = in_array($request->sort, ['price_desc', 'newest'], true) ? 'desc' : 'asc';

        return CarResource::collection($query->orderBy($sortColumn, $sortDirection)->paginate(9)->withQueryString());
    }

    /** Returns one active vehicle with its category and ordered images. */
    public function show(Car $car): CarResource
    {
        abort_unless($car->is_active, 404);

        return new CarResource($car->load(['category', 'images']));
    }

    /** Lists active categories used by catalogue filters and forms. */
    public function categories(): JsonResponse
    {
        return response()->json(['data' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    /** Quotes a requested half-open rental period using backend prices and availability. */
    public function availability(Request $request, Car $car): JsonResponse
    {
        return response()->json($this->availability->quote(
            $car,
            (string) $request->query('start_date', $request->input('start_date', '')),
            (string) $request->query('end_date', $request->input('end_date', '')),
        ));
    }

    /** Returns safe blocking periods for the public calendar without client details. */
    public function unavailablePeriods(Request $request, Car $car): JsonResponse
    {
        [$from, $to] = $this->availability->normalizeRange(
            (string) $request->query('from', ''),
            (string) $request->query('to', ''),
        );

        return response()->json([
            'data' => $this->availability->getUnavailablePeriods($car, $from, $to),
            'car_id' => $car->id,
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
