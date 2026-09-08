<span data-bs-toggle="tooltip" data-bs-placement="top" title="Calculator (Alt + C)" style="position: fixed; bottom: 30px; left: 30px; z-index: 1050;" class="d-none d-md-block">
    <button type="button" class="btn btn-warning btn-icon rounded-circle shadow-lg d-flex align-items-center justify-content-center" 
            data-bs-toggle="modal" data-bs-target="#globalCalculatorModal"
            style="width: 60px; height: 60px;">
        <i class="fa-solid fa-calculator fs-2"></i>
    </button>
</span>

<div class="modal modal-blur fade" id="globalCalculatorModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document" style="position: absolute;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden; width: 320px; margin: auto;">
            <div class="modal-header bg-dark text-white py-2 cursor-pointer user-select-none" id="calc-modal-header" title="Drag to move">
                <div class="modal-title font-monospace fw-bold mb-0 d-flex align-items-center" style="font-size: 15px;">
                    <i class="fa-solid fa-calculator text-warning me-2"></i> Calculator
                    <button type="button" class="btn btn-success btn-sm ms-3 d-flex align-items-center justify-content-center" id="calc-paste-btn" title="Paste to last input (Enter)" style="width: 28px; height: 28px; padding: 0;">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
                <button type="button" class="btn-close btn-close-white m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <div class="calculator-container">
                    <div class="mb-3">
                        <div id="calc-history" class="text-end text-muted font-monospace small px-2" style="min-height: 20px;"></div>
                        <input type="text" id="calc-display" class="form-control text-end font-monospace fw-bold text-dark bg-white shadow-sm border-secondary" readonly value="0" style="font-size: 26px; height: 55px; letter-spacing: 1px;">
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-3"><button type="button" class="btn btn-outline-danger w-100 fw-bold calc-btn" data-action="clear">C</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-warning w-100 fw-bold calc-btn" data-action="delete"><i class="fa-solid fa-delete-left"></i></button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-secondary w-100 fw-bold calc-btn" data-val="%">%</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-primary w-100 fw-bold calc-btn fs-4" data-val="/">/</button></div>

                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="7">7</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="8">8</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="9">9</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-primary w-100 fw-bold calc-btn fs-5" data-val="*">x</button></div>

                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="4">4</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="5">5</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="6">6</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-primary w-100 fw-bold calc-btn fs-4" data-val="-">-</button></div>

                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="1">1</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="2">2</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="3">3</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-primary w-100 fw-bold calc-btn fs-4" data-val="+">+</button></div>

                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="00">00</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn" data-val="0">0</button></div>
                        <div class="col-3"><button type="button" class="btn btn-white shadow-sm border w-100 fw-bold calc-btn num-btn fs-4" data-val=".">.</button></div>
                        <div class="col-3"><button type="button" class="btn btn-primary shadow-sm w-100 fw-bold calc-btn fs-4" data-action="calculate">=</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .calculator-container .calc-btn {
        height: 50px;
        font-size: 1.25rem;
        transition: all 0.1s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    .calculator-container .num-btn {
        color: #333;
    }
    .calculator-container .calc-btn:active, 
    .calculator-container .calc-btn.btn-pressed {
        transform: scale(0.90) !important;
        filter: brightness(0.85);
        box-shadow: inset 0 3px 5px rgba(0,0,0,0.2) !important;
    }
    #calc-modal-header {
        cursor: grab;
    }
    #calc-modal-header:active {
        cursor: grabbing;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const display = document.getElementById('calc-display');
        const historyDisplay = document.getElementById('calc-history');
        const buttons = document.querySelectorAll('.calc-btn');
        let currentExpression = '';
        let shouldResetDisplay = false;
        
        let lastActiveElement = null;
        let lastTabulatorCell = null;
        let pendingPasteValue = null;

        // Global tracking for inputs (in case user clicks FAB instead of Alt+C)
        document.addEventListener('focusin', function(e) {
            if (e.target.tagName === 'INPUT' && e.target.id !== 'calc-display' && !e.target.readOnly) {
                lastActiveElement = e.target;
                lastTabulatorCell = e.target.closest('.tabulator-cell');
            }
        });

        function updateDisplay(val, hist = '') {
            display.value = val;
            historyDisplay.textContent = hist;
        }

        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                const val = this.getAttribute('data-val');
                const action = this.getAttribute('data-action');

                if (action === 'clear') {
                    currentExpression = '';
                    updateDisplay('0', '');
                    return;
                }

                if (action === 'delete') {
                    if (shouldResetDisplay) return;
                    currentExpression = currentExpression.toString().slice(0, -1);
                    updateDisplay(currentExpression || '0', historyDisplay.textContent);
                    return;
                }

                if (action === 'calculate') {
                    if(!currentExpression) return;
                    try {
                        let evalExpression = currentExpression.replace(/%/g, '/100');
                        // Use a safe evaluation
                        if (/^[0-9+\-*/.%() ]+$/.test(evalExpression)) {
                            const result = new Function('return ' + evalExpression)();
                            
                            if (!isFinite(result)) {
                                updateDisplay('Error', '');
                                currentExpression = '';
                                shouldResetDisplay = true;
                                return;
                            }
                            
                            const formattedResult = Number.isInteger(result) ? result : parseFloat(result.toFixed(4));
                            
                            updateDisplay(formattedResult, currentExpression + ' =');
                            currentExpression = formattedResult.toString();
                            shouldResetDisplay = true;
                        } else {
                            updateDisplay('Error', '');
                            currentExpression = '';
                            shouldResetDisplay = true;
                        }
                    } catch (e) {
                        updateDisplay('Error', '');
                        currentExpression = '';
                        shouldResetDisplay = true;
                    }
                    return;
                }

                if (val) {
                    if (shouldResetDisplay && /[0-9.]/.test(val)) {
                        currentExpression = val;
                        shouldResetDisplay = false;
                        historyDisplay.textContent = '';
                    } else {
                        shouldResetDisplay = false;
                        if (currentExpression === '0' && /[0-9]/.test(val) && val !== '00') {
                            currentExpression = val;
                        } else if (currentExpression === '0' && val === '00') {
                            // do nothing
                        } else {
                            currentExpression += val;
                        }
                    }
                    updateDisplay(currentExpression, historyDisplay.textContent);
                }
            });
        });

        const pasteBtn = document.getElementById('calc-paste-btn');
        const modalEl = document.getElementById('globalCalculatorModal');
        
        function pasteToLastInput() {
            let resultVal = display.value;
            if (resultVal === 'Error') return;

            pendingPasteValue = resultVal;

            if (window.jQuery) {
                $('#globalCalculatorModal').modal('hide');
            } else {
                bootstrap.Modal.getInstance(modalEl).hide();
            }
        }

        if (pasteBtn) {
            pasteBtn.addEventListener('click', pasteToLastInput);
        }

        // Global keyboard shortcut (Alt + C)
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.code === 'KeyC') {
                // Allow standard text copy if user has text selected
                let selectedText = window.getSelection().toString();
                if (document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA')) {
                    selectedText = document.activeElement.value.substring(document.activeElement.selectionStart, document.activeElement.selectionEnd);
                }
                if (selectedText.length > 0) return;

                e.preventDefault();
                
                if (document.activeElement && document.activeElement.tagName === 'INPUT' && document.activeElement.id !== 'calc-display') {
                    lastActiveElement = document.activeElement;
                    lastTabulatorCell = document.activeElement.closest('.tabulator-cell');
                }
                
                if (window.jQuery) {
                    $('#globalCalculatorModal').modal('toggle');
                } else {
                    const calcModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    if (modalEl.classList.contains('show')) {
                        calcModal.hide();
                    } else {
                        calcModal.show();
                    }
                }
            }
        });

        if (modalEl) {
            // Drag functionality and save position
            const header = document.getElementById('calc-modal-header');
            const dialog = modalEl.querySelector('.modal-dialog');
            
            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = 0;
            let yOffset = 0;

            // Load saved position
            const savedCalcPos = localStorage.getItem('erp_calc_pos');
            if (savedCalcPos) {
                try {
                    const pos = JSON.parse(savedCalcPos);
                    xOffset = pos.x;
                    yOffset = pos.y;
                } catch (e) {}
            }

            header.addEventListener('mousedown', dragStart);
            document.addEventListener('mouseup', dragEnd);
            document.addEventListener('mousemove', drag);

            function dragStart(e) {
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;

                if (e.target === header || header.contains(e.target)) {
                    isDragging = true;
                }
            }

            function dragEnd(e) {
                if(isDragging) {
                    initialX = currentX;
                    initialY = currentY;
                    isDragging = false;
                    
                    // Save position
                    localStorage.setItem('erp_calc_pos', JSON.stringify({
                        x: xOffset,
                        y: yOffset
                    }));
                }
            }

            function drag(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                    xOffset = currentX;
                    yOffset = currentY;
                    
                    dialog.style.transform = `translate(${currentX}px, ${currentY}px)`;
                }
            }

            modalEl.addEventListener('hidden.bs.modal', function () {
                xOffset = 0;
                yOffset = 0;
                dialog.style.transform = 'translate(0px, 0px)';
                
                if (pendingPasteValue !== null) {
                    if (lastTabulatorCell && document.body.contains(lastTabulatorCell)) {
                        lastTabulatorCell.click();
                        setTimeout(() => {
                            let editor = lastTabulatorCell.querySelector('input');
                            if (editor) {
                                editor.value = pendingPasteValue;
                                editor.dispatchEvent(new Event('input', { bubbles: true }));
                                editor.dispatchEvent(new Event('change', { bubbles: true }));
                                editor.focus();
                            }
                        }, 50);
                    } else if (lastActiveElement && document.body.contains(lastActiveElement)) {
                        lastActiveElement.value = pendingPasteValue;
                        lastActiveElement.dispatchEvent(new Event('input', { bubbles: true }));
                        lastActiveElement.dispatchEvent(new Event('change', { bubbles: true }));
                        lastActiveElement.focus();
                    }
                    pendingPasteValue = null;
                }
            });

            modalEl.addEventListener('show.bs.modal', function () {
                // Apply saved offset when showing
                dialog.style.transform = `translate(${xOffset}px, ${yOffset}px)`;
            });

            modalEl.addEventListener('shown.bs.modal', function () {
                display.focus();
            });

            modalEl.addEventListener('keydown', function(e) {
                const key = e.key;
                let activeBtn = null;
                
                if (/^[0-9+\-*/.%]$/.test(key)) {
                    e.preventDefault();
                    activeBtn = document.querySelector(`.calc-btn[data-val="${key}"]`);
                    if(activeBtn) activeBtn.click();
                } else if (key === 'Enter') {
                    e.preventDefault();
                    activeBtn = document.querySelector('.calc-btn[data-action="calculate"]');
                    if (shouldResetDisplay) {
                        pasteToLastInput();
                    } else {
                        if(activeBtn) activeBtn.click();
                    }
                } else if (key === '=') {
                    e.preventDefault();
                    activeBtn = document.querySelector('.calc-btn[data-action="calculate"]');
                    if(activeBtn) activeBtn.click();
                } else if (key === 'Escape') {
                    e.preventDefault();
                    bootstrap.Modal.getInstance(modalEl).hide();
                } else if (key === 'Backspace') {
                    e.preventDefault();
                    activeBtn = document.querySelector('.calc-btn[data-action="delete"]');
                    if(activeBtn) activeBtn.click();
                } else if (key.toLowerCase() === 'c') {
                    e.preventDefault();
                    activeBtn = document.querySelector('.calc-btn[data-action="clear"]');
                    if(activeBtn) activeBtn.click();
                }
                
                if (activeBtn) {
                    activeBtn.classList.add('btn-pressed');
                    setTimeout(() => {
                        activeBtn.classList.remove('btn-pressed');
                    }, 120);
                }
            });
        }
    });
</script>
