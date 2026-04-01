<?php
/**
 * Performance Reports Page — Manager Only
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireManager();

$pageTitle = __('reports');

$pdo = getDBConnection();
$designers = getDesigners();

// Get filter values
$filterDesigner = $_GET['designer'] ?? '';
$filterType     = $_GET['design_type'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to'] ?? '';

// Build WHERE clauses for filters
$where = [];
$params = [];

if ($filterDesigner) {
    $where[] = "t.assigned_to = ?";
    $params[] = (int)$filterDesigner;
}
if ($filterType) {
    $where[] = "t.design_type = ?";
    $params[] = $filterType;
}
if ($filterDateFrom) {
    $where[] = "t.created_at >= ?";
    $params[] = $filterDateFrom . ' 00:00:00';
}
if ($filterDateTo) {
    $where[] = "t.created_at <= ?";
    $params[] = $filterDateTo . ' 23:59:59';
}

$whereSQL = !empty($where) ? 'AND ' . implode(' AND ', $where) : '';

// Get performance data per designer
$sql = "SELECT 
            u.id AS designer_id,
            u.full_name AS designer_name,
            COUNT(t.id) AS total_tasks,
            SUM(CASE WHEN t.status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN t.status = 'new' THEN 1 ELSE 0 END) AS new_tasks
        FROM users u
        LEFT JOIN tasks t ON u.id = t.assigned_to $whereSQL
        WHERE u.role = 'designer'
        GROUP BY u.id, u.full_name
        ORDER BY u.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$performanceData = $stmt->fetchAll();

// Get breakdown by design type per designer
$typeSql = "SELECT 
                t.assigned_to,
                t.design_type,
                COUNT(*) AS count
            FROM tasks t
            JOIN users u ON t.assigned_to = u.id AND u.role = 'designer'
            WHERE 1=1 " . $whereSQL . "
            GROUP BY t.assigned_to, t.design_type
            ORDER BY t.assigned_to, t.design_type";

$typeStmt = $pdo->prepare($typeSql);
$typeStmt->execute($params);
$typeBreakdown = $typeStmt->fetchAll();

// Organize type breakdown by designer
$typeMap = [];
foreach ($typeBreakdown as $row) {
    $typeMap[$row['assigned_to']][$row['design_type']] = $row['count'];
}

// Overall totals
$totalAll = 0;
$deliveredAll = 0;
$inProgressAll = 0;
$newAll = 0;
foreach ($performanceData as $d) {
    $totalAll += $d['total_tasks'];
    $deliveredAll += $d['delivered'];
    $inProgressAll += $d['in_progress'];
    $newAll += $d['new_tasks'];
}

include __DIR__ . '/../templates/header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="form-group mb-1">
                <label class="form-label"><?= __('filter_by_designer') ?></label>
                <select name="designer" class="form-control">
                    <option value=""><?= __('all') ?></option>
                    <?php foreach ($designers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDesigner == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-1">
                <label class="form-label"><?= __('filter_by_type') ?></label>
                <select name="design_type" class="form-control">
                    <option value=""><?= __('all') ?></option>
                    <?php foreach (DESIGN_TYPES as $key => $labels): ?>
                    <option value="<?= $key ?>" <?= $filterType === $key ? 'selected' : '' ?>><?= $labels[getCurrentLang()] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-1">
                <label class="form-label"><?= __('date_from') ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= sanitize($filterDateFrom) ?>">
            </div>
            <div class="form-group mb-1">
                <label class="form-label"><?= __('date_to') ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= sanitize($filterDateTo) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><?= __('apply_filter') ?></button>
                <a href="/manager/reports.php" class="btn btn-outline btn-sm"><?= __('clear_filter') ?></a>
            </div>
        </div>
    </form>
</div>

<!-- Overall KPI -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon total">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $totalAll ?></div>
            <div class="kpi-label"><?= __('total_tasks') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon delivered">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $deliveredAll ?></div>
            <div class="kpi-label"><?= __('delivered_tasks') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon progress">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $inProgressAll ?></div>
            <div class="kpi-label"><?= __('in_progress_tasks') ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon new">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        </div>
        <div class="kpi-content">
            <div class="kpi-value"><?= $newAll ?></div>
            <div class="kpi-label"><?= __('new_tasks') ?></div>
        </div>
    </div>
</div>

<!-- Designer Performance Cards -->
<h2 class="section-title"><?= __('performance_summary') ?></h2>
<div class="report-grid">
    <?php foreach ($performanceData as $designer): ?>
    <div class="report-card">
        <div class="report-card-header">
            <h3 class="report-card-title"><?= sanitize($designer['designer_name']) ?></h3>
            <?php 
                $rate = $designer['total_tasks'] > 0 ? round(($designer['delivered'] / $designer['total_tasks']) * 100) : 0;
                $rateClass = $rate >= 70 ? 'high' : ($rate >= 40 ? 'medium' : 'low');
            ?>
            <span class="delivery-rate <?= $rateClass ?>" style="font-size:1rem;padding:0;"><?= $rate ?>%</span>
        </div>
        <div class="report-card-body">
            <div class="report-stat">
                <span class="report-stat-label"><?= __('total_assigned') ?></span>
                <span class="report-stat-value"><?= (int)$designer['total_tasks'] ?></span>
            </div>
            <div class="report-stat">
                <span class="report-stat-label"><?= __('total_delivered') ?></span>
                <span class="report-stat-value text-success"><?= (int)$designer['delivered'] ?></span>
            </div>
            <div class="report-stat">
                <span class="report-stat-label"><?= __('total_in_progress') ?></span>
                <span class="report-stat-value text-warning"><?= (int)$designer['in_progress'] ?></span>
            </div>
            <div class="report-stat">
                <span class="report-stat-label"><?= __('total_new') ?></span>
                <span class="report-stat-value" style="color:var(--info);"><?= (int)$designer['new_tasks'] ?></span>
            </div>
            <div class="report-stat">
                <span class="report-stat-label"><?= __('delivery_rate') ?></span>
                <span class="report-stat-value delivery-rate <?= $rateClass ?>" style="font-size:1rem;padding:0;"><?= $rate ?>%</span>
            </div>

            <!-- Design Type Breakdown -->
            <?php if (isset($typeMap[$designer['designer_id']])): ?>
            <div class="mt-2" style="border-top:1px solid var(--border);padding-top:12px;">
                <strong class="text-small" style="color:var(--text-secondary);"><?= __('breakdown_by_type') ?></strong>
                <?php foreach (DESIGN_TYPES as $typeKey => $typeLabels): ?>
                <div class="report-stat">
                    <span class="report-stat-label"><?= $typeLabels[getCurrentLang()] ?></span>
                    <span class="report-stat-value"><?= $typeMap[$designer['designer_id']][$typeKey] ?? 0 ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Detailed Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= __('report_title') ?></h2>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th><?= __('designer_name') ?></th>
                    <th><?= __('total_assigned') ?></th>
                    <th><?= __('total_delivered') ?></th>
                    <th><?= __('total_in_progress') ?></th>
                    <th><?= __('total_new') ?></th>
                    <th><?= __('delivery_rate') ?></th>
                    <?php foreach (DESIGN_TYPES as $typeKey => $typeLabels): ?>
                    <th><?= $typeLabels[getCurrentLang()] ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($performanceData as $designer): ?>
                <tr>
                    <td><strong><?= sanitize($designer['designer_name']) ?></strong></td>
                    <td><?= (int)$designer['total_tasks'] ?></td>
                    <td class="text-success"><?= (int)$designer['delivered'] ?></td>
                    <td class="text-warning"><?= (int)$designer['in_progress'] ?></td>
                    <td><?= (int)$designer['new_tasks'] ?></td>
                    <td>
                        <?php 
                            $rate = $designer['total_tasks'] > 0 ? round(($designer['delivered'] / $designer['total_tasks']) * 100) : 0;
                            $rateClass = $rate >= 70 ? 'high' : ($rate >= 40 ? 'medium' : 'low');
                        ?>
                        <span class="delivery-rate <?= $rateClass ?>" style="font-size:0.9rem;padding:0;"><?= $rate ?>%</span>
                    </td>
                    <?php foreach (DESIGN_TYPES as $typeKey => $typeLabels): ?>
                    <td><?= $typeMap[$designer['designer_id']][$typeKey] ?? 0 ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
