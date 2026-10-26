<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="container-fluid" style="padding:18px;direction:rtl">

  <div class="card" style="border:0;box-shadow:0 1px 4px rgba(0,0,0,.08);border-radius:12px;margin-bottom:14px">
    <div class="card-body" style="padding:16px 20px">
      <h4 style="margin:0 0 6px;font-weight:700;color:#0f172a">
        <i class="fa fa-user-shield"></i>&nbsp; إدارة صلاحية الاطلاع على الرواتب
      </h4>
      <p style="margin:0;color:#64748b;font-size:13.5px;line-height:1.8">
        طبقة حماية وسيطة فوق نظام الصلاحيات. <b>المسموح</b> فقط يشوف شاشات الرواتب،
        و<b>المحجوب</b> (حتى لو عنده الصلاحية القياسية) بيشوف صفحة "تحت الصيانة".
        المدراء دائماً مسموحين ولا يمكن حجبهم من هنا.
      </p>
    </div>
  </div>

  <div style="margin-bottom:10px">
    <input type="text" id="paSearch" class="form-control" placeholder="بحث بالاسم / رقم الموظف / رقم المستخدم..."
           style="max-width:380px;display:inline-block">
    <span style="color:#94a3b8;font-size:12.5px;margin-right:8px">
      الإجمالي: <b><?= count($rows) ?></b> &nbsp;•&nbsp;
      المسموحين: <b><?= array_sum(array_map(function($r){ return (int)$r['IN_WHITELIST']; }, $rows)) ?></b>
    </span>
  </div>

  <div class="card" style="border:0;box-shadow:0 1px 4px rgba(0,0,0,.08);border-radius:12px">
    <div class="table-responsive">
      <table class="table table-hover" id="paTable" style="margin:0;font-size:13.5px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:40px">#</th>
            <th>رقم المستخدم</th>
            <th>رقم الموظف</th>
            <th>الاسم</th>
            <th class="text-center">صلاحية قياسية</th>
            <th class="text-center">الحالة</th>
            <th class="text-center" style="width:120px">إجراء</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0; foreach ($rows as $r): $i++;
            $allowed = (int)$r['IN_WHITELIST'] === 1;
            $isMgr   = (int)$r['IS_MANAGER'] === 1;
          ?>
          <tr>
            <td><?= $i ?></td>
            <td><?= (int)$r['USER_NO'] ?></td>
            <td><?= htmlspecialchars($r['EMP_NO']) ?></td>
            <td><?= htmlspecialchars($r['USER_NAME']) ?></td>
            <td class="text-center">
              <?= ((int)$r['HAS_PERM'] === 1)
                    ? '<span class="badge bg-info" style="background:#0ea5e9">نعم</span>'
                    : '<span style="color:#cbd5e1">—</span>' ?>
            </td>
            <td class="text-center pa-status">
              <?php if ($isMgr): ?>
                <span class="badge" style="background:#7c3aed">مدير</span>
              <?php elseif ($allowed): ?>
                <span class="badge bg-success">مسموح</span>
              <?php else: ?>
                <span class="badge bg-secondary">محجوب</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <?php if ($isMgr): ?>
                <span style="color:#cbd5e1;font-size:12px">—</span>
              <?php else: ?>
                <button type="button"
                        class="btn btn-sm pa-toggle <?= $allowed ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                        data-user="<?= (int)$r['USER_NO'] ?>"
                        data-cur="<?= $allowed ? '1' : '0' ?>">
                  <?= $allowed ? 'حجب' : 'سماح' ?>
                </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php
$toggle_url = $toggle_url;
$scripts = <<<SCRIPT
<script>
  var paToggleUrl = "$toggle_url";

  $(document).on('click', '.pa-toggle', function(){
    var btn = this;
    var u   = $(btn).data('user');
    var cur = String($(btn).data('cur'));
    var act = (cur === '1') ? 'revoke' : 'grant';

    get_data(paToggleUrl, { user_no: u, action: act }, function(resp){
      if (String(resp).trim() === '1') {
        success_msg(act === 'grant' ? 'تم السماح للمستخدم' : 'تم حجب المستخدم');
        var row = $(btn).closest('tr');
        if (act === 'grant') {
          $(btn).data('cur','1').removeClass('btn-outline-success').addClass('btn-outline-danger').text('حجب');
          row.find('.pa-status').html('<span class="badge bg-success">مسموح</span>');
        } else {
          $(btn).data('cur','0').removeClass('btn-outline-danger').addClass('btn-outline-success').text('سماح');
          row.find('.pa-status').html('<span class="badge bg-secondary">محجوب</span>');
        }
      } else {
        danger_msg(resp || 'تعذّر تنفيذ العملية');
      }
    });
  });

  $('#paSearch').on('keyup', function(){
    var q = $(this).val().toLowerCase();
    $('#paTable tbody tr').each(function(){
      var t = $(this).text().toLowerCase();
      $(this).toggle(t.indexOf(q) > -1);
    });
  });
</script>
SCRIPT;
sec_scripts($scripts);
?>
