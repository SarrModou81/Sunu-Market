<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Catégories initiales définies au cahier des charges (section 5).
     */
    public function run(): void
    {
        $categories = [
            'Électronique' => ['Téléphones', 'Ordinateurs', 'Tablettes', 'Accessoires', 'TV & Audio'],
            'Mode' => ['Vêtements homme', 'Vêtements femme', 'Chaussures', 'Sacs', 'Montres & Bijoux'],
            'Maison' => ['Meubles', 'Électroménager', 'Décoration', 'Jardin', 'Cuisine'],
            'Véhicules' => ['Voitures', 'Motos', 'Pièces détachées', 'Location'],
            'Informatique' => ['Ordinateurs portables', 'Composants', 'Périphériques', 'Logiciels'],
            'Beauté' => ['Cosmétiques', 'Parfums', 'Soins capillaires', 'Bien-être'],
            'Loisirs' => ['Sport', 'Livres', 'Jeux & Jouets', 'Musique', 'Instruments'],
            'Autres' => [],
        ];

        $position = 0;
        foreach ($categories as $name => $subcategories) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'position' => $position++, 'is_active' => true]
            );

            $subPosition = 0;
            foreach ($subcategories as $subName) {
                $category->subcategories()->updateOrCreate(
                    ['slug' => Str::slug($subName)],
                    ['name' => $subName, 'position' => $subPosition++, 'is_active' => true]
                );
            }
        }
    }
}
