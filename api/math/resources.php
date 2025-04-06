<?php
require_once '../../config/db_connect.php';

// Get resources for mathematics
$resources_query = "
    SELECT * FROM resources 
    WHERE subject_id = 2 
    ORDER BY type ASC, title ASC
";
$resources_result = $conn->query($resources_query);

// Group resources by type
$grouped_resources = [];
while ($resource = $resources_result->fetch_assoc()) {
    $grouped_resources[$resource['type']][] = $resource;
}

// Define resource type icons and labels
$resource_types = [
    'book' => ['icon' => 'fas fa-book', 'label' => 'Books'],
    'video' => ['icon' => 'fas fa-video', 'label' => 'Videos'],
    'website' => ['icon' => 'fas fa-globe', 'label' => 'Websites'],
    'document' => ['icon' => 'fas fa-file-alt', 'label' => 'Documents']
];
?>

<div class="resources-content">
    <!-- Resource Type Tabs -->
    <ul class="nav nav-pills mb-4" role="tablist">
        <?php 
        $first = true;
        foreach ($resource_types as $type => $info):
            if (isset($grouped_resources[$type])):
        ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                        id="<?php echo $type; ?>-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#<?php echo $type; ?>-pane"
                        type="button"
                        role="tab">
                    <i class="<?php echo $info['icon']; ?> me-2"></i>
                    <?php echo $info['label']; ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($grouped_resources[$type]); ?></span>
                </button>
            </li>
        <?php
            $first = false;
            endif;
        endforeach;
        ?>
    </ul>
    
    <!-- Resource Content -->
    <div class="tab-content">
        <?php 
        $first = true;
        foreach ($resource_types as $type => $info):
            if (isset($grouped_resources[$type])):
        ?>
            <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                 id="<?php echo $type; ?>-pane"
                 role="tabpanel"
                 tabindex="0">
                
                <div class="row g-4">
                    <?php foreach ($grouped_resources[$type] as $resource): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="resource-icon me-3">
                                            <i class="<?php echo $info['icon']; ?> fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                            <?php if ($resource['link']): ?>
                                                <a href="<?php echo htmlspecialchars($resource['link']); ?>" 
                                                   class="small text-muted"
                                                   target="_blank">
                                                    Visit Resource <i class="fas fa-external-link-alt ms-1"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($resource['notes']): ?>
                                        <p class="card-text small text-muted mb-0">
                                            <?php echo htmlspecialchars($resource['notes']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <button class="btn btn-sm btn-outline-primary w-100"
                                            hx-get="/api/math/resource-details.php?id=<?php echo $resource['id']; ?>"
                                            hx-target="#resourceDetailsModal .modal-content">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            </div>
        <?php
            $first = false;
            endif;
        endforeach;
        ?>
    </div>
</div>

<!-- Resource Details Modal -->
<div class="modal fade" id="resourceDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<style>
.resource-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(13, 110, 253, 0.1);
    border-radius: 12px;
}

.nav-pills .nav-link {
    border-radius: 20px;
    padding: 0.5rem 1rem;
}

.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style> 