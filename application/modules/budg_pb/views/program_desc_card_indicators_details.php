<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * جدول فرعي حقيقي (HMVC) — مؤشرات قياس أداء البرنامج (عرض فقط، بدون تعديل)
 * يُستدعى عبر modules::run() من داخل program_desc_card_show.php
 * البيانات تُقرأ من PLANUNIT.INDECATOR_INFO_TB/INDECATOR_DATA_TB عبر PROGRAM_INDICATORS_GETLIST
 */
?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>مؤشر قياس الأداء</th>
            <th>نوع المؤشر</th>
            <th>القيمة الفعلية <?= date('Y') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (isset($indicators_rows)) foreach ($indicators_rows as $i => $ind): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= $ind['INDECATOR_NAME'] ?></td>
                <td><?= $ind['PERIOD'] ?></td>
                <td><?= $ind['ACTUAL_VALUE'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>