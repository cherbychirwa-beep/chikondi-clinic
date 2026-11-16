@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => 'Select…', 'col' => 'col-md-6', 'help' => null])
<div class="{{ $col }}">
    <label for="{{ $name }}" class="form-label">{{ $label }}@if($required) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $key => $text)
            <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($help)<div class="form-text">{{ $help }}</div>@endif
</div>
