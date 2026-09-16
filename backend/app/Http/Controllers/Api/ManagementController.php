<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Provides CRUD endpoints shared by owner and administrator dashboards.
 * Handles fleet, categories, images, and user management.
 */
class ManagementController extends Controller
{
    /** Receives the role-aware analytics builder used by both staff dashboards. */
    public function __construct(private DashboardAnalyticsService $dashboardAnalytics) {}

    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->validate(['period' => ['nullable', 'in:'.implode(',', DashboardAnalyticsService::PERIODS)]])['period'] ?? '30d';

        return response()->json(['data' => $this->dashboardAnalytics->build($request->user(), $period)]);
    }

    /**
     * List all categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => Category::orderBy('name')->get(),
        ]);
    }

    /**
     * Create or update a category.
     */
    public function saveCategory(Request $request, ?Category $category = null): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name,'.($category?->id ?? 'NULL')],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if ($category) {
            $category->update($data);
        } else {
            $category = Category::create($data);
        }

        return response()->json(['data' => $category], $category->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * List all cars with their relations.
     */
    public function cars(): JsonResponse
    {
        return response()->json([
            'data' => Car::with(['category', 'images'])->latest()->get(),
        ]);
    }

    /**
     * Show a specific car.
     */
    public function showCar(Car $car): JsonResponse
    {
        return response()->json([
            'data' => $car->load(['category', 'images']),
        ]);
    }

    /**
     * Create or update a car.
     */
    public function saveCar(Request $request, ?Car $car = null): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'registration_number' => ['required', 'string', 'max:50', 'unique:cars,registration_number,'.($car?->id ?? 'NULL')],
            'brand' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'year' => ['required', 'integer', 'between:2000,'.(date('Y') + 1)],
            'color' => ['required', 'string'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'doors' => ['required', 'integer', 'between:2,6'],
            'fuel_type' => ['required', 'in:gasoline,diesel,hybrid,electric'],
            'transmission' => ['required', 'in:manual,automatic'],
            'daily_price' => ['required', 'numeric', 'min:1'],
            'mileage' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'operational_status' => ['required', 'in:available,maintenance,unavailable'],
            'is_active' => ['boolean'],
        ]);

        if ($car) {
            $car->update($data);
        } else {
            $car = Car::create($data);
        }

        return response()->json(['data' => $car->load(['category', 'images'])], $car->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * List all clients with their reservation count.
     */
    public function clients(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $query = User::where('role', 'client');

        if (filled($filters['search'] ?? null)) {
            $search = trim($filters['search']);
            $query->where(function ($candidate) use ($search) {
                $candidate->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json([
            // New registrations must appear on the first page immediately. The
            // deterministic ID order also avoids records moving between pages
            // when multiple accounts share the same creation timestamp.
            'data' => $query->withCount('reservations')->orderByDesc('id')->paginate(15),
        ]);
    }

    /**
     * List all staff members (owners and admins).
     */
    public function staff(Request $request): JsonResponse
    {
        $query = User::whereIn('role', ['owner', 'admin']);
        if ($request->search) {
            $query->where(fn ($q) => $q->where('first_name', 'like', '%'.$request->search.'%')->orWhere('last_name', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%'));
        }

        return response()->json([
            'data' => $query->latest()->paginate(15),
        ]);
    }

    /** Returns one client with recent reservations and payments for staff review. */
    public function showClient(User $user): JsonResponse
    {
        abort_unless($user->role === 'client', 404);

        return response()->json(['data' => $user->loadCount('reservations')->load([
            'reservations' => fn ($query) => $query->with('car')->latest()->limit(25),
            'payments' => fn ($query) => $query->with('reservation')->latest()->limit(25),
        ])]);
    }

    /** Returns one Responsable or administrator profile for the staff editor. */
    public function showStaff(User $user): JsonResponse
    {
        abort_unless(in_array($user->role, ['owner', 'admin'], true), 404);

        return response()->json(['data' => $user]);
    }

    /**
     * Create a new staff member.
     */
    public function createStaff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'is_active' => ['boolean'],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'owner';

        return response()->json(['data' => User::create($data)], 201);
    }

    /** Updates a staff profile and changes the password only when one is supplied. */
    public function updateStaff(Request $request, User $user): JsonResponse
    {
        abort_unless(in_array($user->role, ['owner', 'admin'], true), 404);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id], 'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ]);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);

        return response()->json(['data' => $user->fresh()]);
    }

    /**
     * Toggle the active status of a user (staff or client).
     * Deactivation prevents login instead of destructive deletion.
     */
    public function activation(User $user): JsonResponse
    {
        abort_if(request()->user()->is($user), 422, 'Vous ne pouvez pas désactiver votre propre compte.');
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['data' => $user]);
    }

    /**
     * Toggle the active status of a category.
     * Deactivation hides it from public instead of destructive deletion.
     */
    public function categoryActivation(Category $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json(['data' => $category]);
    }

    /**
     * Toggle the active status of a car.
     * Deactivation hides it from public instead of destructive deletion.
     */
    public function carActivation(Car $car): JsonResponse
    {
        $car->update(['is_active' => ! $car->is_active]);

        return response()->json(['data' => $car]);
    }

    /**
     * Update the operational status of a car.
     */
    public function operationalStatus(Request $request, Car $car): JsonResponse
    {
        $data = $request->validate([
            'operational_status' => ['required', 'in:available,maintenance,unavailable'],
        ]);

        $car->update($data);

        return response()->json(['data' => $car]);
    }

    /**
     * Upload multiple images for a car.
     */
    public function uploadImages(Request $request, Car $car): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $created = [];
        $currentMaxOrder = (int) $car->images()->max('sort_order');

        foreach ($request->file('images') as $index => $file) {
            $path = $file->store('cars', 'public');

            $created[] = CarImage::create([
                'car_id' => $car->id,
                'path' => '/storage/'.$path,
                'alt_text' => $car->brand.' '.$car->model,
                'is_primary' => ! $car->images()->exists() && $index === 0,
                'sort_order' => $currentMaxOrder + 1 + $index,
            ]);
        }

        return response()->json(['data' => $created], 201);
    }

    /**
     * Set a specific image as the primary image for a car.
     * Uses a transaction to ensure only one primary image exists.
     */
    public function primaryImage(Car $car, CarImage $image): JsonResponse
    {
        abort_unless($image->car_id === $car->id, 404);

        DB::transaction(function () use ($car, $image) {
            $car->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return response()->json(['data' => $image->fresh()]);
    }

    /**
     * Reorder images for a car.
     */
    public function reorderImages(Request $request, Car $car): JsonResponse
    {
        $data = $request->validate([
            'image_ids' => ['required', 'array'],
            'image_ids.*' => ['integer'],
        ]);

        foreach ($data['image_ids'] as $order => $id) {
            $car->images()->whereKey($id)->update(['sort_order' => $order]);
        }

        return response()->json(['data' => $car->images()->get()]);
    }

    /**
     * Delete an image from a car.
     */
    public function deleteImage(Car $car, CarImage $image): JsonResponse
    {
        abort_unless($image->car_id === $car->id, 404);

        $path = str_replace('/storage/', '', $image->path);
        Storage::disk('public')->delete($path);

        $wasPrimary = $image->is_primary;
        $image->delete();
        if ($wasPrimary) {
            $car->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return response()->json(['message' => 'Image supprimée.']);
    }
}
