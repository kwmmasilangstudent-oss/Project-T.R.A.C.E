<?php
require_once __DIR__ . '/../includes/auth.php';
requireAuth(['admin']);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$success = $_SESSION['_flash_success'] ?? '';
$error = $_SESSION['_flash_error'] ?? '';
unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_budget') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $source = trim($_POST['source'] ?? '');
        $type = trim($_POST['type'] ?? 'allocation');
        $description = trim($_POST['description'] ?? '');

        if ($projectId <= 0 || $amount <= 0) {
            $_SESSION['_flash_error'] = 'Valid project and amount are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO project_budget (project_id, amount, source, type, description) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$projectId, $amount, $source, $type, $description]);
            logAudit('add_budget', 'Added budget for project ID: ' . $projectId . ' amount: ' . $amount);
            $_SESSION['_flash_success'] = 'Budget entry added successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'add_expense') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($projectId <= 0 || $amount <= 0) {
            $_SESSION['_flash_error'] = 'Valid project and amount are required.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO expenses (project_id, amount, description) VALUES (?, ?, ?)');
            $stmt->execute([$projectId, $amount, $description]);
            logAudit('add_expense', 'Added expense for project ID: ' . $projectId . ' amount: ' . $amount);
            $_SESSION['_flash_success'] = 'Expense recorded successfully.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete_expense') {
        $expenseId = (int) ($_POST['expense_id'] ?? 0);
        if ($expenseId > 0) {
            $pdo->prepare('DELETE FROM expenses WHERE id = ?')->execute([$expenseId]);
            logAudit('delete_expense', 'Deleted expense ID: ' . $expenseId);
            $_SESSION['_flash_success'] = 'Expense deleted.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($action === 'delete_budget') {
        $budgetId = (int) ($_POST['budget_id'] ?? 0);
        if ($budgetId > 0) {
            $pdo->prepare('DELETE FROM project_budget WHERE id = ?')->execute([$budgetId]);
            logAudit('delete_budget', 'Deleted budget entry ID: ' . $budgetId);
            $_SESSION['_flash_success'] = 'Budget entry deleted.';
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$projects = $pdo->query('SELECT id, title, status FROM projects ORDER BY title')->fetchAll();

$filterProject = (int) ($_GET['project_id'] ?? 0);

$expensesQuery = 'SELECT e.*, p.title FROM expenses e LEFT JOIN projects p ON p.id = e.project_id WHERE 1=1';
$expenseParams = [];

$budgetWhere = '';
$budgetParams = [];

if ($filterProject) {
    $budgetWhere .= ' AND pb.project_id = ?';
    $expensesQuery .= ' AND e.project_id = ?';
    $budgetParams[] = $filterProject;
    $expenseParams[] = $filterProject;
}

$countBudgetBase = 'SELECT COUNT(*) FROM project_budget pb LEFT JOIN projects p ON p.id = pb.project_id WHERE 1=1';
$selectBudgetBase = 'SELECT pb.*, p.title FROM project_budget pb LEFT JOIN projects p ON p.id = pb.project_id WHERE 1=1';
$paginator = paginate($countBudgetBase . $budgetWhere, $budgetParams, $selectBudgetBase . $budgetWhere . ' ORDER BY pb.created_at DESC', $budgetParams);
$budgetRows = $paginator['data'];

$expenses = $pdo->prepare($expensesQuery);
$expenses->execute($expenseParams);
$expenseRows = $expenses->fetchAll();

$reportQuery = 'SELECT p.id, p.title,
    COALESCE((SELECT SUM(amount) FROM project_budget WHERE project_id = p.id), 0) as total_budget,
    COALESCE((SELECT SUM(amount) FROM expenses WHERE project_id = p.id), 0) as total_expenses
    FROM projects p ORDER BY p.title';
$reportRows = $pdo->query($reportQuery)->fetchAll();

$totalAllocation = 0;
$totalExpensesVal = 0;
foreach ($reportRows as $row) {
    $totalAllocation += (float) $row['total_budget'];
    $totalExpensesVal += (float) $row['total_expenses'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3>Budget Management</h3>
                    <p class="text-muted">Track project budgets, allocations, expenses, and remaining balances.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary">Allocated: ₱<?php echo number_format($totalAllocation, 2); ?></span>
                    <span class="badge bg-danger-subtle text-danger">Expenses: ₱<?php echo number_format($totalExpensesVal, 2); ?></span>
                    <span class="badge bg-success-subtle text-success">Remaining: ₱<?php echo number_format($totalAllocation - $totalExpensesVal, 2); ?></span>
                </div>
            </div>
            <?php if (!empty($success)) : ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <?php if (!empty($error)) : ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h5 class="mb-3">New Budget Allocation</h5>
                        <form method="post" class="row g-3">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="add_budget">
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">Select project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo (int) $project['id']; ?>"><?php echo e($project['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Source</label>
                                <input type="text" name="source" class="form-control" placeholder="e.g. Barangay Fund">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="allocation">Allocation</option>
                                    <option value="donation">Donation</option>
                                    <option value="grant">Grant</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary">Save Budget Entry</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h5 class="mb-3">Record Expense</h5>
                        <form method="post" class="row g-3">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="add_expense">
                            <div class="col-md-6">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">Select project</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo (int) $project['id']; ?>"><?php echo e($project['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control" placeholder="Expense details">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary">Save Expense</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="glass-card p-4 mb-4">
                <h5 class="mb-3">Financial Summary</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Allocated</th>
                                <th>Expenses</th>
                                <th>Remaining</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $row): ?>
                                <?php $remaining = (float) $row['total_budget'] - (float) $row['total_expenses']; ?>
                                <tr>
                                    <td><strong><?php echo e($row['title']); ?></strong></td>
                                    <td>₱<?php echo number_format($row['total_budget'], 2); ?></td>
                                    <td>₱<?php echo number_format($row['total_expenses'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $remaining < 0 ? 'danger' : 'success'; ?>-subtle text-<?php echo $remaining < 0 ? 'danger' : 'success'; ?>">
                                            ₱<?php echo number_format($remaining, 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px; min-width: 100px;">
                                            <div class="progress-bar bg-<?php echo $remaining >= 0 ? 'success' : 'danger'; ?>" style="width: <?php echo $row['total_budget'] > 0 ? min(100, max(0, (($row['total_budget'] - $remaining) / $row['total_budget']) * 100)) : 0; ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportRows)) : ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No projects yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card p-4 mb-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Filter by Project</label>
                        <select name="project_id" class="form-select">
                            <option value="0">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo (int) $project['id']; ?>" <?php echo $filterProject === (int) $project['id'] ? 'selected' : ''; ?>><?php echo e($project['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h5 class="mb-3">Budget Entries</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($budgetRows as $budget): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?php echo e($budget['title'] ?? 'Project'); ?></strong><br>
                                        <small class="text-muted"><?php echo e($budget['source']); ?> • <?php echo date('M d, Y', strtotime($budget['created_at'])); ?></small>
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success-subtle text-success">₱<?php echo number_format($budget['amount'], 2); ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-budget-id="<?php echo (int) $budget['id']; ?>" data-budget-title="<?php echo e($budget['source'] ?? 'Budget Entry'); ?>" data-delete-type="delete_budget" id="budgetDeleteBtn<?php echo (int) $budget['id']; ?>">Delete</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($budgetRows)) : ?>
                                <li class="list-group-item text-center text-muted">No budget entries.</li>
                            <?php endif; ?>
                        </ul>
                        <?php if (!empty($budgetRows)): ?>
                        <div class="mt-3 d-flex justify-content-center">
                            <?php echo renderPagination($paginator); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h5 class="mb-3">Expenses</h5>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($expenseRows as $expense): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?php echo e($expense['title'] ?? 'Project'); ?></strong><br>
                                        <small class="text-muted"><?php echo e($expense['description']); ?> • <?php echo date('M d, Y', strtotime($expense['created_at'])); ?></small>
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-danger-subtle text-danger">-₱<?php echo number_format($expense['amount'], 2); ?></span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-budget-id="<?php echo (int) $expense['id']; ?>" data-budget-title="<?php echo e($expense['title'] ?? 'Expense'); ?>" data-delete-type="delete_expense" id="expenseDeleteBtn<?php echo (int) $expense['id']; ?>">Delete</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($expenseRows)) : ?>
                                <li class="list-group-item text-center text-muted">No expenses recorded.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Delete Confirmation Toast -->
    <div id="deleteToastOverlay" class="delete-toast-overlay">
        <div class="delete-toast-container">
            <div class="delete-toast-card glass-card">
                <div class="delete-toast-header">
                    <div class="delete-toast-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="delete-toast-title">Delete Budget Item</h3>
                </div>
                <div class="delete-toast-message">
                    <p>Are you sure you want to delete <span id="deleteToastTitle"></span>? This action cannot be undone.</p>
                </div>
                <div class="delete-toast-buttons">
                    <button id="deleteToastCancel" class="btn btn-outline-secondary">Cancel</button>
                    <button id="deleteToastConfirm" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastOverlay = document.getElementById('deleteToastOverlay');
            const toastCancel = document.getElementById('deleteToastCancel');
            const toastConfirm = document.getElementById('deleteToastConfirm');
            const toastTitle = document.getElementById('deleteToastTitle');
            
            let pendingDeleteId = null;
            let pendingDeleteType = '';
            
            // Open delete toast when delete button is clicked
            document.querySelectorAll('[data-budget-id]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const budgetId = this.getAttribute('data-budget-id');
                    const budgetTitle = this.getAttribute('data-budget-title') || 'this budget entry';
                    const deleteType = this.getAttribute('data-delete-type') || 'delete_budget';
                    
                    pendingDeleteId = budgetId;
                    pendingDeleteType = deleteType;
                    toastTitle.textContent = budgetTitle;
                    toastOverlay.classList.add('active');
                });
            });
            
            // Close toast when clicking cancel or overlay
            toastCancel.addEventListener('click', function() {
                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
            });
            
            toastOverlay.addEventListener('click', function(e) {
                if (e.target === toastOverlay) {
                    toastOverlay.classList.remove('active');
                    pendingDeleteId = null;
                }
            });
            
            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && toastOverlay.classList.contains('active')) {
                    toastOverlay.classList.remove('active');
                    pendingDeleteId = null;
                }
            });
            
            // Handle confirm button click
            toastConfirm.addEventListener('click', function() {
                if (pendingDeleteId) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = pendingDeleteType;
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    if (pendingDeleteType === 'delete_budget') {
                        idInput.name = 'budget_id';
                    } else {
                        idInput.name = 'expense_id';
                    }
                    idInput.value = pendingDeleteId;
                    
                form.appendChild(actionInput);
                form.appendChild(idInput);
                
                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_csrf_token';
                csrfInput.value = '<?php echo e($_SESSION["_csrf_token"] ?? ""); ?>';
                form.appendChild(csrfInput);
                
                document.body.appendChild(form);
                form.submit();
                }
                
                toastOverlay.classList.remove('active');
                pendingDeleteId = null;
            });
        });
    </script>
