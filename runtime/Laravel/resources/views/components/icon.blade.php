@php
    // Get base icon class from config
    $iconClass = config("icons.{$name}");

    // Build extra classes
    $extra = '';

    // Font size
    if ($size) {
        $extra .= " fs-{$size}";
    }

    // Text color class
    if ($color) {
        $extra .= " text-{$color}";
    }

    // Additional custom classes
    if ($class) {
        $extra .= " {$class}";
    }
@endphp

<i class="{{ $iconClass }}{{ $extra }}"></i>

{{-- 
Normal use
<x-icon name="item" />

✔ With size (Bootstrap fs-3, fs-4…)
<x-icon name="save" size="4" />

✔ With color (Bootstrap colors)
<x-icon name="delete" color="danger" />

✔ With custom classes
<x-icon name="edit" class="me-1 ms-2" />

✔ Combine everything
<x-icon name="item" size="5" color="primary" class="me-2" /> --}}


