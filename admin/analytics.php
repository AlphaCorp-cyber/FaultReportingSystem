<?php
$page_title = 'Analytics';
require_once '../config/config.php';
require_once '../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();

// Get analytics data
$monthly_trends = $db->select(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_faults,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_faults,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_faults
    FROM fault_reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC"
);

$peak_hours = $db->select(
    "SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as fault_count
    FROM fault_reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC"
);

$location_analysis = $db->select(
    "SELECT 
        location,
        COUNT(*) as fault_count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM fault_reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY location
    HAVING fault_count > 1
    ORDER BY fault_count DESC
    LIMIT 10"
);

$response_time_analysis = $db->select(
    "SELECT 
        category,
        AVG(DATEDIFF(updated_at, created_at)) as avg_response_time,
        MIN(DATEDIFF(updated_at, created_at)) as min_response_time,
        MAX(DATEDIFF(updated_at, created_at)) as max_response_time
    FROM fault_reports 
    WHERE status = 'resolved' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY category
    ORDER BY avg_response_time DESC"
);

// Predictive analytics - fault patterns
$seasonal_patterns = $db->select(
    "SELECT 
        MONTH(created_at) as month,
        category,
        COUNT(*) as fault_count
    FROM fault_reports 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
    GROUP BY MONTH(created_at), category
    ORDER BY month ASC, fault_count DESC"
);

// Performance metrics
$performance_metrics = $db->selectOne(
    "SELECT 
        AVG(DATEDIFF(updated_at, created_at)) as avg_resolution_time,
        COUNT(*) as total_processed,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as total_resolved,
        SUM(CASE WHEN DATEDIFF(updated_at, created_at) <= 3 THEN 1 ELSE 0 END) as resolved_within_3_days,
        SUM(CASE WHEN priority = 'high' AND DATEDIFF(updated_at, created_at) <= 1 THEN 1 ELSE 0 END) as high_priority_fast_resolved
    FROM fault_reports 
    WHERE status IN ('resolved', 'closed') 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)"
);

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Analytics Dashboard</h2>
                    <p class="text-muted">Advanced analytics and predictive insights</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="refreshAnalytics()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo round($performance_metrics['avg_resolution_time'], 1); ?></h4>
                            <p class="mb-0">Avg Resolution Time (Days)</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo round(($performance_metrics['total_resolved'] / $performance_metrics['total_processed']) * 100, 1); ?>%</h4>
                            <p class="mb-0">Resolution Rate</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo round(($performance_metrics['resolved_within_3_days'] / $performance_metrics['total_processed']) * 100, 1); ?>%</h4>
                            <p class="mb-0">Resolved Within 3 Days</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tachometer-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-1"><?php echo $performance_metrics['high_priority_fast_resolved']; ?></h4>
                            <p class="mb-0">High Priority Quick Fixes</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bolt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Trends (Last 12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Peak Hours Analysis</h5>
                </div>
                <div class="card-body">
                    <canvas id="peakHoursChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Response Time by Category</h5>
                </div>
                <div class="card-body">
                    <canvas id="responseTimeChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Hotspot Locations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Total Faults</th>
                                    <th>Resolved</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($location_analysis as $location): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($location['location']); ?></td>
                                        <td><span class="badge bg-primary"><?php echo $location['fault_count']; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $location['resolved_count']; ?></span></td>
                                        <td>
                                            <?php $rate = round(($location['resolved_count'] / $location['fault_count']) * 100, 1); ?>
                                            <span class="badge bg-<?php echo $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger'); ?>">
                                                <?php echo $rate; ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Predictive Analytics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Seasonal Patterns & Predictions</h5>
                </div>
                <div class="card-body">
                    <canvas id="seasonalChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights and Recommendations -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Key Insights</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Performance Insights</h6>
                        <ul class="mb-0">
                            <li>Average resolution time: <?php echo round($performance_metrics['avg_resolution_time'], 1); ?> days</li>
                            <li>Best performing category: 
                                <?php 
                                $best_category = $db->selectOne("SELECT category FROM fault_reports WHERE status = 'resolved' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY category ORDER BY AVG(DATEDIFF(updated_at, created_at)) ASC LIMIT 1");
                                echo $best_category ? getFaultCategoryName($best_category['category']) : 'N/A';
                                ?>
                            </li>
                            <li>Peak reporting hours: 
                                <?php 
                                $peak_hour = $db->selectOne("SELECT HOUR(created_at) as hour FROM fault_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY HOUR(created_at) ORDER BY COUNT(*) DESC LIMIT 1");
                                echo $peak_hour ? $peak_hour['hour'] . ':00' : 'N/A';
                                ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Action Items</h6>
                        <ul class="mb-0">
                            <?php if ($performance_metrics['avg_resolution_time'] > 5): ?>
                                <li>Resolution time exceeds target - consider resource allocation</li>
                            <?php endif; ?>
                            <?php if (($performance_metrics['total_resolved'] / $performance_metrics['total_processed']) < 0.8): ?>
                                <li>Resolution rate below 80% - review workflow processes</li>
                            <?php endif; ?>
                            <?php if (!empty($location_analysis)): ?>
                                <li>Focus on hotspot locations for preventive maintenance</li>
                            <?php endif; ?>
                            <li>Schedule preventive maintenance during low-peak hours</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Monthly Trends Chart
const monthlyData = <?php echo json_encode($monthly_trends); ?>;
const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Total Faults',
            data: monthlyData.map(item => item.total_faults),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            fill: true
        }, {
            label: 'Resolved',
            data: monthlyData.map(item => item.resolved_faults),
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true
        }, {
            label: 'High Priority',
            data: monthlyData.map(item => item.high_priority_faults),
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Peak Hours Chart
const peakHoursData = <?php echo json_encode($peak_hours); ?>;
const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
new Chart(peakHoursCtx, {
    type: 'bar',
    data: {
        labels: peakHoursData.map(item => item.hour + ':00'),
        datasets: [{
            label: 'Fault Reports',
            data: peakHoursData.map(item => item.fault_count),
            backgroundColor: 'rgba(255, 193, 7, 0.8)',
            borderColor: '#ffc107',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Response Time Chart
const responseTimeData = <?php echo json_encode($response_time_analysis); ?>;
const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
new Chart(responseTimeCtx, {
    type: 'bar',
    data: {
        labels: responseTimeData.map(item => {
            const categories = <?php echo json_encode(FAULT_CATEGORIES); ?>;
            return categories[item.category] || item.category;
        }),
        datasets: [{
            label: 'Avg Response Time (Days)',
            data: responseTimeData.map(item => parseFloat(item.avg_response_time)),
            backgroundColor: 'rgba(23, 162, 184, 0.8)',
            borderColor: '#17a2b8',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Seasonal Patterns Chart
const seasonalData = <?php echo json_encode($seasonal_patterns); ?>;
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Group data by category
const categoryData = {};
seasonalData.forEach(item => {
    if (!categoryData[item.category]) {
        categoryData[item.category] = new Array(12).fill(0);
    }
    categoryData[item.category][item.month - 1] = item.fault_count;
});

const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8', '#fd7e14'];
let colorIndex = 0;

const datasets = Object.keys(categoryData).map(category => {
    const color = colors[colorIndex % colors.length];
    colorIndex++;
    
    return {
        label: category,
        data: categoryData[category],
        borderColor: color,
        backgroundColor: color + '20',
        fill: false
    };
});

new Chart(seasonalCtx, {
    type: 'line',
    data: {
        labels: monthNames,
        datasets: datasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function refreshAnalytics() {
    location.reload();
}
</script>

<?php include '../includes/footer.php'; ?>
