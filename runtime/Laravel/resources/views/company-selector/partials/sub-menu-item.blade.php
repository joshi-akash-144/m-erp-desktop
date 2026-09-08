@php
    $url = isset($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
@endphp

@if(menu_can_access($item))
    @if(isset($item['children']))
        <div class="dropend">
            <a class="dropdown-item dropdown-toggle @if(menu_is_active($item)) active @endif"
               href="#"
               data-bs-toggle="dropdown">
                @if(!empty($item['icon']))
                    <i class="{{ $item['icon'] }} me-2 text-muted" style="width:14px;font-size:13px;"></i>
                @endif
                {{ $item['title'] }}
            </a>
            <div class="dropdown-menu">
                @foreach($item['children'] as $child)
                    @include('company-selector.partials.sub-menu-item', ['item' => $child])
                @endforeach
            </div>
        </div>
    @else
        <a class="dropdown-item @if(menu_is_active($item)) active @endif" href="{{ $url }}">
            @if(!empty($item['icon']))
                <i class="{{ $item['icon'] }} me-2 text-muted" style="width:14px;font-size:13px;"></i>
            @endif
            {{ $item['title'] }}
            @if(isset($item['badge']))
                <span class="badge badge-sm {{ $item['badge']['class'] }} text-uppercase ms-auto">
                    {{ $item['badge']['text'] }}
                </span>
            @endif
        </a>
    @endif
@endif
