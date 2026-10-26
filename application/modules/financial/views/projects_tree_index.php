<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// Projects tree screen

$MODULE_NAME = 'financial';
$TB_NAME     = 'projects_tree';

$create_url = base_url("$MODULE_NAME/$TB_NAME/create");
$edit_url   = base_url("$MODULE_NAME/$TB_NAME/edit");
$delete_url = base_url("$MODULE_NAME/$TB_NAME/delete");
$get_url    = base_url("$MODULE_NAME/$TB_NAME/get_id");

$can_create = HaveAccess($create_url);
$can_edit   = HaveAccess($get_url, $edit_url);
$can_delete = HaveAccess($delete_url);

$tree_urls_js   = json_encode(array('create' => $create_url, 'edit' => $edit_url, 'delete' => $delete_url, 'get' => $get_url));
$tree_access_js = json_encode(array('create' => (bool)$can_create, 'edit' => (bool)$can_edit, 'delete' => (bool)$can_delete));
?>

<!-- PAGE-HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $title ?></h1>
    </div>
    <div class="ms-auto pageheader-btn">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0);">المالية</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
        </ol>
    </div>
</div>
<!-- PAGE-HEADER END -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= $title ?></h3>
                <div class="ms-auto d-flex gap-1 flex-wrap align-items-center">
                    <?php if ($can_create): ?>
                        <button type="button" class="btn btn-danger btn-sm" onclick="projects_tree_create();">
                            <i class="fa fa-plus"></i> جديد
                        </button>
                    <?php endif; ?>
                    <?php if ($can_edit): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="projects_tree_get($.fn.tree.selected().attr('data-id'));">
                            <i class="fa fa-edit"></i> تحرير
                        </button>
                    <?php endif; ?>
                    <?php if ($can_delete): ?>
                        <button type="button" class="btn btn-dark btn-sm" onclick="projects_tree_delete();">
                            <i class="fa fa-trash"></i> حذف
                        </button>
                    <?php endif; ?>
                    <div class="vr mx-1 d-none d-md-block"></div>
                    <button type="button" class="btn btn-cyan-light btn-sm" onclick="$.fn.tree.expandAll();">
                        <i class="fa fa-angle-double-down"></i> توسيع
                    </button>
                    <button type="button" class="btn btn-cyan-light btn-sm" onclick="$.fn.tree.collapseAll();">
                        <i class="fa fa-angle-double-up"></i> طي
                    </button>
                    <?php if (!empty($help)): ?>
                        <button type="button" class="btn btn-light btn-sm" onclick="<?= htmlspecialchars($help, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa fa-question-circle"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>بحث في الشجرة</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" id="search-tree" class="form-control" placeholder="اكتب جزءاً من الاسم">
                        </div>
                    </div>
                </div>
                <p class="text-muted mb-2">
                    <i class="fa fa-info-circle"></i>
                    انقر عقدة لتحديدها، أو انقر نقراً مزدوجاً لفتح بياناتها. الشجرة ثلاثة مستويات كحدّ أقصى.
                </p>
                <?= $tree ?>
            </div>
        </div>
    </div>
</div>

<!-- node form modal -->
<div class="modal fade" id="projectsTreeModal" tabindex="-1" aria-labelledby="projectsTreeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="projectsTreeModalLabel">بيانات العنصر / المشروع</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="projects_tree_form" method="post" action="<?= $create_url ?>" role="form" novalidate="novalidate">
                <?= AntiForgeryToken() ?>
                <div class="modal-body">
                    <input type="hidden" name="parent_id" id="txt_parent_id">

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="txt_parent_name">العنصر الأب</label>
                            <input type="text" id="txt_parent_name" class="form-control" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="txt_node_id">رقم العنصر</label>
                            <input type="text" name="node_id" id="txt_node_id" class="form-control ltr" readonly>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="txt_node_level">المستوى</label>
                            <input type="text" id="txt_node_level" class="form-control ltr" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="txt_node_name">اسم العنصر / المشروع <span class="text-danger">*</span></label>
                            <input type="text" name="node_name" id="txt_node_name" class="form-control" maxlength="250" required>
                        </div>
                    </div>

                    <!-- number, level 2 and deeper -->
                    <div class="row" id="projects_tree_number_field">
                        <div class="form-group col-md-6">
                            <label for="txt_project_no" id="lbl_project_no">رقم المشروع</label>
                            <input type="text" name="project_no" id="txt_project_no" class="form-control ltr" maxlength="30" readonly>
                        </div>
                    </div>

                    <!-- project data, level 3 only -->
                    <div id="projects_tree_project_fields">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="txt_project_type">نوع المشروع</label>
                            <select name="project_type" id="txt_project_type" class="form-control sel2">
                                <option value="">— اختر —</option>
                                <?php foreach ($project_type_cons as $row): ?>
                                    <option value="<?= $row['CON_NO'] ?>"><?= $row['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="txt_activity_nature">طبيعة النشاط</label>
                            <select name="activity_nature" id="txt_activity_nature" class="form-control sel2">
                                <option value="">— اختر —</option>
                                <?php foreach ($activity_nature_cons as $row): ?>
                                    <option value="<?= $row['CON_NO'] ?>"><?= $row['CON_NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="txt_branch">الفرع</label>
                            <select name="branch" id="txt_branch" class="form-control sel2">
                                <option value="">— اختر —</option>
                                <?php foreach ($branches as $row): ?>
                                    <option value="<?= $row['NO'] ?>"><?= $row['NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="txt_project_supervisor">مشرف المشروع</label>
                            <select name="project_supervisor" id="txt_project_supervisor" class="form-control sel2">
                                <option value="">— اختر —</option>
                                <?php foreach ($supervisors as $row): ?>
                                    <option value="<?= $row['NO'] ?>"><?= $row['NO'] . ' : ' . $row['NAME'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="txt_project_open_date">تاريخ فتح المشروع</label>
                            <input type="text" name="project_open_date" id="txt_project_open_date" data-type="date"
                                   data-date-format="DD/MM/YYYY" data-date-usecurrent="false"
                                   class="form-control ltr" maxlength="10" placeholder="DD/MM/YYYY">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="txt_project_location">موقع المشروع</label>
                            <input type="text" name="project_location" id="txt_project_location" class="form-control" maxlength="500">
                        </div>
                    </div>
                    </div><!-- /projects_tree_project_fields -->
                </div>
                <div class="modal-footer">
                    <button type="submit" id="projects_tree_save" class="btn btn-primary">
                        <i class="fa fa-save"></i> حفظ البيانات
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$scripts = <<<SCRIPT
<script>
var projectsTreeUrls   = {$tree_urls_js};
var projectsTreeAccess = {$tree_access_js};
var projectsTreeMode   = '';     // 'create' or 'edit'
var projectsTreeTarget = null;   // selected tree node

var projectsTreeModalEl = document.getElementById('projectsTreeModal');

function projects_tree_modal() {
    return bootstrap.Modal.getOrCreateInstance(projectsTreeModalEl);
}

// unwraps a text or object reply
function projects_tree_response(response) {
    for (var i = 0; i < 2 && typeof response === 'string'; i++) {
        try { response = JSON.parse(response); } catch (e) { break; }
    }
    return response;
}

// package errors come as strings
function projects_tree_message(result) {
    return typeof result === 'string' ? result : null;
}

// number from level 2, details on level 3
function projects_tree_toggle_fields(level) {
    level = Number(level);
    $('#projects_tree_number_field').toggle(level >= 2);
    $('#projects_tree_project_fields').toggle(level === 3);
    $('#lbl_project_no').text(level === 3 ? 'رقم المشروع' : 'رقم الأب');
}

// sets a select2 value
function projects_tree_set_select(id, value) {
    $('#' + id).val(value == null ? '' : String(value)).trigger('change');
}

function projects_tree_find(id) {
    return $('#projects_tree li > span').filter(function () {
        return $(this).attr('data-id') === String(id);
    }).first();
}

function projects_tree_select(element) {
    $('#projects_tree span.selected').removeClass('selected');
    element.addClass('selected');
}

function projects_tree_create() {
    if (!projectsTreeAccess.create) return;
    var element = $.fn.tree.selected().first();
    var level = Number(element.attr('data-level'));
    if (!element.length || !isFinite(level) || level < 0 || level >= 3) {
        danger_msg('تحذير', 'اختر عنصراً أباً من المستوى الأول أو الثاني، أو جذر الشجرة. الحد الأقصى ثلاثة مستويات.');
        return;
    }
    projectsTreeMode = 'create';
    projectsTreeTarget = element;
    // clearForm would wipe the CSRF field
    $('#projects_tree_form')[0].reset();
    $('#projectsTreeModal .sel2').trigger('change');
    $('#txt_project_open_date').trigger('change');
    $('#projects_tree_form').attr('action', projectsTreeUrls.create);
    $('#txt_parent_id').val(element.attr('data-id'));
    $('#txt_parent_name').val(element.text());
    $('#txt_node_level').val(level + 1);
    projects_tree_toggle_fields(level + 1);
    projects_tree_modal().show();
}

function projects_tree_get(id) {
    if (!projectsTreeAccess.edit) return;
    var element = projects_tree_find(id);
    if (!element.length || String(id) === '0') {
        danger_msg('تحذير', 'اختر عنصراً من الشجرة لتحريره.');
        return;
    }
    get_data(projectsTreeUrls.get, {node_id: id}, function (data) {
        var rows = projects_tree_response(data);
        if (!Array.isArray(rows) || !rows.length || String(rows[0].NODE_ID) !== String(id)) {
            danger_msg('تحذير', projects_tree_message(rows) || 'تعذر جلب بيانات العنصر.');
            return;
        }
        var row = rows[0];
        var parentId = row.PARENT_ID == null ? 0 : row.PARENT_ID;
        projectsTreeMode = 'edit';
        projectsTreeTarget = element;
        $('#projects_tree_form')[0].reset();
        $('#projects_tree_form').attr('action', projectsTreeUrls.edit);
            $('#txt_node_id').val(row.NODE_ID);
        $('#txt_node_level').val(row.NODE_LEVEL);
        $('#txt_parent_id').val(parentId);
        $('#txt_parent_name').val(projects_tree_find(parentId).text());
        $('#txt_node_name').val(row.NODE_NAME);
        $('#txt_project_no').val(row.PROJECT_NO);
        $('#txt_project_location').val(row.PROJECT_LOCATION);
        $('#txt_project_open_date').val(row.PROJECT_OPEN_DATE_TEXT).trigger('change');
        projects_tree_set_select('txt_project_type', row.PROJECT_TYPE);
        projects_tree_set_select('txt_activity_nature', row.ACTIVITY_NATURE);
        projects_tree_set_select('txt_branch', row.BRANCH);
        projects_tree_set_select('txt_project_supervisor', row.PROJECT_SUPERVISOR);
        projects_tree_toggle_fields(row.NODE_LEVEL);
        projects_tree_modal().show();
    }, 'json');
}

function projects_tree_delete() {
    if (!projectsTreeAccess.delete) return;
    var element = $.fn.tree.selected().first();
    var id = element.attr('data-id');
    if (!element.length || id === '0') {
        danger_msg('تحذير', 'اختر عنصراً من الشجرة لحذفه.');
        return;
    }
    if (element.parent().children('ul').children('li').length) {
        danger_msg('تحذير', 'لا يمكن حذف عنصر له عناصر فرعية.');
        return;
    }
    if (!confirm('هل تريد حذف العنصر المحدد؟')) return;
    var parent = element.parent().parent().parent('li').children('span').first();
    // helper adds the CSRF token
    ajax_delete_any(projectsTreeUrls.delete, {node_id: id}, function (data) {
        var result = projects_tree_response(data);
        if (result !== 1 && result !== '1') {
            danger_msg('تحذير', projects_tree_message(result) || 'تعذر حذف العنصر.');
            return;
        }
        $.fn.tree.removeElem(element);
        projects_tree_select(parent.length ? parent : projects_tree_find(0));
        success_msg('رسالة', 'تم حذف العنصر بنجاح.');
    });
}

$(function () {
    $('#projects_tree').tree();

    // keeps the list above the modal
    $('#projectsTreeModal .sel2').select2({
        dropdownParent: $('#projectsTreeModal .modal-content'),
        width: '100%'
    });

    // jQuery.on misses dotted BS5 events
    projectsTreeModalEl.addEventListener('shown.bs.modal', function () {
        $('#txt_node_name').focus();
    });

    projectsTreeModalEl.addEventListener('hide.bs.modal', function () {
        var picker = $('#txt_project_open_date').data('DateTimePicker');
        if (picker) picker.hide();
    });

    $('#projects_tree_form').on('submit', function (event) {
        event.preventDefault();
        var element = projectsTreeTarget;
        if (!element || !element.length || !projectsTreeAccess[projectsTreeMode]) return;

        var node_name = $('#txt_node_name').val();
        var open_date = $('#txt_project_open_date').val();
        if (!node_name || !node_name.trim()) {
            danger_msg('تحذير', 'اسم العنصر / المشروع مطلوب.');
            $('#txt_node_name').focus();
            return;
        }
        if (open_date && !/^\d{2}\/\d{2}\/\d{4}$/.test(open_date)) {
            danger_msg('تحذير', 'أدخل التاريخ بالصيغة DD/MM/YYYY.');
            $('#txt_project_open_date').focus();
            return;
        }
    
        var isCreate = projectsTreeMode === 'create';
        // posts the whole form
        ajax_insert_update($(this), function (data) {
            var result = projects_tree_response(data);

            var label = $('<div>').text(node_name).html();

            if (isCreate) {
                if (!result || result.msg !== 1 || !/^[1-9]\d*$/.test(String(result.id))) {
                    danger_msg('تحذير', projects_tree_message(result) || 'تعذر إضافة العنصر.');
                    return;
                }
                // $.fn.tree.add hangs the node under the selected one
                projects_tree_select(element);
                $.fn.tree.add(label, result.id, "projects_tree_get('" + result.id + "');");
                var added = projects_tree_find(result.id);
                added.attr('data-level', Number(element.attr('data-level')) + 1);
                projects_tree_select(added);
                projects_tree_modal().hide();
                success_msg('رسالة', 'تمت إضافة العنصر بنجاح.');
                return;
            }

            if (result !== 1 && result !== '1') {
                danger_msg('تحذير', projects_tree_message(result) || 'تعذر حفظ البيانات.');
                return;
            }
            projects_tree_select(element);
            // update() rewrites the label, keep the balance
            var balance_badge = element.children('.badge').detach();
            $.fn.tree.update(label);
            element.append(balance_badge);
            projects_tree_modal().hide();
            success_msg('رسالة', 'تم حفظ البيانات بنجاح.');
        }, 'json');
    });
});
</script>
SCRIPT;
sec_scripts($scripts);
