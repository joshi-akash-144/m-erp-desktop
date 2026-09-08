<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $menu = config('company_menu.main');

        foreach ($menu as $index => $item) {
            Module::updateOrCreate(
                ['name' => $item['title']],
                [
                    'title'     => $item['title'],
                    'icon'      => $item['icon'] ?? null,
                    'color'     => $item['color'] ?? null,
                    'bg'        => $item['bg'] ?? null,
                    'order'     => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
