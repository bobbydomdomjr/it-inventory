@extends('layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Add ') . $d['singular'])
@section('breadcrumb', $d['title'])

@section('content')
<div class="page-heading">
    <div>
        <div class="eyebrow">
            <a href="{{ route('inventory.index', $resource) }}" class="back-link">{{ $d['title'] }}</a>
            / {{ $record->exists ? 'EDIT' : 'NEW RECORD' }}
        </div>
        <h1>{{ $record->exists ? 'Edit ' . $d['singular'] : 'Add ' . $d['singular'] }}</h1>
        <p>Enter the details below. Required fields are marked with an asterisk.</p>
    </div>
</div>

<div class="panel form-panel">
    <form method="POST" action="{{ $record->exists ? route('inventory.update', [$resource, $record->id]) : route('inventory.store', $resource) }}" novalidate>
        @csrf
        @if($record->exists)
            @method('PUT')
        @endif

        <div class="form-grid">
            @if(auth()->user()->roles->contains('slug', 'super-admin'))
                <div class="form-field">
                    <label for="f_office_id" class="form-label">Owning office <span class="required-mark">*</span></label>
                    <select id="f_office_id" name="office_id" class="form-select" required>
                        <option value="">Select owning office</option>
                        @foreach(\App\Models\Office::where('is_active', true)->orderBy('name')->get() as $office)
                            <option value="{{ $office->id }}" @selected((string) old('office_id', $record->office_id) === (string) $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                    @error('office_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endif
            @foreach($d['fields'] as $field)
                @php($name = $field['name'])
                @php($type = $field['type'] ?? 'text')
                @php($current = $record->{$name})
                @php($fieldValue = old($name, $record->exists ? $current : ($field['default'] ?? $current)))

                @if($resource === 'consumables' && $record->exists && $name === 'quantity_on_hand')
                    @continue
                @endif

                @if($name === 'is_active' && $current !== null)
                    @php($current = $current ? '1' : '0')
                    @php($fieldValue = old($name, $record->exists ? $current : ($field['default'] ?? $current)))
                @endif

                @if($type === 'date' && $fieldValue instanceof \Carbon\CarbonInterface)
                    @php($fieldValue = $fieldValue->format('Y-m-d'))
                @endif

                @php($hasError = $errors->has($name))
                @php($fieldId = 'f_' . $name)

                <div class="form-field {{ $type === 'textarea' ? 'field-full' : '' }}">
                    <label for="{{ $fieldId }}" class="form-label">
                        {{ $field['label'] }}
                        @if(!empty($field['required']))
                            <span class="required-mark">*</span>
                        @endif
                    </label>

                    @if(!empty($field['workflow_managed']))
                        <div class="form-control bg-light text-secondary" aria-readonly="true">
                            {{ $current instanceof \Carbon\CarbonInterface
                                ? $current->format(in_array($name, ['first_response_at', 'sla_due_at', 'resolved_at', 'created_at', 'updated_at'], true) ? 'd M Y, h:i A' : 'd M Y')
                                : ($current ? str_replace('_', ' ', (string) $current) : 'Managed by workflow') }}
                        </div>
                        <div class="form-text">This field changes only through its approval or transaction workflow.</div>
                    @elseif($type === 'textarea')
                        <textarea
                            id="{{ $fieldId }}"
                            name="{{ $name }}"
                            rows="3"
                            class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                            placeholder="Enter {{ strtolower($field['label']) }}"
                            @required(!empty($field['required']))
                            @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}_error" @endif
                        >{{ $fieldValue }}</textarea>
                    @elseif($type === 'select')
                        <select
                            id="{{ $fieldId }}"
                            name="{{ $name }}"
                            class="form-select {{ $hasError ? 'is-invalid' : '' }}"
                            @required(!empty($field['required']))
                            @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}_error" @endif
                        >
                            <option value="">Select {{ strtolower($field['label']) }}</option>
                            @if(isset($field['options']))
                                @foreach($field['options'] as $value => $label)
                                    <option value="{{ $value }}" @selected((string) $fieldValue === (string) $value)>{{ $label }}</option>
                                @endforeach
                            @elseif(isset($options[$name]))
                                @foreach($options[$name] as $value => $label)
                                    <option value="{{ $value }}" @selected((string) $fieldValue === (string) $value)>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    @else
                        <input
                            id="{{ $fieldId }}"
                            type="{{ $type === 'number' ? 'number' : $type }}"
                            @if(isset($field['step'])) step="{{ $field['step'] }}" @endif
                            @if($type === 'number') min="0" @endif
                            name="{{ $name }}"
                            class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                            value="{{ $type === 'password' ? '' : $fieldValue }}"
                            placeholder="{{ $type === 'password' ? 'Leave blank to keep existing value' : 'Enter ' . strtolower($field['label']) }}"
                            @required(!empty($field['required']))
                            @if($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}_error" @endif
                        >
                    @endif

                    @if($hasError)
                        <div class="invalid-feedback d-block" id="{{ $fieldId }}_error">{{ $errors->first($name) }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($resource === 'categories')
            <div class="small text-secondary mt-2" id="categoryStockDefaultHelp">Set the default reorder threshold for new consumable items in this category. Enter 0 to use the system-wide low-stock default; existing stock thresholds are not changed.</div>
            <div class="panel-subsection mt-3" id="categoryCustomFieldsPanel">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div><h2 class="h6 mb-1">Asset-specific fields</h2><p class="small text-secondary mb-0">Define additional fields that appear on asset forms using this category. Keep these specific to the equipment type.</p></div>
                    <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="addCategoryCustomField"><i class="bi bi-plus-lg me-1"></i>Add field</button>
                </div>
                <input type="hidden" name="custom_fields_present" value="1">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-1" style="min-width:760px">
                        <thead><tr><th style="width:22%">Field label</th><th style="width:18%">Field key</th><th style="width:14%">Type</th><th>Options (for select)</th><th style="width:90px">Required</th><th style="width:42px"></th></tr></thead>
                        <tbody id="categoryCustomFieldsBody">
                            @foreach(old('custom_fields', $record->custom_fields ?? []) as $index => $customField)
                                <tr>
                                    <td><input class="form-control form-control-sm" name="custom_fields[{{ $index }}][label]" value="{{ $customField['label'] ?? '' }}" maxlength="80" placeholder="e.g. RAM" required></td>
                                    <td><input class="form-control form-control-sm" name="custom_fields[{{ $index }}][key]" value="{{ $customField['key'] ?? '' }}" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" placeholder="e.g. ram" required></td>
                                    <td><select class="form-select form-select-sm" name="custom_fields[{{ $index }}][type]"><option value="text" @selected(($customField['type'] ?? 'text')==='text')>Text</option><option value="number" @selected(($customField['type'] ?? '')==='number')>Number</option><option value="date" @selected(($customField['type'] ?? '')==='date')>Date</option><option value="select" @selected(($customField['type'] ?? '')==='select')>Select</option><option value="textarea" @selected(($customField['type'] ?? '')==='textarea')>Long text</option></select></td>
                                    <td><input class="form-control form-control-sm" name="custom_fields[{{ $index }}][options]" value="{{ is_array($customField['options'] ?? null) ? implode(', ', $customField['options']) : ($customField['options'] ?? '') }}" placeholder="e.g. 8 GB, 16 GB, 32 GB"></td>
                                    <td class="text-center"><input class="form-check-input" type="checkbox" name="custom_fields[{{ $index }}][required]" value="1" @checked(!empty($customField['required']))></td>
                                    <td><button type="button" class="btn btn-sm btn-light border remove-category-field" aria-label="Remove field"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-text">Field keys use lowercase letters, numbers, and underscores (for example, <code>ram_size</code>). Existing values are retained with each asset.</div>
            </div>
        @endif

        @if($resource === 'assets')
            <input type="hidden" name="useful_life_manual" id="usefulLifeManual" value="0">
            <input type="hidden" name="depreciation_method_manual" id="depreciationMethodManual" value="0">
            <input type="hidden" name="salvage_value_manual" id="salvageValueManual" value="0">
            <div class="panel-subsection mt-3" id="assetCustomFieldsSection" hidden>
                <div class="mb-2"><h2 class="h6 mb-1">Category-specific information</h2><p class="small text-secondary mb-0">Additional fields configured for the selected asset category.</p></div>
                <div class="form-grid" id="assetCustomFieldsBody"></div>
            </div>
        @endif

        @if($resource === 'consumables')
            <input type="hidden" name="reorder_level_manual" id="reorderLevelManual" value="0">
        @endif

        <div class="form-actions">
            <a href="{{ $record->exists ? route('inventory.show', [$resource, $record->id]) : route('inventory.index', $resource) }}" class="btn btn-light border">Cancel</a>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2 me-2"></i>{{ $record->exists ? 'Save changes' : 'Create record' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if($resource === 'categories')
    const body = document.getElementById('categoryCustomFieldsBody');
    const add = document.getElementById('addCategoryCustomField');
    let nextIndex = Math.max(0, body ? body.querySelectorAll('tr').length : 0);
    add?.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = `<td><input class="form-control form-control-sm" name="custom_fields[${nextIndex}][label]" maxlength="80" placeholder="e.g. RAM" required></td>
        <td><input class="form-control form-control-sm" name="custom_fields[${nextIndex}][key]" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" placeholder="e.g. ram" required></td>
        <td><select class="form-select form-select-sm" name="custom_fields[${nextIndex}][type]"><option value="text">Text</option><option value="number">Number</option><option value="date">Date</option><option value="select">Select</option><option value="textarea">Long text</option></select></td>
        <td><input class="form-control form-control-sm" name="custom_fields[${nextIndex}][options]" placeholder="e.g. 8 GB, 16 GB, 32 GB"></td>
        <td class="text-center"><input class="form-check-input" type="checkbox" name="custom_fields[${nextIndex}][required]" value="1"></td>
        <td><button type="button" class="btn btn-sm btn-light border remove-category-field" aria-label="Remove field"><i class="bi bi-x-lg"></i></button></td>`;
        body?.appendChild(row); nextIndex += 1;
    });
    body?.addEventListener('click', event => {
        const button = event.target.closest('.remove-category-field');
        if (button) button.closest('tr')?.remove();
    });
    const categoryType = document.getElementById('f_type');
    const stockField = document.getElementById('f_default_min_stock_level')?.closest('.form-field');
    const stockHelp = document.getElementById('categoryStockDefaultHelp');
    const assetSettings = ['f_default_useful_life_months','f_useful_life_months','f_depreciation_method','f_salvage_percent'].map(id => document.getElementById(id)?.closest('.form-field')).filter(Boolean);
    const customPanel = document.getElementById('categoryCustomFieldsPanel');
    function syncCategoryType() {
        if (stockField) stockField.hidden = categoryType?.value !== 'consumable';
        if (stockHelp) stockHelp.hidden = categoryType?.value !== 'consumable';
        assetSettings.forEach(field => field.hidden = categoryType?.value !== 'asset');
        if (customPanel) customPanel.hidden = categoryType?.value !== 'asset';
    }
    categoryType?.addEventListener('change', syncCategoryType); syncCategoryType();
    @endif

    @if($resource === 'assets')
    const categories = @json($assetCategoryDefinitions ?? []);
    const isExistingAsset = @json($record->exists);
    const currentValues = @json(old('custom_attributes', $assetCustomFieldValues ?? []));
    const categorySelect = document.getElementById('f_category_id');
    const customSection = document.getElementById('assetCustomFieldsSection');
    const customBody = document.getElementById('assetCustomFieldsBody');
    const usefulLife = document.getElementById('f_useful_life_months');
    const depreciation = document.getElementById('f_depreciation_method');
    const salvageValue = document.getElementById('f_salvage_value');
    usefulLife?.addEventListener('input', () => { usefulLife.dataset.userEdited = '1'; document.getElementById('usefulLifeManual').value = '1'; });
    depreciation?.addEventListener('change', () => { depreciation.dataset.userEdited = '1'; document.getElementById('depreciationMethodManual').value = '1'; });
    salvageValue?.addEventListener('input', () => { salvageValue.dataset.userEdited = '1'; document.getElementById('salvageValueManual').value = '1'; });
    function renderAssetFields(applyCategoryDefaults = false) {
        if (!categorySelect || !customBody) return;
        const category = categories[categorySelect.value];
        customBody.replaceChildren();
        const fields = category?.custom_fields || [];
        customSection.hidden = !fields.length;
        fields.forEach(field => {
            const wrapper = document.createElement('div'); wrapper.className = 'form-field';
            const label = document.createElement('label'); label.className = 'form-label'; label.textContent = field.label + (field.required ? ' *' : '');
            const name = 'custom_attributes[' + field.key + ']';
            const value = currentValues[field.key] ?? '';
            let input;
            if (field.type === 'textarea') { input = document.createElement('textarea'); input.rows = 2; input.className = 'form-control'; input.value = value; }
            else if (field.type === 'select') {
                input = document.createElement('select'); input.className = 'form-select';
                const placeholder = document.createElement('option'); placeholder.value = ''; placeholder.textContent = 'Select ' + field.label; input.appendChild(placeholder);
                (field.options || []).forEach(option => { const item = document.createElement('option'); item.value = option; item.textContent = option; input.appendChild(item); }); input.value = value;
            } else { input = document.createElement('input'); input.type = ['number','date'].includes(field.type) ? field.type : 'text'; input.className = 'form-control'; input.value = value; if (field.type === 'number') input.min = '0'; }
            input.name = name; input.required = !!field.required; input.id = 'custom_' + field.key;
            label.htmlFor = input.id; wrapper.append(label, input); customBody.appendChild(wrapper);
        });
        if (category && applyCategoryDefaults) {
            if (category.useful_life_months && usefulLife && !usefulLife.dataset.userEdited) usefulLife.value = category.useful_life_months;
            if (category.depreciation_method && category.depreciation_method !== 'not_configured' && depreciation && !depreciation.dataset.userEdited) depreciation.value = category.depreciation_method;
            if (category.salvage_percent > 0 && salvageValue && !salvageValue.dataset.userEdited) {
                const cost = parseFloat(document.getElementById('f_purchase_cost')?.value || '0');
                salvageValue.value = (cost * Number(category.salvage_percent) / 100).toFixed(2);
            }
        }
    }
    categorySelect?.addEventListener('change', function () { Object.keys(currentValues).forEach(key => delete currentValues[key]); renderAssetFields(true); });
    document.getElementById('f_purchase_cost')?.addEventListener('input', function () {
        const category = categories[categorySelect?.value];
        if (category && Number(category.salvage_percent) > 0 && salvageValue && !salvageValue.dataset.userEdited) salvageValue.value = (parseFloat(this.value || '0') * Number(category.salvage_percent) / 100).toFixed(2);
    });
    renderAssetFields(!isExistingAsset);
    @endif

    @if($resource === 'consumables')
    const categoryDefaults = @json($consumableCategoryStockDefaults ?? []);
    const categorySelect = document.getElementById('f_category_id');
    const reorderLevel = document.getElementById('f_reorder_level');
    const manualFlag = document.getElementById('reorderLevelManual');
    reorderLevel?.addEventListener('input', () => { if (manualFlag) manualFlag.value = '1'; });
    categorySelect?.addEventListener('change', function () {
        if (reorderLevel && manualFlag?.value !== '1') reorderLevel.value = categoryDefaults[this.value] ?? 0;
    });
    if (categorySelect?.value && reorderLevel && manualFlag?.value !== '1' && !@json($record->exists)) reorderLevel.value = categoryDefaults[categorySelect.value] ?? reorderLevel.value;
    @endif
});
</script>
@endpush

@endsection
