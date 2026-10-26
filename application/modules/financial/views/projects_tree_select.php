<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// Project picker, opened in #report iframe by journal rows
?>

<div class="row">
    <div class="toolbar">
        <div class="caption"><?= $title ?></div>
        <ul>
            <li><a onclick="projects_tree_pick('', '');" href="javascript:;"><i class="glyphicon glyphicon-remove"></i>بدون مشروع</a></li>
        </ul>
    </div>

    <div class="form-body">
        <div class="form-group">
            <div class="input-group col-sm-4">
                <span class="input-group-addon"><i class="icon icon-search"></i></span>
                <input type="text" id="search-tbl" data-set="projectsTbl" class="form-control" placeholder="بحث">
            </div>
        </div>

        <div id="container">
            <table class="table selected-red" id="projectsTbl" data-container="container">
                <thead>
                <tr>
                    <th style="width: 110px;">رقم المشروع</th>
                    <th>اسم المشروع</th>
                    <th style="width: 220px;">الفرع</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($nodes) === 0): ?>
                    <tr><td colspan="3" class="text-center">لا توجد مشاريع</td></tr>
                <?php endif; ?>

                <?php foreach ($nodes as $row): ?>
                    <?php
                    $level = (int)$row['NODE_LEVEL'];
                    $project_no = trim((string)$row['PROJECT_NO']);
                    $is_pickable = $level === (int)$pick_level && $project_no !== '';
                    $node_name = htmlspecialchars($row['NODE_NAME'], ENT_QUOTES, 'UTF-8');
                    $project_no_html = htmlspecialchars($project_no, ENT_QUOTES, 'UTF-8');
                    ?>
                    <?php if ($is_pickable): ?>
                        <tr class="project-pick" style="cursor: pointer;"
                            data-no="<?= $project_no_html ?>" data-name="<?= $node_name ?>">
                    <?php else: ?>
                        <tr class="project-group" style="color: #888; font-weight: bold;">
                    <?php endif; ?>
                        <td class="align-left"><?= $project_no_html ?></td>
                        <td class="align-right" style="padding-right: <?= 10 + ($level - 1) * 24 ?>px;"><?= $node_name ?></td>
                        <td><?= htmlspecialchars((string)(isset($row['BRANCH_NAME']) ? $row['BRANCH_NAME'] : ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$scripts = <<<SCRIPT
<script>
    $(function () {
        // double click picks a project
        $('#projectsTbl').on('dblclick', 'tr.project-pick', function () {
            projects_tree_pick($(this).attr('data-no'), $(this).attr('data-name'));
        });
    });

    // writes back to the calling row
    function projects_tree_pick(project_no, name) {
        parent.$('#h_$txt').val(project_no);
        parent.$('#$txt').val(name).trigger('change');
        parent.$('#report').modal('hide');
    }
</script>
SCRIPT;

sec_scripts($scripts);
