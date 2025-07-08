<?php
$page_title = 'My Fault Reports';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('resident');

$user = getCurrentUser();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query
$where_conditions = ["user_id = ?"];
$params = [$user['id']];

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($category_filter)) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ? OR reference_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$total_query = "SELECT COUNT(*) as total FROM fault_reports WHERE $where_clause";
$total_result = $db->selectOne($total_query, $params);
$total_faults = $total_result['total'];
$total_pages = ceil($total_faults / $per_page);

// Get fault reports
$query = "SELECT * FROM fault_reports WHERE $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$faults = $db->select($query, $params);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>My Fault Reports</h2>
                    <p class="text-muted">View and track your submitted fault reports</p>
                </div>
                <div>
                    <a href="submit_fault.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Submit New Report
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by title, description, or reference">
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach (FAULT_STATUSES as $key => $name): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo ($status_filter === $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach (FAULT_CATEGORIES as $key => $name): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo ($category_filter === $key) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                        <a href="my_faults.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Fault Reports 
                <span class="badge bg-secondary"><?php echo $total_faults; ?></span>
            </h5>
            
            <?php if ($total_faults > 0): ?>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportData('csv')">
                        <i class="fas fa-download me-1"></i>Export CSV
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportData('pdf')">
                        <i class="fas fa-file-pdf me-1"></i>Export PDF
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-body">
            <?php if (empty($faults)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No fault reports found</h4>
                    <p class="text-muted">
                        <?php if (!empty($search) || !empty($status_filter) || !empty($category_filter)): ?>
                            Try adjusting your filters or search terms.
                        <?php else: ?>
                            You haven't submitted any fault reports yet.
                        <?php endif; ?>
                    </p>
                    <a href="submit_fault.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Submit Your First Report
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Date Submitted</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faults as $fault): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($fault['reference_number']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($fault['title']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($fault['location']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo getFaultCategoryName($fault['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadgeClass($fault['status']); ?>">
                                            <?php echo getFaultStatusName($fault['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $fault['priority'] === 'high' ? 'danger' : ($fault['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($fault['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo formatDate($fault['created_at']); ?>
                                            <br>
                                            <span class="text-muted"><?php echo getTimeAgo($fault['created_at']); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo formatDate($fault['updated_at']); ?>
                                            <br>
                                            <span class="text-muted"><?php echo getTimeAgo($fault['updated_at']); ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewFaultDetails(<?php echo $fault['id']; ?>)"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    onclick="trackFault('<?php echo $fault['reference_number']; ?>')"
                                                    title="Track Progress">
                                                <i class="fas fa-route"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&category=<?php echo $category_filter; ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fault Details Modal -->
<div class="modal fade" id="faultDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fault Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="faultDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Tracking Modal -->
<div class="modal fade" id="trackingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fault Progress Tracking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="trackingContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewFaultDetails(faultId) {
    fetch(`../api/get_fault_details.php?id=${faultId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('faultDetailsContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('faultDetailsModal'));
                modal.show();
            } else {
                alert('Error loading fault details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading fault details');
        });
}

function trackFault(referenceNumber) {
    fetch(`../api/get_fault_details.php?ref=${referenceNumber}&track=true`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('trackingContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
                modal.show();
            } else {
                alert('Error loading tracking information');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading tracking information');
        });
}

function exportData(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    const exportUrl = `../api/export_faults.php?${params.toString()}`;
    window.open(exportUrl, '_blank');
}

// Auto-refresh page every 5 minutes for status updates
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000); // 5 minutes
</script>

<?php include '../includes/footer.php'; ?>
