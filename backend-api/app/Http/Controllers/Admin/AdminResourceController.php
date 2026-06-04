<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminResourceController extends Controller
{
    private function resourceName(Request $request): ?string
    {
        $segments = $request->segments();
        $adminIndex = array_search('admin', $segments, true);

        return $adminIndex === false
            ? ($segments[1] ?? null)
            : ($segments[$adminIndex + 1] ?? null);
    }

    public function index(Request $request)
    {
        return match ($this->resourceName($request)) {
            'users' => response()->json(['items' => $this->usersQuery($request)->paginate(20)]),
            'drivers' => response()->json(['items' => $this->driversQuery($request)->paginate(20)]),
            'vehicles' => response()->json(['items' => $this->vehiclesQuery($request)->paginate(20)]),
            'work-orders', 'work_orders' => response()->json(['items' => $this->workOrdersQuery($request)->paginate(20)]),
            'fuel-logs', 'fuel_logs' => response()->json(['items' => $this->fuelLogsQuery($request)->paginate(20)]),
            'audit-logs', 'audit_logs' => response()->json(['items' => $this->auditLogsQuery($request)->paginate(20)]),
            default => response()->json(['message' => 'Unknown admin resource.'], 404),
        };
    }

    public function store(Request $request)
    {
        return match ($this->resourceName($request)) {
            'users' => $this->storeUser($request),
            'drivers' => $this->storeDriver($request),
            'vehicles' => $this->storeVehicle($request),
            'work-orders', 'work_orders' => $this->storeWorkOrder($request),
            default => response()->json(['message' => 'Create is not supported for this resource.'], 404),
        };
    }

    public function show(string $id)
    {
        $request = request();

        return match ($this->resourceName($request)) {
            'users' => response()->json(['user' => User::query()->with('driver')->findOrFail($id)]),
            'drivers' => response()->json(['driver' => Driver::query()->with(['user', 'photo'])->findOrFail($id)]),
            'vehicles' => response()->json(['vehicle' => Vehicle::query()->with('photo')->findOrFail($id)]),
            'work-orders', 'work_orders' => response()->json([
                'work_order' => WorkOrder::query()->with(['driver', 'vehicle', 'creator', 'waybill'])->findOrFail($id),
            ]),
            default => response()->json(['message' => 'Show is not supported for this resource.'], 404),
        };
    }

    public function update(Request $request, string $id)
    {
        return match ($this->resourceName($request)) {
            'users' => $this->updateUser($request, (int) $id),
            'drivers' => $this->updateDriver($request, (int) $id),
            'vehicles' => $this->updateVehicle($request, (int) $id),
            'work-orders', 'work_orders' => $this->updateWorkOrder($request, (int) $id),
            default => response()->json(['message' => 'Update is not supported for this resource.'], 404),
        };
    }

    public function destroy(string $id)
    {
        $request = request();

        return match ($this->resourceName($request)) {
            'users' => $this->deactivateUser($request, (int) $id),
            'drivers' => $this->deactivateDriver($request, (int) $id),
            'vehicles' => $this->deactivateVehicle($request, (int) $id),
            'work-orders', 'work_orders' => $this->cancelWorkOrder($request, (int) $id),
            default => response()->json(['message' => 'Delete is not supported for this resource.'], 404),
        };
    }

    public function changePassword(Request $request, string $id)
    {
        $payload = $request->validate([
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        if ($this->resourceName($request) === 'drivers') {
            $driver = Driver::query()->with('user')->findOrFail($id);
            $driver->user->update(['password' => Hash::make($payload['password'])]);
            $this->audit($request, 'driver.password_changed', 'drivers', $driver->id);

            return response()->json(['driver' => $driver->load('user')]);
        }

        $user = User::query()->findOrFail($id);
        $user->update(['password' => Hash::make($payload['password'])]);
        $this->audit($request, 'user.password_changed', 'users', $user->id);

        return response()->json(['user' => $user]);
    }

    private function usersQuery(Request $request): Builder
    {
        return User::query()
            ->with('driver')
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($q) {
                    $query->where('full_name', 'ilike', "%{$q}%")
                        ->orWhere('login', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%");
                });
            })
            ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('is_active'), fn (Builder $query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest('id');
    }

    private function driversQuery(Request $request): Builder
    {
        return Driver::query()
            ->with(['user', 'photo'])
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($q) {
                    $query->where('full_name', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%")
                        ->orWhere('license_number', 'ilike', "%{$q}%")
                        ->orWhereHas('user', fn (Builder $user) => $user->where('login', 'ilike', "%{$q}%"));
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id');
    }

    private function vehiclesQuery(Request $request): Builder
    {
        return Vehicle::query()
            ->with('photo')
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($q) {
                    $query->where('plate_number', 'ilike', "%{$q}%")
                        ->orWhere('brand', 'ilike', "%{$q}%")
                        ->orWhere('model', 'ilike', "%{$q}%")
                        ->orWhere('vin', 'ilike', "%{$q}%");
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('fuel_type'), fn (Builder $query) => $query->where('fuel_type', $request->string('fuel_type')->toString()))
            ->latest('id');
    }

    private function workOrdersQuery(Request $request): Builder
    {
        return WorkOrder::query()
            ->with(['driver.user', 'vehicle', 'creator', 'waybill'])
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($q) {
                    $query->where('route_name', 'ilike', "%{$q}%")
                        ->orWhere('dispatcher_comment', 'ilike', "%{$q}%")
                        ->orWhereHas('driver', fn (Builder $driver) => $driver->where('full_name', 'ilike', "%{$q}%"))
                        ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('plate_number', 'ilike', "%{$q}%"));
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date'), fn (Builder $query) => $query->whereDate('date', $request->date('date')))
            ->when($request->filled('driver_id'), fn (Builder $query) => $query->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('vehicle_id'), fn (Builder $query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->latest('date')
            ->latest('id');
    }

    private function fuelLogsQuery(Request $request): Builder
    {
        return FuelLog::query()
            ->with(['waybill', 'vehicle', 'driver'])
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($q) {
                    $query->where('comment', 'ilike', "%{$q}%")
                        ->orWhereHas('driver', fn (Builder $driver) => $driver->where('full_name', 'ilike', "%{$q}%"))
                        ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('plate_number', 'ilike', "%{$q}%"));
                });
            })
            ->when($request->filled('fuel_type'), fn (Builder $query) => $query->where('fuel_type', $request->string('fuel_type')->toString()))
            ->when($request->filled('vehicle_id'), fn (Builder $query) => $query->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('driver_id'), fn (Builder $query) => $query->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('fueled_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('fueled_at', '<=', $request->date('date_to')))
            ->latest('fueled_at')
            ->latest('id');
    }

    private function auditLogsQuery(Request $request): Builder
    {
        return AuditLog::query()
            ->with('user')
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function (Builder $query) use ($q) {
                    $query->where('action', 'ilike', "%{$q}%")
                        ->orWhere('entity_type', 'ilike', "%{$q}%")
                        ->orWhereHas('user', fn (Builder $user) => $user->where('login', 'ilike', "%{$q}%"));
                });
            })
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest('created_at')
            ->latest('id');
    }

    private function storeUser(Request $request)
    {
        $payload = $request->validate([
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'dispatcher', 'medic', 'mechanic'])],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->create([
            ...$payload,
            'password' => Hash::make($payload['password']),
            'is_active' => $payload['is_active'] ?? true,
        ]);

        $this->audit($request, 'user.created', 'users', $user->id, null, $user->toArray());

        return response()->json(['user' => $user], 201);
    }

    private function storeDriver(Request $request)
    {
        $payload = $request->validate([
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255', 'unique:drivers,license_number'],
            'license_category' => ['required', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blocked'])],
            'note' => ['nullable', 'string'],
        ]);

        $driver = DB::transaction(function () use ($payload) {
            $user = User::query()->create([
                'login' => $payload['login'],
                'password' => Hash::make($payload['password'] ?? 'driver123'),
                'role' => 'driver',
                'full_name' => $payload['full_name'],
                'phone' => $payload['phone'] ?? null,
                'is_active' => true,
            ]);

            return Driver::query()->create([
                'user_id' => $user->id,
                'full_name' => $payload['full_name'],
                'phone' => $payload['phone'] ?? null,
                'license_number' => $payload['license_number'],
                'license_category' => $payload['license_category'],
                'status' => $payload['status'] ?? 'active',
                'note' => $payload['note'] ?? null,
            ])->load('user');
        });

        $this->audit($request, 'driver.created', 'drivers', $driver->id, null, $driver->toArray());

        return response()->json(['driver' => $driver], 201);
    }

    private function storeVehicle(Request $request)
    {
        $payload = $request->validate([
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:255', 'unique:vehicles,plate_number'],
            'vin' => ['nullable', 'string', 'max:255', 'unique:vehicles,vin'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'fuel_type' => ['required', Rule::in(['petrol', 'gas', 'diesel'])],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['available', 'on_line', 'maintenance', 'inactive'])],
            'note' => ['nullable', 'string'],
        ]);

        $vehicle = Vehicle::query()->create([
            ...$payload,
            'current_mileage' => $payload['current_mileage'] ?? 0,
            'status' => $payload['status'] ?? 'available',
        ]);

        $this->audit($request, 'vehicle.created', 'vehicles', $vehicle->id, null, $vehicle->toArray());

        return response()->json(['vehicle' => $vehicle], 201);
    }

    private function storeWorkOrder(Request $request)
    {
        $payload = $request->validate([
            'date' => ['required', 'date'],
            'shift' => ['required', 'string', 'max:50'],
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'route_name' => ['required', 'string', 'max:255'],
            'dispatcher_comment' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['planned', 'active', 'completed', 'cancelled'])],
        ]);

        $workOrder = WorkOrder::query()->create([
            ...$payload,
            'status' => $payload['status'] ?? 'planned',
            'created_by' => $request->user()->id,
        ])->load(['driver', 'vehicle', 'creator']);

        $this->audit($request, 'work_order.created', 'work_orders', $workOrder->id, null, $workOrder->toArray());

        return response()->json(['work_order' => $workOrder], 201);
    }

    private function updateUser(Request $request, int $id)
    {
        $user = User::query()->findOrFail($id);
        $old = $user->toArray();

        $payload = $request->validate([
            'login' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'login')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'role' => ['sometimes', 'required', Rule::in(['admin', 'dispatcher', 'medic', 'mechanic'])],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        } else {
            unset($payload['password']);
        }

        $user->update($payload);
        $this->audit($request, 'user.updated', 'users', $user->id, $old, $user->fresh()->toArray());

        return response()->json(['user' => $user->fresh('driver')]);
    }

    private function updateDriver(Request $request, int $id)
    {
        $driver = Driver::query()->with('user')->findOrFail($id);
        $old = $driver->toArray();

        $payload = $request->validate([
            'login' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'login')->ignore($driver->user_id)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'license_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('drivers', 'license_number')->ignore($driver->id)],
            'license_category' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'blocked'])],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($driver, $payload) {
            $driverPayload = collect($payload)->only([
                'full_name',
                'phone',
                'license_number',
                'license_category',
                'status',
                'note',
            ])->all();

            $userPayload = collect($payload)->only(['login', 'full_name', 'phone'])->all();

            if (! empty($payload['password'])) {
                $userPayload['password'] = Hash::make($payload['password']);
            }

            $driver->update($driverPayload);
            $driver->user->update($userPayload);
        });

        $driver = $driver->fresh('user');
        $this->audit($request, 'driver.updated', 'drivers', $driver->id, $old, $driver->toArray());

        return response()->json(['driver' => $driver]);
    }

    private function updateVehicle(Request $request, int $id)
    {
        $vehicle = Vehicle::query()->findOrFail($id);
        $old = $vehicle->toArray();

        $payload = $request->validate([
            'brand' => ['sometimes', 'required', 'string', 'max:255'],
            'model' => ['sometimes', 'required', 'string', 'max:255'],
            'plate_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('vehicles', 'plate_number')->ignore($vehicle->id)],
            'vin' => ['nullable', 'string', 'max:255', Rule::unique('vehicles', 'vin')->ignore($vehicle->id)],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'fuel_type' => ['sometimes', 'required', Rule::in(['petrol', 'gas', 'diesel'])],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['available', 'on_line', 'maintenance', 'inactive'])],
            'note' => ['nullable', 'string'],
        ]);

        $vehicle->update($payload);
        $this->audit($request, 'vehicle.updated', 'vehicles', $vehicle->id, $old, $vehicle->fresh()->toArray());

        return response()->json(['vehicle' => $vehicle->fresh('photo')]);
    }

    private function updateWorkOrder(Request $request, int $id)
    {
        $workOrder = WorkOrder::query()->findOrFail($id);
        $old = $workOrder->toArray();

        $payload = $request->validate([
            'date' => ['sometimes', 'required', 'date'],
            'shift' => ['sometimes', 'required', 'string', 'max:50'],
            'driver_id' => ['sometimes', 'required', 'integer', 'exists:drivers,id'],
            'vehicle_id' => ['sometimes', 'required', 'integer', 'exists:vehicles,id'],
            'route_name' => ['sometimes', 'required', 'string', 'max:255'],
            'dispatcher_comment' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['planned', 'active', 'completed', 'cancelled'])],
        ]);

        $workOrder->update($payload);
        $this->audit($request, 'work_order.updated', 'work_orders', $workOrder->id, $old, $workOrder->fresh()->toArray());

        return response()->json(['work_order' => $workOrder->fresh(['driver', 'vehicle', 'creator', 'waybill'])]);
    }

    private function deactivateUser(Request $request, int $id)
    {
        $user = User::query()->findOrFail($id);
        $old = $user->toArray();
        $user->update(['is_active' => false]);
        $this->audit($request, 'user.deactivated', 'users', $user->id, $old, $user->fresh()->toArray());

        return response()->json(['user' => $user->fresh()], 202);
    }

    private function deactivateDriver(Request $request, int $id)
    {
        $driver = Driver::query()->with('user')->findOrFail($id);
        $old = $driver->toArray();
        $driver->update(['status' => 'inactive']);
        $driver->user->update(['is_active' => false]);
        $this->audit($request, 'driver.deactivated', 'drivers', $driver->id, $old, $driver->fresh('user')->toArray());

        return response()->json(['driver' => $driver->fresh('user')], 202);
    }

    private function deactivateVehicle(Request $request, int $id)
    {
        $vehicle = Vehicle::query()->findOrFail($id);
        $old = $vehicle->toArray();
        $vehicle->update(['status' => 'inactive']);
        $this->audit($request, 'vehicle.deactivated', 'vehicles', $vehicle->id, $old, $vehicle->fresh()->toArray());

        return response()->json(['vehicle' => $vehicle->fresh()], 202);
    }

    private function cancelWorkOrder(Request $request, int $id)
    {
        $workOrder = WorkOrder::query()->findOrFail($id);
        $old = $workOrder->toArray();
        $workOrder->update(['status' => 'cancelled']);
        $this->audit($request, 'work_order.cancelled', 'work_orders', $workOrder->id, $old, $workOrder->fresh()->toArray());

        return response()->json(['work_order' => $workOrder->fresh(['driver', 'vehicle'])], 202);
    }

    private function audit(Request $request, string $action, ?string $entityType = null, ?int $entityId = null, ?array $old = null, ?array $new = null): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
        ]);
    }
}
