<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // COMIDAS
        $comidas = MenuCategory::create(['name' => 'Comidas']);
        $comidasItems = [
            ['name' => 'Pachamanca', 'price' => 25],
            ['name' => '1/4 Picante de cuy', 'price' => 25],
            ['name' => '1/2 Picante de cuy', 'price' => 30],
            ['name' => 'Caldo de gallina', 'price' => 20],
            ['name' => 'Caldo de gallina SOLO', 'price' => 10],
            ['name' => 'Arroz con pato', 'price' => 25],
            ['name' => 'Chicharrón', 'price' => 25],
            ['name' => 'Mixto 1', 'price' => 25],
            ['name' => 'Mixto 2', 'price' => 25],
            ['name' => 'Mixto 3', 'price' => 25],
            ['name' => 'Mixto 4', 'price' => 25],
            ['name' => 'SOLO Cecina', 'price' => 25],
            ['name' => 'SOLO Chorizo', 'price' => 25],
        ];

        foreach ($comidasItems as $item) {
            MenuItem::create([
                'menu_category_id' => $comidas->id,
                'name' => $item['name'],
                'price' => $item['price'],
            ]);
        }

        // BEBIDAS
        $bebidas = MenuCategory::create(['name' => 'Bebidas']);
        $bebidasItems = [
            ['name' => 'Jarra de Chicha', 'price' => 10],
            ['name' => '1/2 Jarra de Chicha', 'price' => 5],
            ['name' => 'Vaso de chicha', 'price' => 3],
            ['name' => 'Inca cola personal Vidrio', 'price' => 2.5],
            ['name' => 'Inca cola personal', 'price' => 3.5],
            ['name' => 'Inca cola 1L', 'price' => 7],
            ['name' => 'Inca cola 1.5L', 'price' => 8],
            ['name' => 'Coca cola personal Vidrio', 'price' => 2.5],
            ['name' => 'Coca cola personal', 'price' => 3.5],
            ['name' => 'Coca cola 1L', 'price' => 8],
            ['name' => 'Coca cola 1.5L', 'price' => 9],
            ['name' => 'Guaraná personal', 'price' => 2],
            ['name' => 'Guaraná 2L', 'price' => 8],
            ['name' => 'Agua cielo', 'price' => 2],
            ['name' => 'Agua Belen', 'price' => 1.5],
            ['name' => 'Sporade', 'price' => 3],
            ['name' => 'Cerveza San Juan', 'price' => 7.5],
            ['name' => 'Cerveza Cristal', 'price' => 8],
            ['name' => 'Cerveza Cuzqueña', 'price' => 10],
        ];

        foreach ($bebidasItems as $item) {
            MenuItem::create([
                'menu_category_id' => $bebidas->id,
                'name' => $item['name'],
                'price' => $item['price'],
            ]);
        }

        // EXTRAS
        $extras = MenuCategory::create(['name' => 'Extras']);
        $extrasItems = [
            ['name' => 'Porciones', 'price' => 3],
            ['name' => 'Tapper', 'price' => 1],
            ['name' => 'Dulces', 'price' => 1],
            ['name' => 'Helados', 'price' => 2],
        ];

        foreach ($extrasItems as $item) {
            MenuItem::create([
                'menu_category_id' => $extras->id,
                'name' => $item['name'],
                'price' => $item['price'],
            ]);
        }
    }
}
