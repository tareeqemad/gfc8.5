<div id="batchPaymentFeedback" class="alert" role="alert" aria-live="polite" tabindex="-1" style="display:none">
    <strong class="batch-payment-title"></strong>
    <div class="batch-payment-message mt-1"></div>
    <ul class="batch-payment-errors mt-2 mb-2" style="white-space:pre-wrap"></ul>
    <a class="batch-payment-detail" href="#">فتح تفاصيل الدفعة</a>
</div>
<script>
var _batchPaymentPending = false;
function paymentBatchFeedback(batchId, response) {
    var confirmed = response && response.ok === true && (response.state === 'PAID' || response.state === 'ALREADY_PAID');
    var $box = $('#batchPaymentFeedback');
    $box.attr('class', 'alert ' + (confirmed ? 'alert-success' : 'alert-danger'));
    $box.find('.batch-payment-title').text(confirmed
        ? (response.state === 'ALREADY_PAID' ? 'الدفعة منفّذة مسبقاً' : 'تم صرف الدفعة')
        : 'تعذر صرف الدفعة');
    $box.find('.batch-payment-message').text(response.msg || 'تعذر تأكيد نتيجة الصرف');
    var $errors = $box.find('.batch-payment-errors').empty();
    (Array.isArray(response.failure_details) ? response.failure_details : []).forEach(function(reason) {
        $errors.append($('<li>').text(String(reason)));
    });
    $box.find('.batch-payment-detail').attr('href', <?= json_encode(base_url('payment_req/payment_req/batch_detail/')) ?> + encodeURIComponent(batchId));
    $box.show().trigger('focus');
}
function paymentBatchExecute(options) {
    if (_batchPaymentPending) return false;
    _batchPaymentPending = true;
    var $buttons = $(options.buttons);
    $buttons.prop('disabled', true).attr('aria-busy', 'true');
    paymentBatchFeedback(options.batchId, {msg:'جارٍ تنفيذ الصرف، يرجى الانتظار.', failure_details:[]});
    $('#batchPaymentFeedback').attr('class', 'alert alert-info').find('.batch-payment-title').text('العملية قيد التنفيذ');
    var paid = false;
    $.ajax({
        url: options.url, type:'POST', dataType:'json',
        data:{batch_id:options.batchId, __AntiForgeryToken:$('input[name="__AntiForgeryToken"]').val()},
        success:function(response) {
            if (!response || typeof response !== 'object') {
                response = {ok:false, state:'UNCONFIRMED', msg:'تعذر قراءة نتيجة الصرف؛ افتح تفاصيل الدفعة وتحقق من حالتها قبل إعادة المحاولة'};
            }
            paid = response.ok === true && (response.state === 'PAID' || response.state === 'ALREADY_PAID');
            paymentBatchFeedback(options.batchId, response);
            if (paid && options.onPaid) options.onPaid(response);
        },
        error:function() {
            paymentBatchFeedback(options.batchId, {ok:false, state:'UNCONFIRMED',
                msg:'تعذر التأكد من نتيجة الصرف بسبب الاتصال؛ افتح تفاصيل الدفعة وتحقق من حالتها قبل إعادة المحاولة'});
        },
        complete:function() {
            _batchPaymentPending = false;
            $buttons.prop('disabled', paid).attr('aria-busy', 'false');
            if (options.onSettled) options.onSettled();
        }
    });
    return true;
}
</script>
