<?php
/**
 * Administrator Category Management
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$pageTitle = 'Category Management';
$pdo = Database::getConnection();

$action = $_POST['action'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            // Check duplicate
            $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $check->execute([$name]);
            if ($check->fetch()) {
                $error = "A category named '{$name}' already exists.";
            } else {
                $insert = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $insert->execute([$name, $description]);
                setFlashMessage('success', "Category '{$name}' added successfully.");
                redirect('admin/categories.php');
            }
        }

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || empty($name)) {
            $error = 'Category ID and name are required.';
        } else {
            $check = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $check->execute([$name, $id]);
            if ($check->fetch()) {
                $error = "Another category already exists with the name '{$name}'.";
            } else {
                $update = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $update->execute([$name, $description, $id]);
                setFlashMessage('success', "Category '{$name}' updated.");
                redirect('admin/categories.php');
            }
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        // Safeguard check: Are any food items linked to this category?
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE category_id = ?");
        $stmt->execute([$id]);
        $itemCount = (int)$stmt->fetchColumn();

        if ($itemCount > 0) {
            setFlashMessage('danger', "Cannot delete category: {$itemCount} food item(s) are currently assigned to it. Please reassign or remove the items first.");
        } else {
            $delete = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $delete->execute([$id]);
            setFlashMessage('success', 'Category deleted successfully.');
        }
        redirect('admin/categories.php');
    }
}

// Fetch categories with food count
$categories = $pdo->query("
    SELECT c.*, COUNT(f.id) as food_count
    FROM categories c
    LEFT JOIN food_items f ON f.category_id = c.id
    GROUP BY c.id
    ORDER BY c.id ASC
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Category Management</h2>
        <p class="text-muted mb-0">Organize menu items into logical food categories.</p>
    </div>
    <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-folder-plus me-1"></i> Add Category
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small mb-4">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Linked Items</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="text-muted"><?= $cat['id'] ?></td>
                        <td class="fw-bold text-dark"><?= e($cat['name']) ?></td>
                        <td class="text-muted small"><?= e($cat['description']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= (int)$cat['food_count'] ?> dishes</span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editCatModal<?= $cat['id'] ?>">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>

                            <form method="POST" action="categories.php" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1" <?= ($cat['food_count'] > 0) ? 'title="Has linked items"' : '' ?>>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editCatModal<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0">
                                <form method="POST" action="categories.php">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Category</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Category Name</label>
                                            <input type="text" name="name" class="form-control" value="<?= e($cat['name']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Description</label>
                                            <textarea name="description" class="form-control" rows="3"><?= e($cat['description']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">Update Category</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="POST" action="categories.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. South Indian Specials" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this food group..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
