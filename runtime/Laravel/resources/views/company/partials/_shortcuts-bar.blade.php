{{--
    Tally/Busy-style persistent keyboard shortcuts bar.

    Usage in any create.blade.php:

        @include('company.partials._shortcuts-bar', [
            'barModuleLabel'     => 'Purchase Invoice',
            'barModuleShortcuts' => [
                ['key' => 'Alt+G', 'label' => 'GRN',            'type' => 'info'],
                ['key' => 'Alt+P', 'label' => 'Purchase Order',  'type' => 'info'],
                ['key' => 'Alt+L', 'label' => 'Ledger',          'type' => 'info'],
                ['key' => 'Alt+V', 'label' => 'Debit Note',      'type' => 'info'],
            ],
        ])

    type values: primary | success | danger | warning | info (default)
--}}

@php
    $barModuleLabel     = $barModuleLabel     ?? null;
    $barModuleShortcuts = $barModuleShortcuts ?? [];
@endphp

<div id="shortcuts_bar" class="d-print-none" @if(count($barModuleShortcuts) == 0) style="display: none;" @endif>
<style>
    #shortcuts_bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        background: #1a2332;
        border-top: 2px solid #206bc4;
        font-family: var(--tblr-font-monospace, 'Courier New', monospace);
        transition: transform 0.25s ease;
    }

    #shortcuts_bar.collapsed { transform: translateY(calc(100% - 24px)); }

    /* ── Toggle header ── */
    #sc_bar_toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #206bc4;
        color: #fff;
        padding: 2px 12px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        user-select: none;
        height: 24px;
    }
    #sc_bar_toggle:hover { background: #1855a8; }

    /* ── Bar body ── */
    #sc_bar_body {
        display: flex;
        align-items: stretch;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding: 2px 6px;
        gap: 0;
        scrollbar-width: none;
    }
    #sc_bar_body::-webkit-scrollbar { display: none; }

    /* ── Separator ── */
    .sc-sep {
        width: 1px;
        background: #3a5570;
        margin: 2px 4px;
        flex-shrink: 0;
    }

    /* ── Module badge ── */
    .sc-module-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        background: #0f1c2e;
        border: 1px solid #3a5a80;
        border-radius: 3px;
        color: #9dd6ff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        white-space: nowrap;
        margin-right: 4px;
        text-transform: uppercase;
        align-self: center;
    }

    /* ── Shortcut item ── */
    .sc-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 10px 2px 8px;
        border-right: 1px solid #2d3f55;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .sc-item:last-child { border-right: none; }

    .sc-key {
        display: inline-block;
        background: #0f1c2e;
        border: 1px solid #3a5a80;
        border-radius: 3px;
        color: #7ec8f5;
        font-size: 10px;
        font-weight: 700;
        padding: 0 5px;
        line-height: 16px;
        min-width: 22px;
        text-align: center;
    }
    .sc-label { color: #c8d9f0; font-size: 11px; }

    /* colour variants */
    .sc-primary  .sc-key { background:#1e4a7a; border-color:#4a9af5; color:#fff; }
    .sc-success  .sc-key { background:#1a3d20; border-color:#4caf66; color:#7df5a0; }
    .sc-danger   .sc-key { background:#4a1a1a; border-color:#e05252; color:#f77; }
    .sc-warning  .sc-key { background:#3d2e00; border-color:#e0a040; color:#ffd070; }
    .sc-info     .sc-key { background:#0e2e40; border-color:#40b0d0; color:#70d8f5; }
    .sc-info     .sc-label { color:#9dd6ff; }

    /* F1 special */
    .sc-f1 .sc-key  { background:#1e4060; border-color:#5ab0f5; color:#9dd6ff; }
    .sc-f1 .sc-label{ color:#9dd6ff; }
    .sc-f1 { cursor: pointer; }
    .sc-f1:hover .sc-label { text-decoration: underline; }

    @if(count($barModuleShortcuts) > 0)
    body { padding-bottom: 58px !important; }
    @endif
</style>

    {{-- ── Toggle header ── --}}
    <!-- <div id="sc_bar_toggle" onclick="toggleShortcutsBar()">
        <span>
            <i class="fa-solid fa-keyboard me-1" style="font-size:10px;"></i>
            @if($barModuleLabel) {{ strtoupper($barModuleLabel) }} — @endif
            KEYBOARD SHORTCUTS
        </span>
        <span id="sc_bar_chevron">&#9650;</span>
    </div> -->

    {{-- ── Bar body ── --}}
    <div id="sc_bar_body">

        {{-- Common form shortcuts --}}
        <div class="sc-item sc-primary custom-shortcut" data-key="alts" data-click=".form-save-btn" onclick="document.querySelector('.form-save-btn')?.click()">
            <!-- <span class="sc-key">Alt+S</span>
            <span class="sc-label">Save</span> -->
        </div>
        <!-- <div class="sc-item sc-success custom-shortcut" data-key="altn" data-click=".add-row-btn, #add_item_row, #add_particular_row, #add_delivery_challan_item_row, #add_grn_item_row" onclick="document.querySelector('.add-row-btn, #add_item_row, #add_particular_row, #add_delivery_challan_item_row, #add_grn_item_row')?.click()">
            <span class="sc-key">Alt+N</span>
            <span class="sc-label">Add Row</span>
        </div>
        <div class="sc-item sc-danger custom-shortcut" data-key="altd" data-click=".delete-row-btn, .btn-remove-row, .delete-particular-row, .erp-btn-icon.delete" onclick="document.querySelector('.delete-row-btn, .btn-remove-row, .delete-particular-row, .erp-btn-icon.delete')?.click()">
            <span class="sc-key">Alt+D</span>
            <span class="sc-label">Del Row</span>
        </div>
        <div class="sc-item sc-warning custom-shortcut" data-key="altr" data-click=".btn-clear, .btn-reset, button[type='reset'], button[onclick*='reset']" onclick="document.querySelector('.btn-clear, .btn-reset, button[type=\'reset\'], button[onclick*=\'reset\']')?.click()">
            <span class="sc-key">Alt+R</span>
            <span class="sc-label">Clear</span>
        </div>
        <div class="sc-item custom-shortcut" data-key="altb" data-click=".back-btn" onclick="document.querySelector('.back-btn')?.click()">
            <span class="sc-key">Alt+B</span>
            <span class="sc-label">Back</span>
        </div>
        <div class="sc-item custom-shortcut" data-key="alte" data-click=".edit-mode-btn, .btn-edit, a[href*='edit']" onclick="document.querySelector('.edit-mode-btn, .btn-edit, a[href*=\'edit\']')?.click()">
            <span class="sc-key">Alt+E</span>
            <span class="sc-label">Edit Mode</span>
        </div>
        <div class="sc-item">
            <span class="sc-key">Tab</span>
            <span class="sc-label">Next Field</span>
        </div>
        <div class="sc-item">
            <span class="sc-key">Shift+Tab</span>
            <span class="sc-label">Prev Field</span>
        </div>
        <div class="sc-item">
            <span class="sc-key">Enter</span>
            <span class="sc-label">Confirm</span>
        </div>
        <div class="sc-item custom-shortcut" data-key="escape" data-click=".btn-close, [data-bs-dismiss='modal']" onclick="document.querySelector('.btn-close, [data-bs-dismiss=\'modal\']')?.click()">
            <span class="sc-key">Esc</span>
            <span class="sc-label">Close</span>
        </div> -->

        {{-- Module-specific shortcuts (injected from each create.blade.php) --}}
        @if(count($barModuleShortcuts) > 0)
            <div class="sc-sep"></div>
            @foreach($barModuleShortcuts as $sc)
                @if(!isset($sc['permission']) || auth()->user()->can($sc['permission']))
                    @php $type = $sc['type'] ?? 'info'; @endphp
                    <div class="sc-item sc-{{ $type }} custom-shortcut" 
                         @if(isset($sc['id'])) id="{{ $sc['id'] }}" @endif
                         @if(isset($sc['url'])) data-url="{{ $sc['url'] }}" @endif 
                         @if(isset($sc['click'])) data-click="{{ $sc['click'] }}" @endif
                         @if(isset($sc['key'])) data-key="{{ strtolower(str_replace('+', '', $sc['key'])) }}" @endif
                         style="{{ (isset($sc['url']) || isset($sc['click']) || isset($sc['id'])) ? 'cursor: pointer;' : '' }}"
                         @if(isset($sc['url'])) onclick="window.location.href='{{ $sc['url'] }}'" 
                         @elseif(isset($sc['click'])) onclick="document.querySelector('{{ $sc['click'] }}')?.click()"
                         @endif>
                        <span class="sc-key">{{ $sc['key'] }}</span>
                        <span class="sc-label">{{ $sc['label'] }}</span>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Always last: F1 help --}}
        <!-- <div class="sc-sep"></div>
        <div class="sc-item sc-f1" data-bs-toggle="modal" data-bs-target="#shortcutsModal">
            <span class="sc-key">F1</span>
            <span class="sc-label">Help</span>
        </div> -->

    </div>
</div>

<script>
(function () {
    var KEY  = 'sc_bar_collapsed';
    // var bar  = document.getElementById('shortcuts_bar');
    // var chev = document.getElementById('sc_bar_chevron');

    // if (localStorage.getItem(KEY) === '1') {
    //     bar.classList.add('collapsed');
    //     chev.innerHTML = '&#9660;';
    // }

    // window.toggleShortcutsBar = function () {
    //     var c = bar.classList.toggle('collapsed');
    //     chev.innerHTML = c ? '&#9660;' : '&#9650;';
    //     localStorage.setItem(KEY, c ? '1' : '0');
    // };

    // Handle global keyboard shortcuts for custom shortcuts
    document.addEventListener('keydown', function(e) {
        let keys = [];
        if (e.ctrlKey) keys.push('ctrl');
        if (e.altKey) keys.push('alt');
        if (e.shiftKey) keys.push('shift');
        
        let k = e.key ? e.key.toLowerCase() : '';
        if (k && k !== 'control' && k !== 'alt' && k !== 'shift' && k !== 'meta') {
            keys.push(k);
        }
        
        let pressedKey = keys.join('');
        if (!pressedKey) return;
        
        let shortcuts = document.querySelectorAll('.custom-shortcut');
        for (let i = 0; i < shortcuts.length; i++) {
            let scKey = shortcuts[i].getAttribute('data-key');
            if (scKey && scKey.toLowerCase() === pressedKey) {
                let url = shortcuts[i].getAttribute('data-url');
                let clickTarget = shortcuts[i].getAttribute('data-click');
                
                // Immediately prevent default to stop browser shortcuts
                e.preventDefault();

                if (url) {
                    window.location.href = url;
                } else if (clickTarget) {
                    let allTargets = document.querySelectorAll(clickTarget);
                    if (allTargets.length > 0) {
                        // For delete/remove, click the last one, otherwise the first one
                        if (clickTarget.includes('delete') || clickTarget.includes('remove')) {
                            allTargets[allTargets.length - 1].click();
                        } else {
                            allTargets[0].click();
                        }
                    }
                } else {
                    shortcuts[i].click();
                }
                break;
            }
        }
    });
})();
</script>
