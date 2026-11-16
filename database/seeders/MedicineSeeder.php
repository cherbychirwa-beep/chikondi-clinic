<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = [
            ['Paracetamol', 'Paracetamol', '500mg', 'Tablet'],
            ['Ibuprofen', 'Ibuprofen', '400mg', 'Tablet'],
            ['Amoxicillin', 'Amoxicillin', '500mg', 'Capsule'],
            ['Amoxicillin Suspension', 'Amoxicillin', '125mg/5ml', 'Syrup'],
            ['Cotrimoxazole', 'Sulfamethoxazole/Trimethoprim', '480mg', 'Tablet'],
            ['Artemether-Lumefantrine', 'Artemether/Lumefantrine', '20/120mg', 'Tablet'],
            ['Metronidazole', 'Metronidazole', '400mg', 'Tablet'],
            ['Ciprofloxacin', 'Ciprofloxacin', '500mg', 'Tablet'],
            ['Oral Rehydration Salts', 'ORS', '20.5g', 'Sachet'],
            ['Zinc Sulphate', 'Zinc', '20mg', 'Dispersible tablet'],
            ['Salbutamol Inhaler', 'Salbutamol', '100mcg', 'Inhaler'],
            ['Loratadine', 'Loratadine', '10mg', 'Tablet'],
            ['Omeprazole', 'Omeprazole', '20mg', 'Capsule'],
            ['Amlodipine', 'Amlodipine', '5mg', 'Tablet'],
            ['Metformin', 'Metformin', '500mg', 'Tablet'],
            ['Ferrous Sulphate', 'Iron', '200mg', 'Tablet'],
            ['Albendazole', 'Albendazole', '400mg', 'Tablet'],
            ['Vitamin C', 'Ascorbic acid', '100mg', 'Tablet'],
        ];

        foreach ($catalogue as [$name, $generic, $strength, $form]) {
            Medicine::firstOrCreate(
                ['name' => $name, 'strength' => $strength],
                ['generic_name' => $generic, 'dosage_form' => $form, 'is_active' => true],
            );
        }
    }
}
