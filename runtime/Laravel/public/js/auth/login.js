document.addEventListener("DOMContentLoaded", () => {

    // ════════════════════════════════════════════════════════
    //  ACCOUNTING ANIMATIONS
    // ════════════════════════════════════════════════════════

    // ── 1. SVG chart draw ────────────────────────────────────
    const line    = document.getElementById("accLine");
    const area    = document.getElementById("accArea");
    const dot     = document.getElementById("accDot");
    const dotRing = document.getElementById("accDotRing");

    if (line) {
        const len = line.getTotalLength();
        line.style.strokeDasharray  = len;
        line.style.strokeDashoffset = len;

        // Force reflow so the starting state is applied
        line.getBoundingClientRect();

        // Trigger draw after a short delay
        setTimeout(() => {
            line.style.strokeDashoffset = 0;

            // Fade in area + dots after line completes
            setTimeout(() => {
                if (area)    { area.style.opacity    = "1"; }
                if (dot)     { dot.style.opacity     = "1"; }
                if (dotRing) {
                    dotRing.style.opacity = "1";
                    dotRing.classList.add("acc-ring-anim");
                }
            }, 2000);
        }, 600);
    }


    // ── 2. Live transaction feed ─────────────────────────────
    const TXNS = [
        { type: "cr",  ico: "cr",  desc: "Invoice #INV-1042",  sub: "Sharma Trading Co.",   amt: "+₹45,000",  time: "Just now"  },
        { type: "dr",  ico: "dr",  desc: "Purchase #PUR-891",  sub: "Ram Suppliers Ltd.",   amt: "−₹12,500",  time: "1 min ago" },
        { type: "gst", ico: "gst", desc: "GST Return #GSTR-3B", sub: "Q3 FY 2025–26",       amt: "₹8,240",    time: "3 min ago" },
        { type: "cr",  ico: "cr",  desc: "Payment #PAY-567",   sub: "Global Exports Pvt.",  amt: "+₹1,20,000", time: "5 min ago" },
        { type: "dr",  ico: "dr",  desc: "Expense #EXP-344",   sub: "Logistics — Godown B", amt: "−₹8,200",   time: "7 min ago" },
        { type: "cr",  ico: "cr",  desc: "Invoice #INV-1041",  sub: "Patel &amp; Sons",     amt: "+₹28,750",  time: "9 min ago" },
        { type: "dr",  ico: "dr",  desc: "Purchase #PUR-892",  sub: "City Hardware Store",  amt: "−₹35,400",  time: "11 min ago"},
        { type: "gst", ico: "gst", desc: "GST Payable #CGST",  sub: "Auto-reconciled",      amt: "₹4,180",    time: "13 min ago"},
        { type: "cr",  ico: "cr",  desc: "Receipt #REC-229",   sub: "Multi-ref Clearing",   amt: "+₹67,500",  time: "15 min ago"},
        { type: "dr",  ico: "dr",  desc: "Stock Transfer",     sub: "Godown A → Godown C",  amt: "210 units", time: "17 min ago"},
    ];

    // Credit / Debit / GST SVG icons
    const ICONS = {
        cr:  `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>`,
        dr:  `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>`,
        gst: `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>`,
    };

    const feed = document.getElementById("txnFeed");
    if (!feed) return;

    // Render initial 3 items without animation
    let cursor = 0;

    function makeTxnEl(txn, animate) {
        const el = document.createElement("div");
        el.className = "acc-txn-item" + (animate ? " txn-enter" : "");
        el.innerHTML = `
            <div class="acc-txn-ico ${txn.ico}">${ICONS[txn.ico] || ""}</div>
            <div class="acc-txn-body">
                <div class="acc-txn-desc">${txn.desc}</div>
                <div class="acc-txn-sub">${txn.sub}</div>
            </div>
            <div class="acc-txn-right">
                <div class="acc-txn-amt ${txn.type}">${txn.amt}</div>
                <div class="acc-txn-time">${txn.time}</div>
            </div>`;
        return el;
    }

    // Seed with first 3
    [0, 1, 2].forEach(i => {
        feed.appendChild(makeTxnEl(TXNS[i], false));
        cursor++;
    });

    // Cycle: remove bottom, insert at top
    setInterval(() => {
        const items = feed.querySelectorAll(".acc-txn-item");

        // Fade out last
        const last = items[items.length - 1];
        last.classList.add("txn-exit");
        setTimeout(() => { if (last.parentNode) last.remove(); }, 320);

        // Insert new at top
        const next = makeTxnEl(TXNS[cursor % TXNS.length], true);
        feed.insertBefore(next, feed.firstChild);
        cursor++;
    }, 2400);


    // ════════════════════════════════════════════════════════
    //  FORM UX
    // ════════════════════════════════════════════════════════

    // ── Password toggle ──────────────────────────────────────
    const toggleBtn = document.getElementById("togglePwd");
    const pwdInput  = document.getElementById("password");
    const eyeShow   = document.getElementById("eyeShow");
    const eyeHide   = document.getElementById("eyeHide");

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener("click", () => {
            const visible    = pwdInput.type === "text";
            pwdInput.type    = visible ? "password" : "text";
            eyeShow.style.display = visible ? ""     : "none";
            eyeHide.style.display = visible ? "none" : "";
        });
    }

    // ── Submit loading state ─────────────────────────────────
    const form      = document.getElementById("loginForm");
    const submitBtn = document.getElementById("submitBtn");
    const btnDef    = submitBtn?.querySelector(".lc-btn-default");
    const btnLoad   = submitBtn?.querySelector(".lc-btn-loading");

    if (form && submitBtn) {
        form.addEventListener("submit", () => {
            submitBtn.disabled    = true;
            btnDef.style.display  = "none";
            btnLoad.style.display = "inline-flex";
        });
        
        // ── Enter key navigation ─────────────────────────────────
        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), button[type="submit"]'));
        
        inputs.forEach((el, index) => {
            if (el.tagName.toLowerCase() === 'input') {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault(); // Prevent form submission
                        const nextIndex = index + 1;
                        if (nextIndex < inputs.length) {
                            inputs[nextIndex].focus();
                        }
                    }
                });
            }
        });
    }
});
