<?php $__env->startSection('title', ($record->exists ? 'Edit ' : 'Add ') . $d['singular']); ?>
<?php $__env->startSection('breadcrumb', $d['title']); ?>

<?php $__env->startSection('content'); ?>
<div class="page-heading">
    <div>
        <div class="eyebrow">
            <a href="<?php echo e(route('inventory.index', $resource)); ?>" class="back-link"><?php echo e($d['title']); ?></a>
            / <?php echo e($record->exists ? 'EDIT' : 'NEW RECORD'); ?>

        </div>
        <h1><?php echo e($record->exists ? 'Edit ' . $d['singular'] : 'Add ' . $d['singular']); ?></h1>
        <p>Enter the details below. Required fields are marked with an asterisk.</p>
    </div>
</div>

<div class="panel form-panel">
    <form method="POST" action="<?php echo e($record->exists ? route('inventory.update', [$resource, $record->id]) : route('inventory.store', $resource)); ?>" novalidate>
        <?php echo csrf_field(); ?>
        <?php if($record->exists): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?>

        <div class="form-grid">
            <?php if(auth()->user()->roles->contains('slug', 'super-admin')): ?>
                <div class="form-field">
                    <label for="f_office_id" class="form-label">Owning office <span class="required-mark">*</span></label>
                    <select id="f_office_id" name="office_id" class="form-select" required>
                        <option value="">Select owning office</option>
                        <?php $__currentLoopData = \App\Models\Office::where('is_active', true)->orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $office): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($office->id); ?>" <?php if((string) old('office_id', $record->office_id) === (string) $office->id): echo 'selected'; endif; ?>><?php echo e($office->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['office_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            <?php endif; ?>
            <?php $__currentLoopData = $d['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($name = $field['name']); ?>
                <?php ($type = $field['type'] ?? 'text'); ?>
                <?php ($current = $record->{$name}); ?>
                <?php ($fieldValue = old($name, $record->exists ? $current : ($field['default'] ?? $current))); ?>

                <?php if($resource === 'consumables' && $record->exists && $name === 'quantity_on_hand'): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <?php if($name === 'is_active' && $current !== null): ?>
                    <?php ($current = $current ? '1' : '0'); ?>
                    <?php ($fieldValue = old($name, $record->exists ? $current : ($field['default'] ?? $current))); ?>
                <?php endif; ?>

                <?php if($type === 'date' && $fieldValue instanceof \Carbon\CarbonInterface): ?>
                    <?php ($fieldValue = $fieldValue->format('Y-m-d')); ?>
                <?php endif; ?>

                <?php ($hasError = $errors->has($name)); ?>
                <?php ($fieldId = 'f_' . $name); ?>

                <div class="form-field <?php echo e($type === 'textarea' ? 'field-full' : ''); ?>">
                    <label for="<?php echo e($fieldId); ?>" class="form-label">
                        <?php echo e($field['label']); ?>

                        <?php if(!empty($field['required'])): ?>
                            <span class="required-mark">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if(!empty($field['workflow_managed'])): ?>
                        <div class="form-control bg-light text-secondary" aria-readonly="true">
                            <?php echo e($current instanceof \Carbon\CarbonInterface
                                ? $current->format(in_array($name, ['first_response_at', 'sla_due_at', 'resolved_at', 'created_at', 'updated_at'], true) ? 'd M Y, h:i A' : 'd M Y')
                                : ($current ? str_replace('_', ' ', (string) $current) : 'Managed by workflow')); ?>

                        </div>
                        <div class="form-text">This field changes only through its approval or transaction workflow.</div>
                    <?php elseif($type === 'textarea'): ?>
                        <textarea
                            id="<?php echo e($fieldId); ?>"
                            name="<?php echo e($name); ?>"
                            rows="3"
                            class="form-control <?php echo e($hasError ? 'is-invalid' : ''); ?>"
                            placeholder="Enter <?php echo e(strtolower($field['label'])); ?>"
                            <?php if(!empty($field['required'])): echo 'required'; endif; ?>
                            <?php if($hasError): ?> aria-invalid="true" aria-describedby="<?php echo e($fieldId); ?>_error" <?php endif; ?>
                        ><?php echo e($fieldValue); ?></textarea>
                    <?php elseif($type === 'select'): ?>
                        <select
                            id="<?php echo e($fieldId); ?>"
                            name="<?php echo e($name); ?>"
                            class="form-select <?php echo e($hasError ? 'is-invalid' : ''); ?>"
                            <?php if(!empty($field['required'])): echo 'required'; endif; ?>
                            <?php if($hasError): ?> aria-invalid="true" aria-describedby="<?php echo e($fieldId); ?>_error" <?php endif; ?>
                        >
                            <option value="">Select <?php echo e(strtolower($field['label'])); ?></option>
                            <?php if(isset($field['options'])): ?>
                                <?php $__currentLoopData = $field['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if((string) $fieldValue === (string) $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php elseif(isset($options[$name])): ?>
                                <?php $__currentLoopData = $options[$name]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if((string) $fieldValue === (string) $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </select>
                    <?php else: ?>
                        <input
                            id="<?php echo e($fieldId); ?>"
                            type="<?php echo e($type === 'number' ? 'number' : $type); ?>"
                            <?php if(isset($field['step'])): ?> step="<?php echo e($field['step']); ?>" <?php endif; ?>
                            <?php if($type === 'number'): ?> min="0" <?php endif; ?>
                            name="<?php echo e($name); ?>"
                            class="form-control <?php echo e($hasError ? 'is-invalid' : ''); ?>"
                            value="<?php echo e($type === 'password' ? '' : $fieldValue); ?>"
                            placeholder="<?php echo e($type === 'password' ? 'Leave blank to keep existing value' : 'Enter ' . strtolower($field['label'])); ?>"
                            <?php if(!empty($field['required'])): echo 'required'; endif; ?>
                            <?php if($hasError): ?> aria-invalid="true" aria-describedby="<?php echo e($fieldId); ?>_error" <?php endif; ?>
                        >
                    <?php endif; ?>

                    <?php if($hasError): ?>
                        <div class="invalid-feedback d-block" id="<?php echo e($fieldId); ?>_error"><?php echo e($errors->first($name)); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php if($resource === 'categories'): ?>
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
                            <?php $__currentLoopData = old('custom_fields', $record->custom_fields ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $customField): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><input class="form-control form-control-sm" name="custom_fields[<?php echo e($index); ?>][label]" value="<?php echo e($customField['label'] ?? ''); ?>" maxlength="80" placeholder="e.g. RAM" required></td>
                                    <td><input class="form-control form-control-sm" name="custom_fields[<?php echo e($index); ?>][key]" value="<?php echo e($customField['key'] ?? ''); ?>" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" placeholder="e.g. ram" required></td>
                                    <td><select class="form-select form-select-sm" name="custom_fields[<?php echo e($index); ?>][type]"><option value="text" <?php if(($customField['type'] ?? 'text')==='text'): echo 'selected'; endif; ?>>Text</option><option value="number" <?php if(($customField['type'] ?? '')==='number'): echo 'selected'; endif; ?>>Number</option><option value="date" <?php if(($customField['type'] ?? '')==='date'): echo 'selected'; endif; ?>>Date</option><option value="select" <?php if(($customField['type'] ?? '')==='select'): echo 'selected'; endif; ?>>Select</option><option value="textarea" <?php if(($customField['type'] ?? '')==='textarea'): echo 'selected'; endif; ?>>Long text</option></select></td>
                                    <td><input class="form-control form-control-sm" name="custom_fields[<?php echo e($index); ?>][options]" value="<?php echo e(is_array($customField['options'] ?? null) ? implode(', ', $customField['options']) : ($customField['options'] ?? '')); ?>" placeholder="e.g. 8 GB, 16 GB, 32 GB"></td>
                                    <td class="text-center"><input class="form-check-input" type="checkbox" name="custom_fields[<?php echo e($index); ?>][required]" value="1" <?php if(!empty($customField['required'])): echo 'checked'; endif; ?>></td>
                                    <td><button type="button" class="btn btn-sm btn-light border remove-category-field" aria-label="Remove field"><i class="bi bi-x-lg"></i></button></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-text">Field keys use lowercase letters, numbers, and underscores (for example, <code>ram_size</code>). Existing values are retained with each asset.</div>
            </div>
        <?php endif; ?>

        <?php if($resource === 'assets'): ?>
            <input type="hidden" name="useful_life_manual" id="usefulLifeManual" value="0">
            <input type="hidden" name="depreciation_method_manual" id="depreciationMethodManual" value="0">
            <input type="hidden" name="salvage_value_manual" id="salvageValueManual" value="0">
            <div class="panel-subsection mt-3" id="assetCustomFieldsSection" hidden>
                <div class="mb-2"><h2 class="h6 mb-1">Category-specific information</h2><p class="small text-secondary mb-0">Additional fields configured for the selected asset category.</p></div>
                <div class="form-grid" id="assetCustomFieldsBody"></div>
            </div>
        <?php endif; ?>

        <?php if($resource === 'consumables'): ?>
            <input type="hidden" name="reorder_level_manual" id="reorderLevelManual" value="0">
        <?php endif; ?>

        <div class="form-actions">
            <a href="<?php echo e($record->exists ? route('inventory.show', [$resource, $record->id]) : route('inventory.index', $resource)); ?>" class="btn btn-light border">Cancel</a>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2 me-2"></i><?php echo e($record->exists ? 'Save changes' : 'Create record'); ?>

            </button>
        </div>
    </form>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if($resource === 'categories'): ?>
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
    <?php endif; ?>

    <?php if($resource === 'assets'): ?>
    const categories = <?php echo json_encode($assetCategoryDefinitions ?? [], 15, 512) ?>;
    const isExistingAsset = <?php echo json_encode($record->exists, 15, 512) ?>;
    const currentValues = <?php echo json_encode(old('custom_attributes', $assetCustomFieldValues ?? []), 512) ?>;
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
    <?php endif; ?>

    <?php if($resource === 'consumables'): ?>
    const categoryDefaults = <?php echo json_encode($consumableCategoryStockDefaults ?? [], 15, 512) ?>;
    const categorySelect = document.getElementById('f_category_id');
    const reorderLevel = document.getElementById('f_reorder_level');
    const manualFlag = document.getElementById('reorderLevelManual');
    reorderLevel?.addEventListener('input', () => { if (manualFlag) manualFlag.value = '1'; });
    categorySelect?.addEventListener('change', function () {
        if (reorderLevel && manualFlag?.value !== '1') reorderLevel.value = categoryDefaults[this.value] ?? 0;
    });
    if (categorySelect?.value && reorderLevel && manualFlag?.value !== '1' && !<?php echo json_encode($record->exists, 15, 512) ?>) reorderLevel.value = categoryDefaults[categorySelect.value] ?? reorderLevel.value;
    <?php endif; ?>
});
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\it-inventory\resources\views/inventory/form.blade.php ENDPATH**/ ?>