{{-- Expects $clear (URL to reset filters). Adds records-per-page, apply and clear controls. --}}
<div class="col-auto">
    <label for="per_page" class="form-label">Per page</label>
    <select name="per_page" id="per_page" class="form-select">
        @foreach(config('chikondi.per_page_options') as $n)
            <option value="{{ $n }}" @selected((int) request('per_page', config('chikondi.default_per_page')) === $n)>{{ $n }}</option>
        @endforeach
    </select>
</div>
<div class="col-auto d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
    <a href="{{ $clear }}" class="btn btn-outline-secondary">Clear filters</a>
</div>
