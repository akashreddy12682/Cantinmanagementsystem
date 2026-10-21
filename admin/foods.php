<?php
/**
 * Administrator Food Items Management (CRUD)
 */

define('APP_INIT', true);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

$pageTitle = 'Food Management';
$pdo = Database::getConnection();

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$error = '';
$success = '';

// Handle Create / Edit / Delete / Toggle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            $error = 'Please provide valid food item name, category, and price greater than zero.';
        } else {
            // Handle image upload if provided
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = handleImageUpload($_FILES['image']);
                if (!$uploadResult['success']) {
                    $error = $uploadResult['error'];
                } else {
                    $imageName = $uploadResult['filename'];
                }
            }

            if (empty($error)) {
                $insert = $pdo->prepare("
                    INSERT INTO food_items (category_id, name, description, price, image, is_available)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert->execute([$categoryId, $name, $description, $price, $imageName, $isAvailable]);
                setFlashMessage('success', "Food item '{$name}' created successfully!");
                redirect('admin/foods.php');
            }
        }

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if ($id <= 0 || empty($name) || $categoryId <= 0 || $price <= 0) {
            $error = 'Please provide valid food item details.';
        } else {
            // Check if existing image needs updating
            $stmt = $pdo->prepare("SELECT image FROM food_items WHERE id = ?");
            $stmt->execute([$id]);
            $currentImage = $stmt->fetchColumn();
            $imageToSave = $currentImage;

            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = handleImageUpload($_FILES['image']);
                if (!$uploadResult['success']) {
                    $error = $uploadResult['error'];
                } else {
                    $imageToSave = $uploadResult['filename'];
                }
            }

            if (empty($error)) {
                $update = $pdo->prepare("
                    UPDATE food_items 
                    SET category_id = ?, name = ?, description = ?, price = ?, image = ?, is_available = ?
                    WHERE id = ?
                ");
                $update->execute([$categoryId, $name, $description, $price, $imageToSave, $isAvailable, $id]);
                setFlashMessage('success', "Food item '{$name}' updated successfully!");
                redirect('admin/foods.php');
            }
        }

    } elseif ($action === 'toggle_availability') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        $newStatus = ($status === 1) ? 0 : 1;

        $update = $pdo->prepare("UPDATE food_items SET is_available = ? WHERE id = ?");
        $update->execute([$newStatus, $id]);

        setFlashMessage('info', 'Item availability updated.');
        redirect('admin/foods.php');

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        // Safeguard check: has this item been ordered?
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE food_item_id = ?");
        $checkStmt->execute([$id]);
        $orderCount = (int)$checkStmt->fetchColumn();

        if ($orderCount > 0) {
            // It was ordered in past history: to preserve audit integrity, mark unavailable instead of hard delete
            $update = $pdo->prepare("UPDATE food_items SET is_available = 0 WHERE id = ?");
            $update->execute([$id]);
            setFlashMessage('warning', "Food item is linked to existing order receipts. To preserve past records, it has been marked as 'Sold Out / Unavailable' instead of deleting.");
        } else {
            $delete = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
            $delete->execute([$id]);
            setFlashMessage('success', "Food item deleted successfully.");
        }
        redirect('admin/foods.php');
    }
}

// Search & Filter
$search = trim($_GET['q'] ?? '');
$categoryFilter = (int)($_GET['cat'] ?? 0);

$query = "
    SELECT f.*, c.name as category_name
    FROM food_items f
    JOIN categories c ON f.category_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($categoryFilter > 0) {
    $query .= " AND f.category_id = ?";
    $params[] = $categoryFilter;
}

$query .= " ORDER BY f.category_id ASC, f.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$foodItems = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Food Management</h2>
        <p class="text-muted mb-0">Add, edit, and control menu dishes and item stock availability.</p>
    </div>
    <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addFoodModal">
        <i class="bi bi-plus-circle me-1"></i> Add Food Item
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small mb-4">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- Search & Filter Controls -->
<div class="admin-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search dish name..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="cat" class="form-select">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($categoryFilter === (int)$cat['id']) ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
            <?php if (!empty($search) || $categoryFilter > 0): ?>
                <a href="foods.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Foods Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($foodItems)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No food items found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($foodItems as $food): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= getFoodImageUrl($food['image']) ?>" alt="<?= e($food['name']) ?>" width="48" height="48" class="rounded-3 object-fit-cover border">
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($food['name']) ?></div>
                                        <small class="text-muted d-block text-truncate" style="max-width: 280px;"><?= e($food['description']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border"><?= e($food['category_name']) ?></span>
                            </td>
                            <td class="fw-bold text-dark"><?= formatPrice($food['price']) ?></td>
                            <td>
                                <form method="POST" action="foods.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_availability">
                                    <input type="hidden" name="id" value="<?= $food['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $food['is_available'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $food['is_available'] ? 'btn-success' : 'btn-outline-danger' ?> rounded-pill py-0.5 px-2.5 small" title="Click to toggle availability">
                                        <i class="bi <?= $food['is_available'] ? 'bi-check-circle-fill' : 'bi-x-circle' ?> me-1"></i>
                                        <?= $food['is_available'] ? 'In Stock' : 'Sold Out' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal<?= $food['id'] ?>">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                                
                                <form method="POST" action="foods.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete <?= addslashes(e($food['name'])) ?>?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $food['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal for this item -->
                        <div class="modal fade" id="editModal<?= $food['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0">
                                    <form method="POST" action="foods.php" enctype="multipart/form-data">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= $food['id'] ?>">

                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Food Item</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Item Name</label>
                                                <input type="text" name="name" class="form-control" value="<?= e($food['name']) ?>" required>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Category</label>
                                                    <select name="category_id" class="form-select" required>
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?= $cat['id'] ?>" <?= ($food['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                                                <?= e($cat['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Price (INR ₹)</label>
                                                    <input type="number" step="0.5" name="price" class="form-control" value="<?= $food['price'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Description</label>
                                                <textarea name="description" class="form-control" rows="2"><?= e($food['description']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Update Image (JPEG, PNG, WebP max 2MB)</label>
                                                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                            </div>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_available" id="availCheck<?= $food['id'] ?>" <?= $food['is_available'] ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="availCheck<?= $food['id'] ?>">Available for Ordering</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Food Modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="POST" action="foods.php" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Food Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Food Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Masala Dosa" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Price (INR ₹)</label>
                            <input type="number" step="0.5" name="price" class="form-control" placeholder="60.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Ingredients, taste, serving details..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Food Photo (Optional, JPEG, PNG, WebP max 2MB)</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_available" id="newAvailCheck" checked>
                        <label class="form-check-label small" for="newAvailCheck">In Stock / Available Immediately</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Create Food Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
