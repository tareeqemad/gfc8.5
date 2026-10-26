<div class="row">
    <!-- 1. شجرة الأهداف والغايات -->
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sitemap"></i> الهيكل الاستراتيجي</h3>
            </div>
            <div class="card-body">
                <div id="goals_tree_container"></div>
            </div>
        </div>
    </div>

    <!-- 2. بطاقات ومؤشرات الهدف المSelected -->
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title" id="selected_goal_title">اختر هدفاً من الشجرة لعرض مؤشراته</h3>
                <button class="btn btn-sm btn-success" id="btn_add_kpi" disabled onclick="openKpiModal()">
                    <i class="fas fa-plus"></i> إضافة مؤشر جديد
                </button>
            </div>
            <div class="card-body">
                <!-- جدول المؤشرات المربوطة بالهدف -->
                <table class="table table-bordered table-striped" id="kpi_table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المؤشر</th>
                            <th>طبيعة المؤشر</th>
                            <th>الوحدة</th>
                            <th>الوزن</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center text-muted">لم يتم اختيار هدف استراتيجي بعد</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة/تعديل مؤشر -->
<div class="modal fade" id="kpiModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة / تعديل مؤشر أداء</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="kpiForm">
                <div class="modal-body">
                    <input type="hidden" name="kpi_id" id="kpi_id">
                    <input type="hidden" name="goal_id" id="modal_goal_id">

                    <div class="form-group">
                        <label>اسم مؤشر الأداء <span class="text-danger">*</span></label>
                        <input type="text" name="indecator_name" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>طبيعة المؤشر (نوع التجميع)</label>
                                <select name="period" class="form-control">
                                    <option value="1">تراكمي / عدد (SUM)</option>
                                    <option value="2">نسبة مئوية / متوسط (AVG)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>وحدة القياس</label>
                                <select name="unit" class="form-control">
                                    <option value="1">عدد</option>
                                    <option value="2">نسبة مئوية %</option>
                                    <option value="3">مبلغ (ريال)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>الوزن النسبى (%)</label>
                                <input type="number" name="weight" class="form-control" min="1" max="100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ المؤشر</button>
                </div>
            </form>
        </div>
    </div>
</div>