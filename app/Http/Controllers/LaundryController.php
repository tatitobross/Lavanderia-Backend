<?php

namespace App\Http\Controllers;

use App\Models\{Customer, GarmentType, LaundryOrder, LaundryService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaundryController extends Controller
{
    private function listing(Request $request, string $model, array $with = [])
    {
        $query = $model::with($with);
        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($query) use ($search, $model) {
                foreach ((new $model)->getFillable() as $field) $query->orWhere($field, 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->string('status')->value());
        return response()->json($query->latest()->paginate($request->integer('per_page', 30)));
    }

    private function persist(Request $request, ?int $id, string $model, array $rules, array $with = [])
    {
        $data = $request->validate($rules);
        $record = $id ? $model::findOrFail($id) : new $model;
        $record->fill($data)->save();
        return response()->json(['message' => $id ? 'Registro actualizado.' : 'Registro creado.', 'data' => $record->load($with)], $id ? 200 : 201);
    }

    public function customers(Request $request) { return $this->listing($request, Customer::class); }
    public function showCustomer(Customer $customer) { return response()->json(['data' => $customer]); }
    public function storeCustomer(Request $request) { return $this->persist($request, null, Customer::class, $this->customerRules()); }
    public function updateCustomer(Request $request, Customer $customer) { return $this->persist($request, $customer->id, Customer::class, $this->customerRules($customer)); }
    public function destroyCustomer(Customer $customer) { if ($customer->orders()->exists()) return response()->json(['message'=>'No se puede eliminar un cliente con órdenes registradas.'],409); $customer->delete(); return response()->noContent(); }
    private function customerRules(?Customer $customer = null): array { return ['name'=>'required|string|max:150','phone'=>'nullable|string|max:30','email'=>['nullable','email','max:150',Rule::unique('customers','email')->ignore($customer)],'address'=>'nullable|string|max:255','status'=>'required|in:active,inactive']; }

    public function garmentTypes(Request $request) { return $this->listing($request, GarmentType::class); }
    public function showGarmentType(GarmentType $garmentType) { return response()->json(['data' => $garmentType]); }
    public function storeGarmentType(Request $request) { return $this->persist($request, null, GarmentType::class, $this->garmentRules()); }
    public function updateGarmentType(Request $request, GarmentType $garmentType) { return $this->persist($request, $garmentType->id, GarmentType::class, $this->garmentRules($garmentType)); }
    public function destroyGarmentType(GarmentType $garmentType) { if (LaundryOrder::where('garment_type_id',$garmentType->id)->exists()) return response()->json(['message'=>'No se puede eliminar un tipo de prenda usado en órdenes.'],409); $garmentType->delete(); return response()->noContent(); }
    private function garmentRules(?GarmentType $type = null): array { return ['name'=>['required','string','max:100',Rule::unique('garment_types','name')->ignore($type)],'description'=>'nullable|string|max:255']; }

    public function services(Request $request) { return $this->listing($request, LaundryService::class); }
    public function showService(LaundryService $service) { return response()->json(['data' => $service]); }
    public function storeService(Request $request) { return $this->persist($request, null, LaundryService::class, $this->serviceRules()); }
    public function updateService(Request $request, LaundryService $service) { return $this->persist($request, $service->id, LaundryService::class, $this->serviceRules($service)); }
    public function destroyService(LaundryService $service) { if (LaundryOrder::where('laundry_service_id',$service->id)->exists()) return response()->json(['message'=>'No se puede eliminar un servicio usado en órdenes.'],409); $service->delete(); return response()->noContent(); }
    private function serviceRules(?LaundryService $service = null): array { return ['name'=>['required','string','max:100',Rule::unique('laundry_services','name')->ignore($service)],'pricing_mode'=>'required|in:weight,unit','unit_price'=>'required|numeric|min:0.01','estimated_days'=>'required|integer|min:0|max:30','description'=>'nullable|string|max:255','status'=>'required|in:active,inactive']; }

    public function orders(Request $request) { return $this->listing($request, LaundryOrder::class, ['customer','garmentType','laundryService','receiver']); }
    public function showOrder(LaundryOrder $order) { return response()->json(['data'=>$order->load('customer','garmentType','laundryService','receiver')]); }
    public function storeOrder(Request $request) { return $this->persistOrder($request); }
    public function updateOrder(Request $request, LaundryOrder $order) { return $this->persistOrder($request, $order); }
    public function destroyOrder(LaundryOrder $order) { if (in_array($order->status,['ready','delivered'],true)) return response()->json(['message'=>'No se puede eliminar una orden lista o entregada.'],422); $order->delete(); return response()->noContent(); }
    private function persistOrder(Request $request, ?LaundryOrder $order = null) {
        $data = $request->validate(['customer_id'=>'required|exists:customers,id','garment_type_id'=>'required|exists:garment_types,id','laundry_service_id'=>'required|exists:laundry_services,id','quantity'=>'required|numeric|min:0.01|max:9999','status'=>'sometimes|in:received,processing,ready,delivered,cancelled','notes'=>'nullable|string|max:1000']);
        $service = LaundryService::findOrFail($data['laundry_service_id']);
        if ($service->status !== 'active') return response()->json(['message'=>'El servicio seleccionado está inactivo.'],422);
        $data['unit_price'] = $service->unit_price; $data['total'] = round($service->unit_price * $data['quantity'],2); $data['estimated_at'] = now()->addDays($service->estimated_days); $data['received_by'] = $order?->received_by ?? $request->user()->id;
        if (($data['status'] ?? '') === 'delivered') $data['completed_at'] = now();
        $record = $order ?? new LaundryOrder; $record->fill($data)->save();
        $record->load('customer','garmentType','laundryService','receiver');
        $response = ['message'=>$order ? 'Orden actualizada.' : 'Orden registrada.','data'=>$record];
        if (!$order) $response['invoice'] = $this->invoice($record);
        return response()->json($response, $order ? 200 : 201);
    }
    private function invoice(LaundryOrder $order): array {
        return [
            'number' => 'LAV-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'issued_at' => $order->created_at?->toIso8601String(),
            'customer' => ['name' => $order->customer?->name, 'phone' => $order->customer?->phone],
            'garment_type' => ['name' => $order->garmentType?->name],
            'service' => ['name' => $order->laundryService?->name],
            'quantity' => $order->quantity,
            'unit_price' => $order->unit_price,
            'total' => $order->total,
            'estimated_at' => $order->estimated_at?->toIso8601String(),
        ];
    }
    public function advanceOrder(LaundryOrder $order) { $next=['received'=>'processing','processing'=>'ready','ready'=>'delivered']; if (!isset($next[$order->status])) return response()->json(['message'=>'La orden no puede avanzar desde su estado actual.'],422); $order->status=$next[$order->status]; if($order->status==='delivered') $order->completed_at=now(); $order->save(); return response()->json(['message'=>'Estado actualizado.','data'=>$order]); }
    public function dashboard() { return response()->json(['customers'=>Customer::where('status','active')->count(),'orders_today'=>LaundryOrder::whereDate('created_at',today())->count(),'processing'=>LaundryOrder::whereIn('status',['received','processing'])->count(),'ready'=>LaundryOrder::where('status','ready')->count()]); }
}
