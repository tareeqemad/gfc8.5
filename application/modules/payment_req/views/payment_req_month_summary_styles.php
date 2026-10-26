<style>
/* Keep the monthly figures readable without changing other request cards. */
.payment-month-summary .pr-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin:8px 0; }
.payment-month-summary .pr-card { min-width:0; padding:12px; border:1px solid #dbe3eb; border-radius:9px; background:#fff; text-align:right; }
.payment-month-summary .c-label { font-size:12px; line-height:1.6; color:#475569; }
.payment-month-summary .c-val { margin-top:5px; font-size:20px; line-height:1.5; font-weight:700; font-variant-numeric:tabular-nums; overflow-wrap:anywhere; }
.payment-month-summary .c-cnt { margin-top:4px; font-size:11px; line-height:1.6; color:#64748b; }
.payment-month-summary .c-total { background:#f8fafc; }
.payment-month-summary .c-active { background:#f0fdf4; border-color:#bbf7d0; }
.payment-month-summary .c-net { background:#eff6ff; border-color:#bfdbfe; }
.payment-month-summary .small { font-size:12px; line-height:1.8; }
@media(max-width:650px) {
    .payment-month-summary .pr-row { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .payment-month-summary .pr-card:last-child:nth-child(odd) { grid-column:1 / -1; }
}
@media(max-width:420px) { .payment-month-summary .pr-row { grid-template-columns:1fr; } }
</style>
