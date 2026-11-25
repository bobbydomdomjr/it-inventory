<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetCategoryCatalogSeeder extends Seeder
{
    /**
     * Add the recommended starter categories without overwriting any
     * administrator-managed category values or existing asset records.
     */
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('asset_categories') || ! DB::getSchemaBuilder()->hasTable('offices') || ! DB::getSchemaBuilder()->hasColumn('asset_categories', 'default_min_stock_level')) {
            return;
        }

        $catalog = [
            ['COMP', 'Computer Equipment', 'asset', 'Desktop, laptop, workstation'],
            ['PERI', 'Computer Peripherals', 'asset', 'Mouse, keyboard, monitor'],
            ['NET', 'Network Equipment', 'asset', 'Router, switch, firewall'],
            ['COMM', 'Communication Equipment', 'asset', 'IP phone, telephone, radio'],
            ['PRINT', 'Printing Equipment', 'asset', 'Printer, scanner, photocopier'],
            ['SERV', 'Server Equipment', 'asset', 'Rack server, NAS, storage server'],
            ['POWER', 'Power Equipment', 'asset', 'UPS, AVR, power supply'],
            ['STOR', 'Storage Devices', 'asset', 'External HDD, SSD, USB drive'],
            ['AV', 'Audio-Visual Equipment', 'asset', 'Projector, webcam, speakers'],
            ['MOBILE', 'Mobile Devices', 'asset', 'Smartphone, tablet'],
            ['ACC', 'Accessories', 'asset', 'Docking station, adapters, cables'],
            ['SOFT', 'Software', 'software', 'Operating systems, applications'],
            ['CONS', 'IT Consumables', 'consumable', 'Ink, toner, cables, batteries'],
        ];

        $offices = DB::table('offices')->where('is_active', true)->whereNull('deleted_at')->orderBy('id')->get();
        foreach ($offices as $office) {
            foreach ($catalog as [$baseCode, $name, $type, $description]) {
                $existing = DB::table('asset_categories')
                    ->where('office_id', $office->id)
                    ->where('name', $name)
                    ->where('type', $type)
                    ->whereNull('deleted_at')
                    ->first();
                if ($existing) {
                    if (empty($existing->description)) {
                        DB::table('asset_categories')->where('id', $existing->id)->update(['description' => $description, 'updated_at' => now()]);
                    }
                    continue;
                }

                $code = $baseCode;
                $codeOwner = DB::table('asset_categories')->where('code', $code)->first();
                if ($codeOwner && (int) $codeOwner->office_id !== (int) $office->id) {
                    $officePrefix = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', (string) ($office->code ?: 'OFFICE')));
                    $code = substr($officePrefix.'-'.$baseCode, 0, 30);
                }
                $suffix = 2;
                $candidate = $code;
                while (DB::table('asset_categories')->where('code', $candidate)->exists()) {
                    $candidate = substr($code, 0, 26).'-'.$suffix;
                    $suffix++;
                }

                DB::table('asset_categories')->insert([
                    'office_id' => $office->id,
                    'code' => $candidate,
                    'name' => $name,
                    'description' => $description,
                    'type' => $type,
                    'parent_id' => null,
                    'default_useful_life_months' => null,
                    'useful_life_months' => null,
                    'depreciation_method' => 'not_configured',
                    'salvage_percent' => 0,
                    'default_min_stock_level' => 0,
                    'custom_fields' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                    'created_by' => null,
                    'updated_by' => null,
                ]);
            }
        }
    }
}
