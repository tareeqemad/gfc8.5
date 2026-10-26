<?php
$create_activities_url = base_url("budg_pb/activities/create/");
$get_activities_url = base_url("budg_pb/activities/get/");

$create_projects_url = base_url("budg_pb/projects/create/");
$get_projects_url = base_url("budg_pb/projects/get/");
$get_plan_projects_url = base_url("planning/planning/get_tech_cost/");

$create_unit_url = base_url("budg_pb/programs/create_unit/");

?>
<style>
    .ul-ls-mk li {
        list-style: disc;
        text-align: start;
    }

    .unit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .delete-icon {
        opacity: 0;
        transition: 0.3s;
        cursor: pointer;
        color: red;
    }

    .unit-item:hover .delete-icon {
        opacity: 1;
    }

    .highlight-row {
        background-color: #d4e5f5 !important;
        color: #143956;
        border-top: 2px solid #4a90e2;
    }

    .activities-tr {
        background-color: #fafafa !important;
    }

    .projects-tr {
        background-color: #e7e7e7 !important;
    }
</style>

<div class="tbl_container">
    <table class="table" id="page_tb" data-container="container">

        <?php foreach ($page_rows as $row) : ?>

            <thead>
                <tr>
                    <th style="cursor: pointer;" colspan="2" ondblclick="javascript:show_row_details('<?= $row['PROGRAM_ID'] ?>');">
                        البرنامج/ <?= $row['PROG_NAME'] ?>
                        <a href="javascript:;" onclick="show_members(1, <?= $row['PROGRAM_ID'] ?>, 'برنامج: <?= htmlspecialchars($row['PROG_NAME'], ENT_QUOTES) ?>')" title="عرض الأعضاء" style="margin-right:8px;color:#1976d2">
                            <i class="glyphicon glyphicon-user"></i>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>

                <tr class="highlight-row">
                    <td>
                        <?php if (1 || HaveAccess($create_activities_url)):  ?><li><a href="<?= $create_activities_url . $row['PROGRAM_ID'] ?>"><i class="glyphicon glyphicon-plus"></i> اضافة نشاط </a> </li><?php endif; ?>
                    </td>
                    <td>الوحدات التنظيمية للنشاط</td>
                </tr>

                <?php
                $activities = null;
                $projects = null;
                $activities = $activities_program[$row['PROGRAM_ID']] ?? [];
                $projects   = $projects_program[$row['PROGRAM_ID']] ?? [];

                for ($i = 0; $i < count($activities); $i++) {
                    $activity = $activities[$i] ?? null;
                ?>
                    <tr class="activities-tr">
                        <td>
                            <li>
                                <a href='<?= $get_activities_url . $activity['ACTIVITY_ID'] ?>'><?= $activity['ACTIVITY_NAME'] ?></a>
                                <a href="javascript:;" onclick="show_members(2, <?= $activity['ACTIVITY_ID'] ?>, 'نشاط: <?= htmlspecialchars($activity['ACTIVITY_NAME'], ENT_QUOTES) ?>')" title="عرض الأعضاء" style="margin-right:8px;color:#1976d2">
                                    <i class="glyphicon glyphicon-user"></i>
                                </a>
                            </li>
                        </td>
                        <td>
                            <ul class="ul-ls-mk">
                                <?php if ($activity) {
                                    if (1 ||  HaveAccess($create_unit_url)):  ?><li2><a onclick="javascript:unit_create( 'ACTIVITY', <?= $activity['ACTIVITY_ID'] ?> );" href="javascript:;"><i class="glyphicon glyphicon-plus"></i> اضافة وحدة تنظيمية </a> </li2><?php endif;
                                                                                                                                                                                                                                                                foreach ($activities_units[$activity['ACTIVITY_ID']] ?? [] as $unit) {
                                                                                                                                                                                                                                                                    echo "<li id='U_ACTIVITY_" . $unit['SER'] . "' class='unit-item'>
                                        <span class='unit-name'>" . $unit['UNIT_NAME'] . "</span>
                                        <span class='delete-icon' onclick=\"delete_unit('ACTIVITY', " . $unit['SER'] . ")\">
                                            <span class='glyphicon glyphicon-trash'></span>
                                        </span>
                                      </li>";
                                                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                                            }
                                                                                                                                                                                                                                                                    ?>
                            </ul>
                        </td>
                    </tr>

                <?php }  ?>

                <tr class="highlight-row">
                    <td>
                        المشاريع
                    </td>
                    <td>
                        <?php if (1 ||  HaveAccess($create_unit_url)):  ?><li2><a onclick="javascript:unit_create( 'PROGRAM_PROJECT', <?= $row['PROGRAM_ID'] ?> );" href="javascript:;"><i class="glyphicon glyphicon-plus"></i> سجل جديد </a> </li2><?php endif; ?>
                    </td>
                </tr>

                <?php
                if (count($projects) > 0) {

                    // 1) مشاريع الخطة المرتبطة بكل مشروع عادي
                    $attached = [];      // [رقم المشروع العادي][رقم مشروع الخطة] = الصف
                    $attached_ids = [];  // أرقام مشاريع الخطة التي وُجد لها مشروع عادي
                    foreach ($projects as $p) {
                        if ($p['PLAN'] != 1) continue;
                        foreach ($projects_program_[$p['PROJECT_ID']] ?? [] as $r) {
                            if ($r['PLAN'] == 2) {
                                $attached[$p['PROJECT_ID']][$r['PROJECT_ID']] = $r;
                                $attached_ids[$r['PROJECT_ID']] = true;
                            }
                        }
                    }

                    // 2) تجميع المشاريع بحسب مجموعة الوحدات (بدون مشاريع الخطة المرتبطة)
                    $groups = [];
                    foreach ($projects as $project) {
                        if ($project['PLAN'] == 2 && isset($attached_ids[$project['PROJECT_ID']])) continue;

                        $ids = [];
                        foreach ($projects_units[$project['PROJECT_ID']] ?? [] as $u) {
                            $ids[] = (string)$u['UNIT_ID'];
                        }
                        $ids = array_unique($ids);
                        sort($ids, SORT_STRING);
                        $groups[md5(implode('|', $ids))][] = $project;
                    }

                    // 3) إلحاق مشاريع الخطة بالمجموعة التي فيها المشروع العادي
                    foreach ($groups as $k => $g) {
                        $extra = [];
                        foreach ($g as $p) {
                            foreach ($attached[$p['PROJECT_ID']] ?? [] as $id => $r) {
                                $extra[$id] = $r;
                            }
                        }
                        foreach ($extra as $r) {
                            $groups[$k][] = $r;
                        }
                    }

                    // 4) الرسم
                    foreach ($groups as $group) {
                        $first = $group[0];
                ?>
                        <tr class="projects-tr">
                            <td>
                                <?php $last_idx = count($group) - 1;
                                foreach ($group as $idx => $project) { ?>
                                    <div style="padding:4px 0;<?= $idx < $last_idx ? ' border-bottom:1px dashed #cfcfcf;' : '' ?>">
                                        <?php
                                        $url = (($project['PLAN'] ?? null) == 1)
                                            ? $get_projects_url . $project['PROJECT_ID']
                                            : $get_plan_projects_url . $project['PROJECT_ID'];
                                        ?>
                                        <a href="<?= $url ?>"><?= htmlspecialchars($project['PROJECT_NAME'], ENT_QUOTES) ?></a>
                                    </div>
                                <?php } ?>
                            </td>

                            <td>
                                <ul class="ul-ls-mk">
                                    <?php
                                    $units_list = $projects_units[$first['PROJECT_ID']] ?? [];

                                    // السجلات الحقيقية (PLAN=1) أولاً، ثم سجلات الخطة
                                    usort($units_list, function ($a, $b) {
                                        return (($a['PLAN'] ?? 1) <=> ($b['PLAN'] ?? 1));
                                    });

                                    $printed_units = [];
                                    foreach ($units_list as $unit) {
                                        if (isset($printed_units[$unit['UNIT_ID']])) continue; // الوحدة طُبعت سابقاً
                                        $printed_units[$unit['UNIT_ID']] = true;

                                        echo "<li id='U_PROJECT_" . $unit['SER'] . "' class='unit-item'>
                        <span class='unit-name'>" . $unit['UNIT_NAME'] . "</span>";
                                        if (($unit['PLAN'] ?? 1) != 2) {   // زر الحذف للسجلات الحقيقية فقط
                                            /* echo "<span class='delete-icon' onclick=\"delete_unit('PROJECT', " . $unit['SER'] . ")\">
                              <span class='glyphicon glyphicon-trash'></span>
                          </span>";*/
                                        }
                                        echo "</li>";
                                    }
                                    ?>
                                </ul>
                            </td>
                        </tr>
                <?php
                    } // foreach groups
                } // if projects
                ?>

                <?php foreach (($groups_program[$row['PROGRAM_ID']] ?? []) as $gid => $g_units) {
                    $g_projects = $groups_projects[$gid] ?? [];
                ?>
                    <tr class="projects-tr">
                        <td>
                            <?php $g_last = count($g_projects) - 1;
                            foreach ($g_projects as $gi => $gp) {
                                $g_url = (($gp['PLAN'] ?? null) == 1)
                                    ? $get_projects_url . $gp['PROJECT_ID']
                                    : $get_plan_projects_url . $gp['PROJECT_ID'];
                            ?>
                                <div style="padding:4px 0;<?= $gi < $g_last ? ' border-bottom:1px dashed #cfcfcf;' : '' ?>">
                                    <a href="<?= $g_url ?>"><?= htmlspecialchars($gp['PROJECT_NAME'], ENT_QUOTES) ?></a>
                                </div>
                            <?php } ?>
                            <div style="padding-top:6px;">
                                <?php if (count($g_units) > 0) { ?>
                                    <a href="javascript:;" onclick="group_project_create(<?= $row['PROGRAM_ID'] ?>, <?= $gid ?>);"><i class="glyphicon glyphicon-plus"></i> اضافة مشروع </a>
                                <?php } else { ?>
                                    <span style="color:#888;">اضف وحدة تنظيمية اولاً لاضافة مشروع</span>
                                <?php } ?>
                            </div>
                        </td>
                        <td>
                            <ul class="ul-ls-mk">
                                <li2><a onclick="javascript:unit_create( 'GROUP', <?= $gid ?> );" href="javascript:;"><i class="glyphicon glyphicon-plus"></i> اضافة وحدة تنظيمية </a></li2>
                                <?php foreach ($g_units as $gu) {
                                    echo "<li id='U_GROUP_" . $gu['SER'] . "' class='unit-item'>
                        <span class='unit-name'>" . $gu['UNIT_NAME'] . "</span>
                        <!--<span class='delete-icon' onclick=\"delete_unit('GROUP', " . $gu['SER'] . ")\">
                            <span class='glyphicon glyphicon-trash'></span>
                        </span>-->
                      </li>";
                                } ?>
                            </ul>
                        </td>
                    </tr>
                <?php } ?>

            <?php endforeach; ?>
            </tbody>
    </table>
</div>