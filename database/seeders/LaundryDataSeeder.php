<?php

namespace Database\Seeders;

use App\Models\{Customer, GarmentType, LaundryOrder, LaundryService, User};
use Illuminate\Database\Seeder;

class LaundryDataSeeder extends Seeder
{
    public function run(): void
    {
        $customers = collect([
            ['name'=>'María López','phone'=>'9999-0101','email'=>'maria@lavanderia.test','address'=>'Colonia Centro','status'=>'active'],
            ['name'=>'Carlos Méndez','phone'=>'9999-0102','email'=>'carlos@lavanderia.test','address'=>'Barrio La Granja','status'=>'active'],
        ])->map(fn ($data) => Customer::updateOrCreate(['email'=>$data['email']], $data));
        $garments = collect([
            ['name'=>'Camisa','description'=>'Prenda superior de vestir.'],
            ['name'=>'Pantalón','description'=>'Prenda inferior de vestir.'],
            ['name'=>'Edredón','description'=>'Ropa de cama de gran tamaño.'],
        ])->map(fn ($data) => GarmentType::updateOrCreate(['name'=>$data['name']], $data));
        $services = collect([
            ['name'=>'Lavado por libra','pricing_mode'=>'weight','unit_price'=>35,'estimated_days'=>2,'description'=>'Lavado y secado por peso.','status'=>'active'],
            ['name'=>'Lavado y planchado','pricing_mode'=>'unit','unit_price'=>55,'estimated_days'=>3,'description'=>'Servicio por unidad con planchado.','status'=>'active'],
            ['name'=>'Lavado de edredón','pricing_mode'=>'unit','unit_price'=>180,'estimated_days'=>4,'description'=>'Lavado especial de ropa de cama.','status'=>'active'],
        ])->map(fn ($data) => LaundryService::updateOrCreate(['name'=>$data['name']], $data));
        $operator = User::where('username','operador')->first();
        LaundryOrder::updateOrCreate(['notes'=>'Entregar después de las 4:00 p. m.'], [
            'customer_id'=>$customers[0]->id,'garment_type_id'=>$garments[0]->id,'laundry_service_id'=>$services[1]->id,
            'received_by'=>$operator?->id,'quantity'=>3,'unit_price'=>55,'total'=>165,'estimated_at'=>now()->addDays(3),'status'=>'processing',
        ]);
    }
}
