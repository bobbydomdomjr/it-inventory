<?php
namespace App\Http\Controllers;
use App\Support\Audit;use App\Services\AssetHistoryWriter;use App\Services\AssetTagService;use App\Services\StockLedgerService;use Illuminate\Http\Request;use Illuminate\Validation\Rule;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Storage;
class InventoryResourceController extends Controller {
 private function settingValue(string $key, mixed $default = null): mixed
 {
  $record = \App\Models\SystemSetting::query()->where('key', $key)->first();
  return data_get($record?->value, 'value', $default);
 }
 private function definition(string $resource):array
 {
  $d=config("inventory.resources.$resource");abort_unless($d,404);abort_unless(request()->user()->hasPermission($d['permission'].'.view'),403);
  $settingKeys=[];
  if($resource==='assets'){
   foreach(['available','assigned','maintenance','defective','lost','retired'] as $key)$settingKeys[]='asset_status_label_'.$key;
   foreach(['new','good','fair','defective'] as $key)$settingKeys[]='asset_condition_label_'.$key;
   $settingKeys[]='automatic_asset_numbering';
  }
  if($resource==='consumables')$settingKeys[]='inventory_units';
  $settingValues=\App\Models\SystemSetting::query()->whereIn('key',$settingKeys)->get()->mapWithKeys(fn($item)=>[$item->key=>data_get($item->value,'value')])->all();
  $readSetting=static fn(string $key,mixed $default=null)=>$settingValues[$key]??$default;
  if($resource==='assets'){
   foreach(($d['filters']??[]) as &$filter){
    if(($filter['name']??null)==='status')foreach($filter['options'] as $key=>$label)$filter['options'][$key]=$readSetting('asset_status_label_'.$key,$label);
    if(($filter['name']??null)==='condition')foreach($filter['options'] as $key=>$label)$filter['options'][$key]=$readSetting('asset_condition_label_'.strtolower($key),$label);
   }
   unset($filter);
   foreach($d['fields'] as &$field){
    if(($field['name']??null)==='condition')foreach($field['options'] as $key=>$label)$field['options'][$key]=$readSetting('asset_condition_label_'.strtolower($key),$label);
    if(($field['name']??null)==='asset_tag' && !$readSetting('automatic_asset_numbering',true)){$field['required']=true;$field['label']='Asset ID';}
   }
   unset($field);
  }
  if($resource==='consumables'){
   $units=$readSetting('inventory_units',['piece','unit','box','pack','set']);
   $units=is_array($units)?$units:['piece','unit','box','pack','set'];
   $options=[];foreach($units as $unit){$unit=trim((string)$unit);if($unit!=='')$options[$unit]=$unit;}
   foreach($d['fields'] as &$field)if(($field['name']??null)==='unit'){$field['type']='select';$field['options']=$options?:['piece'=>'piece'];}
   unset($field);
  }
  return $d;
 }
 public function index(Request $request,string $resource)
 {
  $d=$this->definition($resource);
  if ($resource === 'assets') {
   $request->validate([
    'purchase_date_from' => ['nullable','date'],
    'purchase_date_to' => ['nullable','date','after_or_equal:purchase_date_from'],
    'purchase_cost_min' => ['nullable','numeric','min:0'],
    'purchase_cost_max' => ['nullable','numeric','min:0','gte:purchase_cost_min'],
    'warranty' => ['nullable','in:active,expired,none'],
    'depreciation' => ['nullable','in:configured'],
   ]);
  }
  $q=$d['model']::query();
  $relations=match($resource){
   'assets'=>['category','department','location','currentEmployee','supplier'],
   'employees'=>['department','user'],
   'maintenance'=>['asset'],
   'tickets'=>['asset'],
   'assignments'=>['asset','employee'],
   'transfers'=>['asset','fromEmployee','toEmployee'],
   'audits'=>['department','location'],
   'departments'=>['parent','headEmployee'],
   'locations'=>['parent'],
   'categories'=>['parent'],
   'consumables'=>['category','supplier','location','batches','locationBalances','reservations'],
   'disposals'=>['asset'],
   default=>[]
  };
  if($relations)$q->with($relations);
  if ($resource === 'assets') {
   $q->with(['assignments' => fn ($query) => $query->where('status', 'issued')->with('employee')]);
  }
  if($term=trim((string)$request->query('q'))){$q->where(function($sub)use($d,$term){foreach($d['search'] as $col)$sub->orWhere($col,'like','%'.$term.'%');});}
  $filterOptions=[];
  foreach(($d['filters']??[]) as $filter){
   if(isset($filter['options']))$filterOptions[$filter['name']]=$filter['options'];
   elseif(isset($filter['source'])){
    $source=$filter['source'];$class=config("inventory.resources.$source.model");
    $sourceQuery=$class::query();
    if ($source === 'categories') {
     $categoryType = match ($resource) { 'assets' => 'asset', 'consumables' => 'consumable', 'software' => 'software', default => null };
     if ($categoryType) $sourceQuery->where('type', $categoryType);
     $sourceQuery->where('is_active', true);
    }
    $items=$sourceQuery->orderBy('id')->get();
    $filterOptions[$filter['name']]=$items->mapWithKeys(function($item)use($source){$label=match($source){'departments'=>$item->code.' — '.$item->name,'categories'=>$item->code.' — '.$item->name,'suppliers'=>$item->company_name,default=>$item->name};return[$item->id=>$label];})->all();
   }
   $value=$request->query($filter['name']);
   if($value===null||$value==='')continue;
   if($filter['name']==='stock_level'){
    if($value==='out')$q->whereRaw("GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) <= 0", [now()]);
    elseif($value==='low')$q->whereRaw("GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) > 0 AND GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) <= GREATEST(reorder_level, min_level)", [now(), now()]);
    elseif($value==='normal')$q->whereRaw("GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) > GREATEST(reorder_level, min_level)", [now()]);
   }else{
    abort_unless(array_key_exists((string)$value,$filterOptions[$filter['name']]??[]),422,'Invalid filter value.');
    $q->where($filter['name'],$value);
   }
  }
  if ($resource === 'assets') {
   if ($request->filled('supplier_id')) $q->where('supplier_id', $request->integer('supplier_id'));
   if ($request->filled('purchase_date_from')) $q->whereDate('purchase_date', '>=', $request->date('purchase_date_from'));
   if ($request->filled('purchase_date_to')) $q->whereDate('purchase_date', '<=', $request->date('purchase_date_to'));
   if ($request->filled('purchase_cost_min')) $q->where('purchase_cost', '>=', $request->input('purchase_cost_min'));
   if ($request->filled('purchase_cost_max')) $q->where('purchase_cost', '<=', $request->input('purchase_cost_max'));
   if ($request->query('warranty') === 'active') $q->whereDate('warranty_expiration', '>=', today());
   if ($request->query('warranty') === 'expired') $q->whereNotNull('warranty_expiration')->whereDate('warranty_expiration', '<', today());
   if ($request->query('warranty') === 'none') $q->whereNull('warranty_expiration');
   if ($request->query('depreciation') === 'configured') $q->whereNotNull('purchase_date')->where(function ($sub) {
    $sub->whereNotNull('useful_life_months')->orWhereHas('category', fn ($category) => $category->whereNotNull('useful_life_months')->orWhereNotNull('default_useful_life_months'));
   });
  }
  $sort = $request->query('sort');
  $allowed = $resource === 'assets'
   ? ['id', 'asset_tag', 'serial_number', 'model', 'brand', 'category_id', 'department_id', 'location_id', 'supplier_id', 'status', 'purchase_date']
   : ($resource === 'consumables' ? ['sku','name','category_id','quantity_on_hand','reorder_level','unit','location_id','supplier_id','unit_cost'] : array_column($d['columns'], 'key'));

  if ($sort && in_array($sort, $allowed, true)) {
   $q->orderBy($sort, $request->query('direction') === 'asc' ? 'asc' : 'desc');
  } else {
   $q->latest();
  }

  if (in_array($resource, ['assets', 'categories'], true)) {
   $requestedPerPage = (int) $request->query('per_page', 50);
   $perPage = in_array($requestedPerPage, [10, 25, 50, 100], true) ? $requestedPerPage : 50;
  } else {
   $perPage = max(10, min(100, (int) $this->settingValue('items_per_page', 15)));
  }
  if ($resource === 'consumables') {
   $q->with(['reservations' => fn ($query) => $query->where('status', 'active')->where(fn ($active) => $active->whereNull('expires_at')->orWhere('expires_at', '>', now()))]);
  }
  $records=$q->paginate($perPage)->withQueryString();
  $stockSummary = null;
  if ($resource === 'consumables') {
   $stockSummary = [
    'items' => \App\Models\InventoryItem::query()->count(),
    'low' => \App\Models\InventoryItem::query()->whereRaw("GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) > 0 AND GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) <= GREATEST(reorder_level, min_level)", [now(), now()])->count(),
    'out' => \App\Models\InventoryItem::query()->whereRaw("GREATEST(0, quantity_on_hand - COALESCE((SELECT SUM(sr.quantity - sr.fulfilled_qty) FROM stock_reservations sr WHERE sr.inventory_item_id = inventory_items.id AND sr.status = 'active' AND (sr.expires_at IS NULL OR sr.expires_at > ?)), 0)) <= 0", [now()])->count(),
    'value' => (float) \App\Models\InventoryItem::query()->selectRaw('COALESCE(SUM(COALESCE((SELECT SUM(b.quantity_on_hand * b.unit_cost) FROM inventory_batches b WHERE b.inventory_item_id = inventory_items.id), 0) + GREATEST(0, inventory_items.quantity_on_hand - COALESCE((SELECT SUM(b2.quantity_on_hand) FROM inventory_batches b2 WHERE b2.inventory_item_id = inventory_items.id), 0)) * inventory_items.unit_cost), 0) AS total_value')->value('total_value'),
    'expiring' => \App\Models\InventoryBatch::query()->where('quantity_on_hand', '>', 0)->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', today()->addDays(30))->count(),
   ];
  }
  $filters = $d['filters'] ?? [];
  $importMaxRows = in_array($resource, ['assets', 'consumables'], true)
   ? max(100, min(10000, (int) $this->settingValue('import_max_rows', 1000)))
   : 1000;
  $importMaxSizeMb = in_array($resource, ['assets', 'consumables'], true)
   ? max(1, min(50, (int) $this->settingValue('import_max_size_mb', 10)))
   : 10;

  if ($resource === 'assets') {
   return view('inventory.assets-index', compact('d', 'records', 'resource', 'filters', 'filterOptions', 'importMaxRows', 'importMaxSizeMb', 'perPage'));
  }
  if ($resource === 'categories') {
   return view('inventory.categories-index', compact('d', 'records', 'resource', 'filters', 'filterOptions', 'perPage'));
  }

  return view('inventory.index', compact('d', 'records', 'resource', 'filters', 'filterOptions', 'importMaxRows', 'importMaxSizeMb', 'stockSummary'));
 }

 public function showImage(Request $request, int $id)
 {
  abort_unless($request->user()->hasPermission('assets.view'), 403);
  $asset = \App\Models\Asset::findOrFail($id);
  abort_unless($asset->image_path && Storage::disk('local')->exists($asset->image_path), 404);
  $extension = strtolower(pathinfo($asset->image_path, PATHINFO_EXTENSION));
  $mime = match ($extension) { 'jpg','jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', default => 'application/octet-stream' };
  return response()->file(Storage::disk('local')->path($asset->image_path), [
   'Content-Type' => $mime,
   'Content-Disposition' => 'inline',
   'Cache-Control' => 'private, no-store',
   'X-Content-Type-Options' => 'nosniff',
  ]);
 }

 public function updateImage(Request $request, int $id)
 {
  abort_unless($request->user()->hasPermission('assets.manage'), 403);
  $asset = \App\Models\Asset::findOrFail($id);
  $data = $request->validate([
   'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
   'gallery_images' => ['nullable','array','max:8'],
   'gallery_images.*' => ['image','mimes:jpg,jpeg,png,webp','max:5120'],
  ]);
  abort_if(empty($data['image']) && empty($data['gallery_images']), 422, 'Choose a primary image or at least one gallery image.');
  $oldPrimaryPath = null;
  DB::transaction(function () use ($request, $asset, $data, &$oldPrimaryPath): void {
   if (!empty($data['image'])) {
    $oldPrimaryPath = $asset->image_path;
    $path = $data['image']->store("assets/{$asset->id}/primary", 'local');
    $asset->update(['image_path' => $path]);
    app(AssetHistoryWriter::class)->record($asset, 'image_updated', ['image_path' => $oldPrimaryPath], ['image_path' => $path]);
   }
   foreach (($data['gallery_images'] ?? []) as $image) {
    $galleryPath = $image->store("assets/{$asset->id}/gallery", 'local');
    $photo = $asset->documents()->create([
     'title' => 'Asset photo',
     'type' => 'asset_photo',
     'version' => 1,
     'original_name' => $image->getClientOriginalName(),
     'path' => $galleryPath,
     'mime_type' => $image->getMimeType(),
     'size' => $image->getSize(),
     'checksum' => hash_file('sha256', $image->getRealPath()),
     'uploaded_by' => $request->user()->id,
    ]);
    app(AssetHistoryWriter::class)->record($asset, 'gallery_image_added', [], ['document_id' => $photo->id, 'file_name' => $photo->original_name]);
   }
  }, 3);
  if ($oldPrimaryPath) Storage::disk('local')->delete($oldPrimaryPath);
  Audit::record('asset_image_updated', 'assets', \App\Models\Asset::class, $asset->id);
  return back()->with('success', 'Asset image and gallery updated.');
 }

 public function removeImage(Request $request, int $id)
 {
  abort_unless($request->user()->hasPermission('assets.manage'), 403);
  $asset = \App\Models\Asset::findOrFail($id);
  abort_unless($asset->image_path, 404);
  $old = $asset->image_path;
  DB::transaction(function () use ($asset, $old): void {
   $asset->update(['image_path' => null]);
   app(AssetHistoryWriter::class)->record($asset, 'image_removed', ['image_path' => $old], []);
  });
  Storage::disk('local')->delete($old);
  Audit::record('asset_image_removed', 'assets', \App\Models\Asset::class, $asset->id);
  return back()->with('success', 'Primary asset image removed.');
 }

 public function bulkUpdateAssets(Request $request)
 {
  abort_unless($request->user()->hasPermission('assets.manage'), 403);
  $data = $request->validate([
   'ids' => ['required','array','min:1','max:200'],
   'ids.*' => ['required','integer','distinct'],
   'status' => ['nullable','in:available,defective'],
   'condition' => ['nullable','in:New,Good,Fair,Defective'],
   'department_id' => ['nullable','integer','exists:departments,id'],
   'location_id' => ['nullable','integer','exists:locations,id'],
   'supplier_id' => ['nullable','integer','exists:suppliers,id'],
  ]);
  $changes = array_filter(array_intersect_key($data, array_flip(['status','condition','department_id','location_id','supplier_id'])), fn ($value) => $value !== null && $value !== '');
  abort_if(empty($changes), 422, 'Select at least one field to update.');
  foreach ([
   'department_id' => \App\Models\Department::class,
   'location_id' => \App\Models\Location::class,
   'supplier_id' => \App\Models\Supplier::class,
  ] as $field => $model) {
   if (!empty($changes[$field])) {
    abort_unless($model::query()->whereKey($changes[$field])->exists(), 422, 'The selected reference is outside your office or unavailable.');
   }
  }
  $assets = \App\Models\Asset::query()->whereIn('id', $data['ids'])->get();
  abort_unless($assets->count() === count($data['ids']), 404, 'One or more selected assets are unavailable in your office.');
  DB::transaction(function () use ($assets, $changes): void {
   foreach ($assets as $asset) {
    $locked = \App\Models\Asset::query()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
    if (array_key_exists('status', $changes)) {
     abort_unless(in_array($locked->status, ['available','defective'], true), 422, 'Assets in assigned, maintenance, lost, or retired status must be changed through their dedicated workflow.');
    }
    $old = $locked->getAttributes();
    $locked->fill($changes)->save();
    app(AssetHistoryWriter::class)->record($locked, 'bulk_updated', $old, $locked->getAttributes());
    Audit::record('bulk_asset_updated', 'assets', \App\Models\Asset::class, $locked->id, $old, $locked->getAttributes());
   }
  }, 3);
  return redirect()->route('inventory.index', 'assets')->with('success', count($assets).' asset record(s) updated successfully.');
 }

 public function bulkLabels(Request $request)
 {
  $this->definition('assets');
  abort_unless($request->user()->hasPermission('assets.view'),403);

  $validated=$request->validate([
   'ids'=>['required','array','min:1','max:200'],
   'ids.*'=>['required','integer','distinct','exists:assets,id'],
  ]);

  $ids=array_map('intval',$validated['ids']);
  $assets=\App\Models\Asset::query()->with(['category','location'])
   ->whereIn('id',$ids)
   ->orderByRaw('FIELD(id,'.implode(',',array_fill(0,count($ids),'?')).')',$ids)
   ->get(['id','asset_tag','name','serial_number','category_id','location_id','status']);

  abort_if($assets->isEmpty(),404,'No assets were found for label printing.');

  return view('inventory.bulk-labels',compact('assets'));
 }

 public function create(string $resource)
 {
  $d=$this->definition($resource);
  abort_if($d['workflow_only']??false,403,'Use the dedicated workflow to create this record.');
  abort_unless(request()->user()->hasPermission($d['permission'].'.manage'),403);
  $record=new $d['model'];
  if($resource==='categories')$record->default_useful_life_months=$this->settingValue('default_useful_life_months',48);
  if($resource==='assets'){$record->useful_life_months=$this->settingValue('default_useful_life_months',48);$record->depreciation_method=$this->settingValue('default_depreciation_method','straight_line');}
  if($resource==='consumables')$record->reorder_level=$this->settingValue('low_stock_default',5);
  $options=$this->options($d, null);
  $assetCategoryDefinitions=$resource==='assets'?$this->assetCategoryDefinitions():[];
  $assetCustomFieldValues=[];
  $consumableCategoryStockDefaults=$resource==='consumables'?$this->consumableCategoryStockDefaults():[];
  return view('inventory.form',compact('d','record','options','resource','assetCategoryDefinitions','assetCustomFieldValues','consumableCategoryStockDefaults'));
 }
 public function store(Request $request,string $resource)
 {
  $d=$this->definition($resource);
  abort_if($d['workflow_only']??false,403,'Use the dedicated workflow to create this record.');
  abort_unless($request->user()->hasPermission($d['permission'].'.manage'),403);
  if ($resource === 'assets' && ! $request->filled('asset_tag') && $this->settingValue('automatic_asset_numbering', true)) {
   $request->merge(['asset_tag' => app(AssetTagService::class)->next()]);
  }
  $data=$this->validateData($request,$d);
  if ($resource === 'categories' && $request->has('custom_fields_present')) {
   $data['custom_fields'] = ($data['type'] ?? 'asset') === 'asset' ? $this->validatedCategoryCustomFields($request) : [];
  }
  if ($resource === 'assets') {
   $data['specs'] = ['custom_fields' => $this->validatedAssetCustomFields($request, (int) $data['category_id'])];
   $data = $this->applyAssetCategoryDefaults($data, null, $request);
  }
  if ($resource === 'consumables') {
   $data = $this->applyConsumableCategoryDefaults($data, $request, null);
  }
  $this->assertValidHierarchyParent($resource, $data['parent_id'] ?? null);
  if ($resource === 'audits') {
   if (($data['scope'] ?? 'all') === 'location' && empty($data['location_id'])) abort(422, 'Select a location for a location-scoped audit.');
   if (($data['scope'] ?? 'all') === 'department' && empty($data['department_id'])) abort(422, 'Select a department for a department-scoped audit.');
  }
  if($resource==='tickets'){
   $slaHours=match($data['priority']??'normal'){'urgent'=>4,'high'=>8,'low'=>72,default=>24};
   $data['sla_due_at']=now()->addHours($slaHours);
  }
  if($resource==='procurements'){
   $data['requested_by_user_id']=$request->user()->id;
   $data['requested_by']=$request->user()->name;
   $data['status']='draft';
  }

  $openingQuantity = null;
  if ($resource === 'consumables') {
   $openingQuantity = (int) ($data['quantity_on_hand'] ?? 0);
   $data['quantity_on_hand'] = 0;
  }
  if($resource==='audits'){
   $record=DB::transaction(function()use($data,$request,$d){
    $record=new $d['model'];
    $record->fill($data)->save();
    $scope=$data['scope']??'all';
    $record->forceFill(['scope'=>$scope,'scheduled_for'=>$record->scheduled_at])->save();
    $assetQuery=\App\Models\Asset::query()->select('id')->orderBy('id');
    if($scope==='location')$assetQuery->where('location_id',$record->location_id);
    if($scope==='department')$assetQuery->where('department_id',$record->department_id);
    $assetQuery->chunkById(500,function($assets)use($record,$request):void{
     $now=now();
     $rows=$assets->map(fn($asset)=>['audit_id'=>$record->id,'asset_id'=>$asset->id,'office_id'=>$record->office_id,'result'=>'Pending','auditor_id'=>null,'created_by'=>$request->user()->id,'updated_by'=>$request->user()->id,'created_at'=>$now,'updated_at'=>$now])->all();
     if($rows)DB::table('inventory_audit_items')->insert($rows);
    });
    return $record;
   });
  }elseif($resource==='maintenance'){
   $record=DB::transaction(function()use($data){
    $asset=\App\Models\Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
    abort_unless(in_array($asset->status,['available','defective'],true),422,'Return the asset or close its current work order before opening another maintenance record.');
    abort_if(\App\Models\MaintenanceRecord::where('asset_id',$asset->id)->whereNotIn('status',['completed','cancelled'])->exists(),422,'An active maintenance work order already exists for this asset.');
    $record=new \App\Models\MaintenanceRecord();
    $record->fill($data)->save();
    $asset->update(['status'=>'maintenance']);
    return $record;
   });
  }else{
   $record = DB::transaction(function () use ($d, $data, $resource, $openingQuantity, $request) {
    $record = new $d['model'];
    $record->fill($data)->save();

    if ($resource === 'consumables') {
     \App\Models\InventoryItemLocation::firstOrCreate(
      ['inventory_item_id' => $record->id, 'location_id' => $record->location_id],
      ['office_id' => $record->office_id, 'quantity_on_hand' => 0]
     );
    }
    if ($resource === 'consumables' && $openingQuantity > 0) {
     app(StockLedgerService::class)->move(
      $record->id,
      'receive',
      $openingQuantity,
      $request->user()->id,
      'Opening balance',
      'Initial stock recorded at item registration.'
     );
    }

    // Keep the asset row and its required history entry atomic. Previously,
    // a history-write failure could return an error after the asset had
    // already committed, causing duplicate-create confusion on retry.
    if ($resource === 'assets') {
     app(AssetHistoryWriter::class)->record($record, 'registered', [], $record->getAttributes());
    }

    if ($resource === 'tickets' && in_array($record->status, ['resolved', 'closed'], true)) {
     $record->forceFill(['resolved_at' => now()])->save();
    }

    return $record;
   }, 3);
  }
  Audit::record('created',$resource,$record::class,$record->id,[],$record->getAttributes());
  return redirect()->route('inventory.index',$resource)->with('success',$d['singular'].' created successfully.');
 }
 public function show(string $resource,int $id)
 {
  $d=$this->definition($resource);
  $record=$d['model']::findOrFail($id);
  $supplierEvaluations=$resource==='suppliers'?$record->evaluations()->with('evaluator')->get():collect();
  $employeeAssets=$resource==='employees'?$record->assets()->with(['category','location'])->get():collect();
  $employeeAssignments=$resource==='employees'?$record->assignments()->with('asset')->latest()->get():collect();
  $documents=method_exists($record,'documents')?$record->documents()->with('uploader')->latest()->get():collect();
  $assetGallery=$resource==='assets'?$documents->where('type','asset_photo')->values():collect();
  $comments=$resource==='tickets'?$record->comments()->with('user')->get():collect();
  $licenseAssignments=$resource==='software'?$record->assignments()->with(['employee','asset'])->latest()->get():collect();
  $receivedAssets=$resource==='procurements'?$record->assets()->latest()->get():collect();
  $approvals=method_exists($record,'approvals')?$record->approvals()->with('approver')->get():collect();
  $assignmentHistory=$resource==='assets'?$record->assignments()->with('employee')->latest()->get():collect();
  $assetHistories=$resource==='assets'?$record->histories()->with('actor')->get():collect();
  $assetValuation=$resource==='assets'?app(\App\Services\AssetValuationService::class)->summarize($record->loadMissing(['category','depreciationEntries'])):null;
  $auditItems=$resource==='audits'?$record->items()->with(['asset','seenLocation','seenEmployee','auditor'])->orderBy('id')->get():collect();
  $stockMovements = $resource === 'consumables' ? $record->movements()->with(['performer','batch','location','toLocation'])->latest('created_at')->limit(60)->get() : collect();
  $stockBatches = $resource === 'consumables' ? $record->batches()->with(['location','supplier'])->where('quantity_on_hand','>',0)->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->get() : collect();
  $stockReservations = $resource === 'consumables' ? $record->reservations()->with(['location','reserver'])->where('status','active')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at','>',now()))->latest()->get() : collect();
  $stockLocationBalances = $resource === 'consumables' ? $record->locationBalances()->with('location')->orderBy('location_id')->get() : collect();
  $stockTransfers = $resource === 'consumables' ? $record->transfers()->with(['fromLocation','toLocation','requester'])->latest('transferred_at')->limit(25)->get() : collect();
  $stockLocations = $resource === 'consumables' ? \App\Models\Location::query()->orderBy('name')->get() : collect();
  $stockReservedQty = $resource === 'consumables' ? app(\App\Services\StockLedgerService::class)->totalReservedQuantity($record->id) : 0;
  $stockAvailableQty = $resource === 'consumables' ? max(0, (int)$record->quantity_on_hand - $stockReservedQty) : 0;
  $trackedBatchQty = $stockBatches->sum('quantity_on_hand');
  $stockValue = $resource === 'consumables'
   ? (float)$stockBatches->sum(fn($batch) => (int)$batch->quantity_on_hand * (float)$batch->unit_cost)
      + max(0, (int)$record->quantity_on_hand - (int)$trackedBatchQty) * (float)$record->unit_cost
   : 0;
  $assetDisplayLabels=['status'=>[],'condition'=>[]];
  if($resource==='assets'){
   $statusKeys=['available','assigned','maintenance','defective','lost','retired'];
   $conditionKeys=['new','good','fair','defective'];
   $labelSettings=\App\Models\SystemSetting::query()->whereIn('key',array_merge(array_map(fn($key)=>'asset_status_label_'.$key,$statusKeys),array_map(fn($key)=>'asset_condition_label_'.$key,$conditionKeys)))->get()->keyBy('key');
   foreach($statusKeys as $key)$assetDisplayLabels['status'][$key]=data_get($labelSettings->get('asset_status_label_'.$key)?->value,'value',str_replace('_',' ',$key));
   foreach($conditionKeys as $key)$assetDisplayLabels['condition'][ucfirst($key)]=data_get($labelSettings->get('asset_condition_label_'.$key)?->value,'value',ucfirst($key));
  }
  $automaticAssetNumbering=(bool)data_get(\App\Models\SystemSetting::where('key','automatic_asset_numbering')->first()?->value,'value',true);
  $approverOptions=collect();$approvalFlow=null;
  if(in_array($resource,['procurements','disposals'],true)){
   $approverOptions=\App\Models\User::where('is_active',true)->where(function($q){$q->whereHas('roles.permissions',fn($p)=>$p->where('name','approvals.approve'))->orWhereHas('roles',fn($r)=>$r->where('slug','super-admin'));})->orderBy('name')->get();
   $approvalFlow=\App\Models\ApprovalFlow::with('steps')->where('subject_type',$record::class)->where('is_active',true)->latest('id')->first();
  }
  return view('inventory.show',compact('d','record','resource','documents','comments','licenseAssignments','receivedAssets','approvals','assignmentHistory','assetHistories','auditItems','approverOptions','approvalFlow','supplierEvaluations','employeeAssets','employeeAssignments','assetDisplayLabels','automaticAssetNumbering','assetValuation','assetGallery','stockMovements','stockBatches','stockReservations','stockLocationBalances','stockTransfers','stockLocations','stockReservedQty','stockAvailableQty','stockValue'));
 }
 public function duplicate(string $resource, int $id)
 {
  abort_unless($resource === 'assets', 404);
  $d = $this->definition($resource);
  abort_unless(request()->user()->hasPermission('assets.manage'), 403);

  $source = $d['model']::findOrFail($id);
  $copyableFields = collect($d['fields'] ?? [])
   ->pluck('name')
   ->reject(fn ($field) => in_array($field, ['asset_tag', 'serial_number'], true))
   ->all();
  $attributes = array_intersect_key($source->getAttributes(), array_flip($copyableFields));

  $record = new $d['model'];
  $record->fill($attributes);
  // A clone is a new, unassigned asset. Do not duplicate identity, photos,
  // custody, assignment, or disposal state from the source record.
  $record->asset_tag = null;
  $record->serial_number = null;
  $record->image_path = null;
  $record->current_employee_id = null;
  $record->status = 'available';
  $record->disposed_at = null;
  $record->office_id = $source->office_id;

  $options = $this->options($d, $record);
  $assetCategoryDefinitions=$resource==='assets'?$this->assetCategoryDefinitions($record):[];
  $assetCustomFieldValues=$resource==='assets'?data_get($record->specs,'custom_fields',[]):[];
  $consumableCategoryStockDefaults=$resource==='consumables'?$this->consumableCategoryStockDefaults($record):[];
  return view('inventory.form', compact('d', 'record', 'options', 'resource', 'assetCategoryDefinitions', 'assetCustomFieldValues', 'consumableCategoryStockDefaults'));
 }

 public function edit(string $resource,int $id)
 {
  $d=$this->definition($resource);
  abort_if($d['workflow_only']??false,403,'This record is controlled by a workflow.');
  abort_unless(request()->user()->hasPermission($d['permission'].'.manage'),403);
  $record=$d['model']::findOrFail($id);
  if($resource==='consumables' && filled($record->unit)){foreach($d['fields'] as &$field)if(($field['name']??null)==='unit')$field['options'][$record->unit]=$record->unit;unset($field);}
  $options=$this->options($d, $record);
  $assetCategoryDefinitions=$resource==='assets'?$this->assetCategoryDefinitions($record):[];
  $assetCustomFieldValues=$resource==='assets'?data_get($record->specs,'custom_fields',[]):[];
  $consumableCategoryStockDefaults=$resource==='consumables'?$this->consumableCategoryStockDefaults($record):[];
  return view('inventory.form',compact('d','record','options','resource','assetCategoryDefinitions','assetCustomFieldValues','consumableCategoryStockDefaults'));
 }
 public function update(Request $request,string $resource,int $id)
 {
  $d=$this->definition($resource);
  abort_if($d['workflow_only']??false,403,'This record is controlled by a workflow.');
  abort_unless($request->user()->hasPermission($d['permission'].'.manage'),403);
  $record=$d['model']::findOrFail($id);
  $old=$record->getAttributes();
  $data=$this->validateData($request,$d,$record);
  if ($resource === 'categories') {
   $this->assertCategoryTypeCanChange($record, $data['type'] ?? $record->type);
   if ($request->has('custom_fields_present')) {
    $data['custom_fields'] = ($data['type'] ?? 'asset') === 'asset' ? $this->validatedCategoryCustomFields($request) : [];
   }
  }
  if ($resource === 'assets') {
   $existingSpecs = $record->specs ?? [];
   $existingSpecs['custom_fields'] = $this->validatedAssetCustomFields($request, (int) ($data['category_id'] ?? $record->category_id), $record);
   $data['specs'] = $existingSpecs;
   $data = $this->applyAssetCategoryDefaults($data, $record, $request);
  }
  if ($resource === 'consumables') {
   $data = $this->applyConsumableCategoryDefaults($data, $request, $record);
   if (array_key_exists('location_id', $data) && (string) ($data['location_id'] ?? '') !== (string) ($record->location_id ?? '')) {
    $currentBalance = $record->locationBalances()->where(function ($query) use ($record) {
     $record->location_id === null ? $query->whereNull('location_id') : $query->where('location_id', $record->location_id);
    })->value('quantity_on_hand');
    $activeReservationQuery = $record->reservations()->where('status', 'active')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    $record->location_id === null ? $activeReservationQuery->whereNull('location_id') : $activeReservationQuery->where('location_id', $record->location_id);
    abort_if((int) $currentBalance > 0 || $activeReservationQuery->exists(), 422, 'Transfer the remaining stock and release active reservations at the current location before changing the item’s default location.');
   }
  }
  $this->assertValidHierarchyParent($resource, $data['parent_id'] ?? null, $record->id);
  if($resource==='procurements')unset($data['requested_by'],$data['requested_by_user_id']);
  if($resource==='audits' && $record->items()->exists()){
   foreach(['scope','department_id','location_id'] as $lockedField){
    if(array_key_exists($lockedField,$data) && (string)$data[$lockedField] !== (string)$record->getAttribute($lockedField))abort(422,'The scope of an audit cannot be changed after its checklist has been generated. Create a new audit to change its scope.');
   }
  }
  if($resource==='consumables')unset($data['quantity_on_hand']);
  if($resource==='employees' && isset($data['status']) && $data['status']!=='active' && $record->assets()->exists()){
   abort(422,'Transfer or return this employee’s assigned assets before changing the employee status.');
  }
  if($resource==='tickets'){
   if(!$record->first_response_at && !$record->resolved_at && array_key_exists('priority',$data)){
    $slaHours=match($data['priority']){'urgent'=>4,'high'=>8,'low'=>72,default=>24};
    $data['sla_due_at']=($record->created_at??now())->copy()->addHours($slaHours);
   }
   if(in_array($data['status']??$record->status,['resolved','closed'],true))$data['resolved_at']=$record->resolved_at??now();
   elseif(isset($data['status']))$data['resolved_at']=null;
  }
  DB::transaction(function () use ($record, $data, $resource, $old): void {
   $record->fill($data)->save();
   if ($resource === 'assets') {
    app(AssetHistoryWriter::class)->record($record, 'updated', $old, $record->getAttributes());
   }
   Audit::record('updated', $resource, $record::class, $record->id, $old, $record->getAttributes());
  }, 3);
  return redirect()->route('inventory.show',[$resource,$record->id])->with('success',$d['singular'].' updated successfully.');
 }
 public function destroy(string $resource,int $id){
  $d=$this->definition($resource);abort_if($d['workflow_only']??false,403,'This record is controlled by a workflow.');abort_unless(request()->user()->hasPermission($d['permission'].'.manage'),403);
  $record=$d['model']::findOrFail($id);
  if($resource==='assets' && ($record->status==='assigned' || $record->assignments()->where('status','issued')->exists()))abort(422,'Return the asset before archiving its record.');
  if($resource==='employees' && $record->assets()->exists())abort(422,'Transfer or return this employee’s assigned assets before archiving the employee.');
  if($resource==='software' && $record->assignments()->where('status','active')->exists())abort(422,'Return all active software allocations before archiving this license.');
  if($resource==='maintenance' && !in_array($record->status,['completed','cancelled'],true))abort(422,'Close or cancel the maintenance work order before archiving it.');
  $old=$record->getAttributes();if($resource==='categories'){$record->update(['is_active'=>false]);Audit::record('archived',$resource,$record::class,$id,$old,$record->getAttributes());return redirect()->route('inventory.index',$resource)->with('success',$d['singular'].' archived. Existing linked records remain connected.');}$record->delete();Audit::record('archived',$resource,$record::class,$id,$old,[]);return redirect()->route('inventory.index',$resource)->with('success',$d['singular'].' archived.');
 }
 private function options(array $definition, $record = null): array
 {
  $options = [];
  $resource = $definition['permission'] ?? null;

  foreach ($definition['fields'] ?? [] as $field) {
   if (($field['type'] ?? null) !== 'select' || empty($field['source'])) {
    continue;
   }

   $source = (string) $field['source'];
   $model = $source === 'users'
    ? \App\Models\User::class
    : config("inventory.resources.{$source}.model");

   abort_unless(is_string($model) && class_exists($model), 500, 'Invalid inventory dropdown source: '.$source.'.');

   $query = $model::query();
   $selectedId = $record?->getAttribute($field['name']);
   $activeFilterApplies = in_array($source, ['employees', 'categories', 'suppliers', 'users'], true)
    || ($source === 'assets' && $resource === 'maintenance');

   if ($activeFilterApplies) {
    $query->where(function ($scope) use ($source, $resource, $selectedId): void {
     $scope->where(function ($active) use ($source, $resource): void {
      if ($source === 'employees') {
       $active->where('status', 'active');
      } elseif ($source === 'categories' || $source === 'users') {
       $active->where('is_active', true);
       if ($source === 'categories') {
        $categoryType = match ($resource) { 'assets' => 'asset', 'consumables' => 'consumable', 'software' => 'software', default => null };
        if ($categoryType) $active->where('type', $categoryType);
       }
      } elseif ($source === 'suppliers') {
       $active->where('accreditation_status', 'active');
      } elseif ($source === 'assets' && $resource === 'maintenance') {
       $active->whereIn('status', ['available', 'defective']);
      }
     });

     // Preserve an existing inactive/current selection on edit so saving an
     // unrelated field does not silently clear a historical relationship.
     if ($selectedId) {
      $scope->orWhereKey($selectedId);
     }
    });
   }

   $items = $query->orderBy('id')->get();

   $options[$field['name']] = $items->mapWithKeys(function ($item) use ($source): array {
    $label = match ($source) {
     'employees' => trim(($item->employee_no ? $item->employee_no.' — ' : '').$item->full_name),
     'departments', 'categories' => trim(($item->code ? $item->code.' — ' : '').$item->name),
     'assets' => trim(($item->asset_tag ? $item->asset_tag.' — ' : '').$item->name),
     'suppliers' => $item->company_name,
     'users' => trim($item->name.' <'.$item->email.'>'),
     default => $item->name ?? $item->title ?? (string) $item->getKey(),
    };

    return [$item->getKey() => $label];
   })->all();
  }

  return $options;
 }

 private function assertValidHierarchyParent(string $resource, $parentId, ?int $recordId = null): void
 {
  if (! in_array($resource, ['departments', 'locations', 'categories'], true) || ! $parentId) return;
  $model = match ($resource) {
   'departments' => \App\Models\Department::class,
   'locations' => \App\Models\Location::class,
   default => \App\Models\AssetCategory::class,
  };
  $seen = [];
  $cursor = (int) $parentId;
  while ($cursor) {
   abort_if($recordId && $cursor === $recordId, 422, 'A record cannot be its own parent or a descendant of itself.');
   abort_if(isset($seen[$cursor]), 422, 'The existing hierarchy contains a cycle. Correct the parent records first.');
   $seen[$cursor] = true;
   $parent = $model::query()->select(['id', 'parent_id'])->find($cursor);
   if (! $parent) break;
   $cursor = (int) ($parent->parent_id ?? 0);
  }
 }
 private function assetCategoryDefinitions(?\App\Models\Asset $record = null): array
 {
  $query = \App\Models\AssetCategory::query()->where(function ($scope) use ($record): void {
   $scope->where('type', 'asset')->where('is_active', true);
   if ($record?->category_id) $scope->orWhereKey($record->category_id);
  });
  return $query->orderBy('name')->get()->mapWithKeys(fn ($category) => [
   $category->id => [
    'name' => $category->name,
    'custom_fields' => $category->custom_fields ?? [],
    'useful_life_months' => $category->useful_life_months ?: $category->default_useful_life_months,
    'depreciation_method' => $category->depreciation_method ?: 'not_configured',
    'salvage_percent' => (float) $category->salvage_percent,
   ],
  ])->all();
 }

 private function consumableCategoryStockDefaults(?\App\Models\InventoryItem $record = null): array
 {
  $query = \App\Models\AssetCategory::query()->where(function ($scope) use ($record): void {
   $scope->where('type', 'consumable')->where('is_active', true);
   if ($record?->category_id) $scope->orWhereKey($record->category_id);
  });
  $systemDefault = (int) $this->settingValue('low_stock_default', 5);
  return $query->get(['id', 'default_min_stock_level'])->mapWithKeys(fn ($category) => [
   $category->id => (int) ($category->default_min_stock_level ?: $systemDefault),
  ])->all();
 }

 private function assertCategoryTypeCanChange(\App\Models\AssetCategory $category, ?string $newType): void
 {
  if (! $newType || $newType === $category->type) return;
  $hasLinkedRecords = $category->assets()->exists()
   || $category->consumables()->exists()
   || $category->software()->exists();
  abort_if($hasLinkedRecords, 422, 'The category type cannot be changed while assets, consumables, or software records are linked to it. Create a new category for the other record type.');
 }

 private function validatedCategoryCustomFields(Request $request): array
 {
  $request->validate([
   'custom_fields' => ['nullable','array','max:25'],
   'custom_fields.*.label' => ['required','string','max:80'],
   'custom_fields.*.key' => ['required','string','regex:/^[a-z][a-z0-9_]{1,39}$/','distinct'],
   'custom_fields.*.type' => ['required','in:text,number,date,select,textarea'],
   'custom_fields.*.options' => ['nullable','string','max:1500'],
   'custom_fields.*.required' => ['nullable','boolean'],
  ]);
  $fields = [];
  foreach ((array) $request->input('custom_fields', []) as $field) {
   $options = array_values(array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? ''))), fn ($value) => $value !== ''));
   if (($field['type'] ?? '') === 'select' && count($options) < 1) {
    throw \Illuminate\Validation\ValidationException::withMessages(['custom_fields' => 'Add at least one option for each select-type field.']);
   }
   $fields[] = [
    'key' => strtolower($field['key']),
    'label' => trim($field['label']),
    'type' => $field['type'],
    'required' => ! empty($field['required']),
    'options' => $field['type'] === 'select' ? $options : [],
   ];
  }
  return $fields;
 }

 private function validatedAssetCustomFields(Request $request, int $categoryId, ?\App\Models\Asset $record = null): array
 {
  $categoryQuery = \App\Models\AssetCategory::query()->where('type', 'asset');
  if (! $record || (int) $record->category_id !== $categoryId) $categoryQuery->where('is_active', true);
  $category = $categoryQuery->findOrFail($categoryId);
  $definitions = collect($category->custom_fields ?? [])->keyBy('key');
  $input = (array) $request->input('custom_attributes', []);
  $rules = ['custom_attributes' => ['nullable','array']];
  foreach ($definitions as $key => $definition) {
   $fieldRules = [! empty($definition['required']) ? 'required' : 'nullable'];
   switch ($definition['type'] ?? 'text') {
    case 'number': $fieldRules[] = 'numeric'; $fieldRules[] = 'min:0'; break;
    case 'date': $fieldRules[] = 'date'; break;
    case 'select': $fieldRules[] = \Illuminate\Validation\Rule::in($definition['options'] ?? []); break;
    default: $fieldRules[] = 'string'; $fieldRules[] = 'max:2000'; break;
   }
   $rules['custom_attributes.'.$key] = $fieldRules;
  }
  $request->validate($rules);
  $values = [];
  foreach ($definitions as $key => $definition) {
   $values[$key] = $input[$key] ?? null;
  }
  return $values;
 }

 private function applyAssetCategoryDefaults(array $data, ?\App\Models\Asset $record = null, ?Request $request = null): array
 {
  $categoryId = $data['category_id'] ?? $record?->category_id;
  $categoryQuery = \App\Models\AssetCategory::query()->where('type', 'asset');
  if (! $record || (int) $record->category_id !== (int) $categoryId) $categoryQuery->where('is_active', true);
  $category = $categoryQuery->findOrFail($categoryId);
  $categoryChanged = ! $record || (int) $record->category_id !== (int) $categoryId;

  if ($categoryChanged && ! $request?->boolean('useful_life_manual')) {
   $data['useful_life_months'] = $category->useful_life_months
    ?: ($category->default_useful_life_months ?: $this->settingValue('default_useful_life_months', 48));
  }
  if ($categoryChanged && ! $request?->boolean('depreciation_method_manual')) {
   $data['depreciation_method'] = ! in_array($category->depreciation_method, [null, '', 'not_configured'], true)
    ? $category->depreciation_method
    : $this->settingValue('default_depreciation_method', 'straight_line');
  }
  if ($categoryChanged && ! $request?->boolean('salvage_value_manual')) {
   $salvagePercent = (float) $category->salvage_percent;
   if ($salvagePercent <= 0) $salvagePercent = (float) $this->settingValue('default_salvage_percent', 0);
   $purchaseCost = (float) ($data['purchase_cost'] ?? $record?->purchase_cost ?? 0);
   $data['salvage_value'] = round($purchaseCost * $salvagePercent / 100, 2);
  } elseif (! array_key_exists('salvage_value', $data) || $data['salvage_value'] === null || $data['salvage_value'] === '') {
   $purchaseCost = (float) ($data['purchase_cost'] ?? $record?->purchase_cost ?? 0);
   $data['salvage_value'] = round($purchaseCost * (float) $this->settingValue('default_salvage_percent', 0) / 100, 2);
  }
  return $data;
 }

 private function applyConsumableCategoryDefaults(array $data, Request $request, ?\App\Models\InventoryItem $record): array
 {
  $categoryId = array_key_exists('category_id', $data) ? $data['category_id'] : $record?->category_id;
  if (! $categoryId) {
   if (array_key_exists('reorder_level', $data)) $data['min_level'] = (int) $data['reorder_level'];
   return $data;
  }
  $categoryQuery = \App\Models\AssetCategory::query()->where('type', 'consumable');
  if (! $record || (int) $record->category_id !== (int) $categoryId) $categoryQuery->where('is_active', true);
  $category = $categoryQuery->findOrFail($categoryId);
  $categoryChanged = ! $record || (int) $record->category_id !== (int) $categoryId;
  if ($categoryChanged && ! $request->boolean('reorder_level_manual')) {
   $default = (int) ($category->default_min_stock_level ?: $this->settingValue('low_stock_default', 5));
   $data['reorder_level'] = $default;
   $data['min_level'] = $default;
  } elseif (array_key_exists('reorder_level', $data)) {
   $data['min_level'] = (int) $data['reorder_level'];
  }
  return $data;
 }

 private function validateData(Request $request, array $definition, $record = null): array
 {
  $rules = [];

  foreach ($definition['fields'] as $field) {
   if (! empty($field['workflow_managed'])) {
    continue;
   }

   $name = $field['name'];
   $type = $field['type'] ?? 'text';
   $fieldRules = [! empty($field['required']) ? 'required' : 'nullable'];

   // Each validation rule must be a separate array entry. A pipe-delimited
   // string inside a rule array is treated as one method name by Laravel.
   switch ($type) {
    case 'email':
     $fieldRules[] = 'email';
     $fieldRules[] = 'max:254';
     break;
    case 'date':
     $fieldRules[] = 'date';
     break;
    case 'number':
     $fieldRules[] = (($field['step'] ?? null) === '1') ? 'integer' : 'numeric';
     $fieldRules[] = 'min:0';
     break;
    default:
     $fieldRules[] = 'string';
     $fieldRules[] = 'max:5000';
     break;
   }

   if ($name === 'code' && $definition['model'] === \App\Models\AssetCategory::class) {
    $fieldRules[] = 'max:30';
    $fieldRules[] = 'alpha_dash';
   }

   if (! empty($field['unique'])) {
    $model = $definition['model'];
    $table = $record ? $record->getTable() : (new $model())->getTable();
    $fieldRules[] = Rule::unique($table, $name)->ignore($record?->getKey());
   }

   if ($type === 'select' && ! empty($field['source'])) {
    $sourceModel = $field['source'] === 'users'
     ? \App\Models\User::class
     : config('inventory.resources.'.$field['source'].'.model');

    abort_unless(is_string($sourceModel) && class_exists($sourceModel), 500, 'Invalid inventory reference configuration for '.$name.'.');
    $fieldRules[] = 'exists:'.(new $sourceModel())->getTable().',id';
   } elseif ($type === 'select' && ! empty($field['options'])) {
    $fieldRules[] = Rule::in(array_keys($field['options']));
   }

   if (in_array($name, ['default_useful_life_months', 'useful_life_months'], true)) {
    $fieldRules[] = 'integer';
   }

   if ($name === 'salvage_percent') {
    $fieldRules[] = 'max:100';
   }

   $rules[$name] = $fieldRules;
  }

  if ($request->user()?->roles->contains('slug', 'super-admin')) {
   $rules['office_id'] = [$record ? 'nullable' : 'required', 'integer', 'exists:offices,id'];
  }

  $data = $request->validate($rules);

  // Laravel's exists rule checks the table directly and does not apply Eloquent
  // global scopes. Re-check every model-backed reference through its scoped model
  // so a user cannot attach a record from another office by guessing an ID.
  foreach ($definition['fields'] as $field) {
   $name = $field['name'];
   if (($field['type'] ?? '') !== 'select' || empty($field['source']) || empty($data[$name])) continue;
   $sourceModel = $field['source'] === 'users'
    ? \App\Models\User::class
    : config('inventory.resources.'.$field['source'].'.model');
   abort_unless(is_string($sourceModel) && class_exists($sourceModel), 500, 'Invalid inventory reference configuration for '.$name.'.');
   abort_unless($sourceModel::query()->whereKey($data[$name])->exists(), 422, 'The selected '.$field['label'].' is outside your office or unavailable.');
  }

  foreach ($definition['fields'] as $field) {
   $name = $field['name'];

   if (($field['type'] ?? '') === 'password' && empty($data[$name])) {
    unset($data[$name]);
   }

   if (array_key_exists('default', $field)
    && (! array_key_exists($name, $data) || $data[$name] === null || $data[$name] === '')) {
    $data[$name] = $record?->getAttribute($name) ?? $field['default'];
   }
  }

  return $data;
 }
}
