{{-- One prescription row. $i = index (or the "__i__" placeholder), $row = old values, $medicines = catalogue --}}
<div class="rx-row border rounded p-3 mb-2 bg-light">
    <div class="row g-2">
        <div class="col-md-5">
            <label class="form-label">Medicine <span class="text-danger">*</span></label>
            <select name="prescriptions[{{ $i }}][medicine_id]" class="form-select {{ $errors->has("prescriptions.$i.medicine_id") ? 'is-invalid' : '' }}" required>
                <option value="">Select medicine…</option>
                @foreach($medicines as $m)
                    <option value="{{ $m->id }}" @selected((string) ($row['medicine_id'] ?? '') === (string) $m->id)>{{ $m->label }}</option>
                @endforeach
            </select>
            @error("prescriptions.$i.medicine_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Dosage <span class="text-danger">*</span></label>
            <input type="text" name="prescriptions[{{ $i }}][dosage]" value="{{ $row['dosage'] ?? '' }}" maxlength="100" placeholder="1 tablet" required
                   class="form-control {{ $errors->has("prescriptions.$i.dosage") ? 'is-invalid' : '' }}">
            @error("prescriptions.$i.dosage")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Frequency <span class="text-danger">*</span></label>
            <input type="text" name="prescriptions[{{ $i }}][frequency]" value="{{ $row['frequency'] ?? '' }}" maxlength="100" placeholder="3 times daily" required
                   class="form-control {{ $errors->has("prescriptions.$i.frequency") ? 'is-invalid' : '' }}">
            @error("prescriptions.$i.frequency")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Duration <span class="text-danger">*</span></label>
            <input type="text" name="prescriptions[{{ $i }}][duration]" value="{{ $row['duration'] ?? '' }}" maxlength="100" placeholder="5 days" required
                   class="form-control {{ $errors->has("prescriptions.$i.duration") ? 'is-invalid' : '' }}">
            @error("prescriptions.$i.duration")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger w-100 remove-rx" title="Remove medicine" aria-label="Remove medicine"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="col-12">
            <label class="form-label">Additional instructions</label>
            <input type="text" name="prescriptions[{{ $i }}][instructions]" value="{{ $row['instructions'] ?? '' }}" maxlength="1000" class="form-control" placeholder="e.g. take after meals">
        </div>
    </div>
</div>
