<?php

namespace Database\Seeders;

use App\Models\Locality;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LocalitySeeder extends Seeder
{
    /**
     * Ciudades y departamentos por provincia (slug => nombres).
     *
     * @var array<string, list<string>>
     */
    private const LOCALITIES = [
        'mendoza' => [
            'Ciudad de Mendoza', 'Godoy Cruz', 'Guaymallén', 'Maipú', 'Luján de Cuyo',
            'Las Heras', 'Lavalle', 'Junín', 'San Martín', 'Rivadavia',
            'Santa Rosa', 'La Paz', 'Tupungato', 'Tunuyán', 'San Carlos',
            'San Rafael', 'General Alvear', 'Malargüe',
        ],
        'san-juan' => [
            'Capital', 'Rawson', 'Rivadavia', 'Chimbas', 'Santa Lucía',
            'Pocito', 'Sarmiento', 'Albardón', 'Angaco', 'Caucete',
            'Jáchal', 'Iglesia', 'Calingasta', 'Valle Fértil', '25 de Mayo',
            '9 de Julio', 'Ullum', 'Zonda', 'San Martín',
        ],
        'san-luis' => [
            'Ciudad de San Luis', 'Villa Mercedes', 'Merlo', 'La Punta', 'Juana Koslay',
            'El Volcán', 'Potrero de los Funes', 'La Toma', 'Santa Rosa del Conlara',
            'Quines', 'San Francisco del Monte de Oro', 'Villa de la Quebrada',
            'Nogolí', 'Tilisarao', 'Concarán', 'Buena Esperanza', 'Nueva Galia',
        ],
    ];

    public function run(): void
    {
        foreach (self::LOCALITIES as $provinceSlug => $names) {
            $province = Province::query()->firstOrCreate(
                ['slug' => $provinceSlug],
                ['name' => Str::headline($provinceSlug), 'is_active' => true],
            );

            foreach ($names as $name) {
                Locality::query()->updateOrCreate(
                    ['province_id' => $province->id, 'slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true],
                );
            }
        }
    }
}
