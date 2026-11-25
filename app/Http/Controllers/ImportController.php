<?php

namespace App\Http\Controllers;

use App\Models\{Asset, AssetCategory, Department, InventoryItem, Location, Supplier};
use App\Services\{AssetHistoryWriter, AssetTagService, StockLedgerService};
use App\Models\SystemSetting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function template(string $resource)
    {
        abort_unless(in_array($resource, ['assets', 'consumables'], true), 404);
        $headers = $resource === 'assets'
            ? ['asset_tag', 'name', 'serial_number', 'category_code', 'brand', 'model', 'specifications', 'purchase_date', 'purchase_cost', 'salvage_value', 'useful_life_months', 'depreciation_method', 'department_code', 'location_name', 'condition']
            : ['sku', 'name', 'description', 'unit', 'category_code', 'quantity_on_hand', 'reorder_level', 'unit_cost', 'supplier_name'];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fclose($out);
        }, "{$resource}-import-template.csv", ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, string $resource)
    {
        abort_unless(in_array($resource, ['assets', 'consumables'], true), 404);
        abort_unless($request->user()->hasPermission(($resource === 'assets' ? 'assets' : 'consumables').'.manage'), 403);
        $settings = SystemSetting::query()->whereIn('key', [
            'import_max_size_mb', 'import_max_rows', 'automatic_asset_numbering', 'default_salvage_percent',
            'default_useful_life_months', 'default_depreciation_method', 'inventory_units', 'low_stock_default',
        ])->get()->mapWithKeys(fn ($setting) => [$setting->key => data_get($setting->value, 'value')]);
        $automaticNumbering = (bool) ($settings->get('automatic_asset_numbering', true));
        $defaultSalvagePercent = (float) ($settings->get('default_salvage_percent', 0));
        $defaultUsefulLife = (int) ($settings->get('default_useful_life_months', 48));
        $defaultDepreciationMethod = (string) ($settings->get('default_depreciation_method', 'straight_line'));
        $allowedUnits = $settings->get('inventory_units', ['piece', 'unit', 'box', 'pack', 'set']);
        $allowedUnits = is_array($allowedUnits) && $allowedUnits ? array_values($allowedUnits) : ['piece', 'unit', 'box', 'pack', 'set'];
        $defaultReorderLevel = (int) ($settings->get('low_stock_default', 5));
        $maxImportMb = (int) ($settings->get('import_max_size_mb', 10));
        $maxImportMb = max(1, min(50, $maxImportMb));
        $maxImportRows = max(100, min(10000, (int) $settings->get('import_max_rows', 1000)));
        $data = $request->validate([
            'file' => ['required','file','mimes:csv,txt,xlsx','max:'.($maxImportMb * 1024)],
        ]);
        $uploaded = $data['file'];
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $rows = [];

        if ($extension === 'xlsx') {
            abort_unless(
                class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)
                && class_exists(\XMLReader::class)
                && class_exists(\XMLWriter::class)
                && class_exists(\ZipArchive::class),
                503,
                'Excel import requires the PhpSpreadsheet dependency and PHP XMLReader, XMLWriter, and ZIP extensions.'
            );
            $sheetRows = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploaded->getRealPath())
                ->getActiveSheet()->toArray(null, true, true, false);
            if (count($sheetRows) < 1) {
                throw ValidationException::withMessages(['file' => 'The Excel file is empty.']);
            }
            $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($sheetRows));
            foreach ($sheetRows as $offset => $values) {
                if (count(array_filter($values, fn ($value) => $value !== null && $value !== '')) === 0) continue;
                if (count($values) !== count($headers)) {
                    throw ValidationException::withMessages(['file' => 'Invalid column count on Excel row '.($offset + 2).'.']);
                }
                $rows[$offset + 2] = array_combine($headers, $values);
                if (count($rows) > $maxImportRows) {
                    throw ValidationException::withMessages(['file' => "Import is limited to {$maxImportRows} rows per upload by System Settings."]);
                }
            }
        } else {
            $handle = fopen($uploaded->getRealPath(), 'r');
            if (! $handle) {
                throw ValidationException::withMessages(['file' => 'Unable to read the uploaded CSV.']);
            }
            $headers = fgetcsv($handle);
            if (! $headers) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
            }
            $headers = array_map(fn ($value) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), $headers);
            $line = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if (count($values) !== count($headers)) {
                    fclose($handle);
                    throw ValidationException::withMessages(['file' => "Invalid column count on CSV line {$line}."]);
                }
                if (count(array_filter($values, fn ($value) => $value !== null && $value !== '')) === 0) continue;
                $rows[$line] = array_combine($headers, $values);
                if (count($rows) > $maxImportRows) {
                    fclose($handle);
                    throw ValidationException::withMessages(['file' => "Import is limited to {$maxImportRows} rows per upload by System Settings."]);
                }
            }
            fclose($handle);
        }

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers ?? []);
        $required = $resource === 'assets' ? ['name', 'category_code'] : ['sku', 'name', 'unit'];
        foreach ($required as $column) {
            if (! in_array($column, $headers, true)) {
                throw ValidationException::withMessages(['file' => "Import file is missing required column: {$column}"]);
            }
        }

        $errors = [];
        $seen = [];
        foreach ($rows as $line => $row) {
            $rules = $resource === 'assets' ? [
                'asset_tag' => ($automaticNumbering ? 'nullable' : 'required').'|string|max:80',
                'name' => 'required|string|max:255',
                'serial_number' => 'nullable|string|max:255',
                'category_code' => ['required', 'string', 'max:30', Rule::exists('asset_categories', 'code')->where('type', 'asset')->where('is_active', true)],
                'brand' => 'nullable|string|max:255', 'model' => 'nullable|string|max:255',
                'specifications' => 'nullable|string|max:5000', 'purchase_date' => 'nullable|date',
                'purchase_cost' => 'nullable|numeric|min:0', 'salvage_value' => 'nullable|numeric|min:0',
                'useful_life_months' => 'nullable|integer|min:1|max:600',
                'depreciation_method' => 'nullable|in:straight_line,not_configured',
                'department_code' => 'nullable|exists:departments,code', 'location_name' => 'nullable|exists:locations,name',
                'condition' => 'nullable|in:New,Good,Fair,Defective',
            ] : [
                'sku' => ['required', 'string', 'max:80', Rule::unique('inventory_items', 'sku')],
                'category_code' => ['nullable', 'string', 'max:30', Rule::exists('asset_categories', 'code')->where('type', 'consumable')->where('is_active', true)],
                'name' => 'required|string|max:255', 'description' => 'nullable|string|max:5000',
                'unit' => ['required', 'string', 'max:30', Rule::in($allowedUnits)], 'quantity_on_hand' => 'nullable|integer|min:0',
                'reorder_level' => 'nullable|integer|min:0', 'unit_cost' => 'nullable|numeric|min:0',
                'supplier_name' => 'nullable|exists:suppliers,company_name',
            ];
            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "CSV line {$line}: {$message}";
                }
                continue;
            }

            if ($resource === 'assets' && filled($row['asset_tag'] ?? null)) {
                $tag = trim($row['asset_tag']);
                if (Asset::where('asset_tag', $tag)->exists() || isset($seen[strtolower($tag)])) {
                    $errors[] = "CSV line {$line}: Asset tag '{$tag}' is already registered or duplicated in this file.";
                }
                $seen[strtolower($tag)] = true;
            }
            if ($resource === 'consumables') {
                $sku = trim($row['sku']);
                $skuKey = mb_strtolower($sku, 'UTF-8');
                if (isset($seen[$skuKey])) {
                    $errors[] = "CSV line {$line}: SKU '{$sku}' is duplicated in this file (case-insensitive).";
                }
                $seen[$skuKey] = true;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        $created = 0;
        DB::transaction(function () use ($rows, $resource, &$created, $request, $automaticNumbering, $defaultSalvagePercent, $defaultUsefulLife, $defaultDepreciationMethod, $defaultReorderLevel) {
            foreach ($rows as $row) {
                if ($resource === 'assets') {
                    $category = AssetCategory::query()->where('type', 'asset')->where('is_active', true)->where('code', $row['category_code'])->firstOrFail();
                    $department = filled($row['department_code'] ?? null) ? Department::where('code', $row['department_code'])->firstOrFail() : null;
                    $location = filled($row['location_name'] ?? null) ? Location::where('name', $row['location_name'])->firstOrFail() : null;
                    $purchaseCost = (float) ($row['purchase_cost'] ?? 0);
                    $categorySalvagePercent = (float) $category->salvage_percent > 0 ? (float) $category->salvage_percent : $defaultSalvagePercent;
                    $salvageValue = filled($row['salvage_value'] ?? null)
                        ? (float) $row['salvage_value']
                        : round($purchaseCost * $categorySalvagePercent / 100, 2);
                    $asset = Asset::create([
                        'asset_tag' => filled($row['asset_tag'] ?? null) ? trim($row['asset_tag']) : ($automaticNumbering ? app(AssetTagService::class)->next() : null),
                        'name' => $row['name'], 'serial_number' => $row['serial_number'] ?? null,
                        'category_id' => $category->id, 'brand' => $row['brand'] ?? null, 'model' => $row['model'] ?? null,
                        'specifications' => $row['specifications'] ?? null, 'purchase_date' => $row['purchase_date'] ?? null,
                        'purchase_cost' => $purchaseCost, 'salvage_value' => $salvageValue,
                        'useful_life_months' => filled($row['useful_life_months'] ?? null) ? (int) $row['useful_life_months'] : ($category->useful_life_months ?: ($category->default_useful_life_months ?: $defaultUsefulLife)),
                        'depreciation_method' => filled($row['depreciation_method'] ?? null) && $row['depreciation_method'] !== 'not_configured' ? $row['depreciation_method'] : (! in_array($category->depreciation_method, [null, '', 'not_configured'], true) ? $category->depreciation_method : $defaultDepreciationMethod),
                        'department_id' => $department?->id, 'location_id' => $location?->id,
                        'condition' => $row['condition'] ?? 'Good', 'status' => 'available',
                    ]);
                    app(AssetHistoryWriter::class)->record($asset, 'imported', [], $asset->getAttributes());
                    Audit::record('asset_imported', 'assets', Asset::class, $asset->id);
                } else {
                    $supplier = filled($row['supplier_name'] ?? null) ? Supplier::where('company_name', $row['supplier_name'])->firstOrFail() : null;
                    $category = filled($row['category_code'] ?? null)
                        ? AssetCategory::query()->where('type', 'consumable')->where('is_active', true)->where('code', $row['category_code'])->firstOrFail()
                        : null;
                    $openingQuantity = (int) ($row['quantity_on_hand'] ?? 0);
                    $reorderLevel = filled($row['reorder_level'] ?? null)
                        ? (int) $row['reorder_level']
                        : (int) (($category?->default_min_stock_level) ?: $defaultReorderLevel);
                    $item = InventoryItem::create([
                        'sku' => $row['sku'], 'name' => $row['name'], 'description' => $row['description'] ?? null,
                        'category_id' => $category?->id, 'unit' => $row['unit'], 'quantity_on_hand' => 0,
                        'reorder_level' => $reorderLevel, 'min_level' => $reorderLevel,
                        'unit_cost' => $row['unit_cost'] ?? 0, 'supplier_id' => $supplier?->id,
                    ]);
                    if ($openingQuantity > 0) {
                        app(StockLedgerService::class)->move(
                            $item->id, 'receive', $openingQuantity, $request->user()->id,
                            'CSV import opening balance', 'Opening quantity supplied in the import file.'
                        );
                    }
                    Audit::record('consumable_imported', 'consumables', InventoryItem::class, $item->id);
                }
                $created++;
            }
        }, 3);

        Audit::record('inventory_imported', $resource, null, null, [], ['rows' => $created, 'format' => $extension]);
        return redirect()->route('inventory.index', $resource)->with('success', "Successfully imported {$created} records. No records were imported if any validation error was found.");
    }
}
