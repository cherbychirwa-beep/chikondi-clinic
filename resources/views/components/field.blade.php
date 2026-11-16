@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'col' => 'col-md-6'])
<div class="{{ $col }}">
    <label for="{{ $name }}" class="form-label">{{ $label }}@if($required) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
           @if($type !== 'password') value="{{ old($name, $value) }}" @endif
           @required($required)
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($help)<div class="form-text">{{ $help }}</div>@endif
</div>
