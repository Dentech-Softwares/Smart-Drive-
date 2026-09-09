<?php
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Our Vehicles - Smart Drive Car Hire';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$transmissionFilter = isset($_GET['transmission']) ? sanitize($_GET['transmission']) : '';
$fuelFilter = isset($_GET['fuel_type']) ? sanitize($_GET['fuel_type']) : '';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sql = "SELECT v.*, c.name as category_name, CONCAT(v.brand, ' ', v.model) as name FROM vehicles v 
        JOIN vehicle_categories c ON v.category_id = c.id 
        WHERE " . getVehicleListingVisibilityCondition('v');
$params = [];
if ($categoryFilter > 0) {
    $sql .= " AND v.category_id = ?";
    $params[] = $categoryFilter;
}
if ($transmissionFilter) {
    $sql .= " AND v.transmission = ?";
    $params[] = $transmissionFilter;
}
if ($fuelFilter) {
    $sql .= " AND v.fuel_type = ?";
    $params[] = $fuelFilter;
}
if ($minPrice > 0) {
    $sql .= " AND v.price_per_day >= ?";
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $sql .= " AND v.price_per_day <= ?";
    $params[] = $maxPrice;
}
$sql .= " ORDER BY v.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();
$categories = $pdo->query("SELECT * FROM vehicle_categories WHERE status = 'active' ORDER BY name")->fetchAll();
?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Our Vehicle Fleet</h2>
            <p>Choose from our wide selection of premium vehicles</p>
            <div class="divider"></div>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Transmission</label>
                    <select name="transmission" class="auto-submit">
                        <option value="">All</option>
                        <option value="Automatic" <?php echo $transmissionFilter === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo $transmissionFilter === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fuel Type</label>
                    <select name="fuel_type" class="auto-submit">
                        <option value="">All</option>
                        <option value="Petrol" <?php echo $fuelFilter === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo $fuelFilter === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo $fuelFilter === 'Electric' ? 'selected' : ''; ?>>Electric</option>
                        <option value="Hybrid" <?php echo $fuelFilter === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Min Price (KSh)</label>
                    <input type="number" name="min_price" value="<?php echo $minPrice ?: ''; ?>" placeholder="Min" class="auto-submit">
                </div>
                <div class="form-group">
                    <label>Max Price (KSh)</label>
                    <input type="number" name="max_price" value="<?php echo $maxPrice ?: ''; ?>" placeholder="Max" class="auto-submit">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            </form>
        </div>
        <div class="category-filter">
            <a href="<?php echo BASE_URL; ?>vehicles.php<?php echo $transmissionFilter || $fuelFilter || $minPrice || $maxPrice ? '?transmission=' . urlencode($transmissionFilter) . '&fuel_type=' . urlencode($fuelFilter) . '&min_price=' . urlencode($minPrice) . '&max_price=' . urlencode($maxPrice) : ''; ?>" 
               class="category-chip <?php echo !$categoryFilter ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> All
            </a>
            <?php foreach ($categories as $cat): 
                $count = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE category_id = ? AND " . getVehicleListingVisibilityCondition(''));
                $count->execute([$cat['id']]);
                $catCount = $count->fetchColumn();
                
                $otherParams = http_build_query(array_filter([
                    'transmission' => $transmissionFilter,
                    'fuel_type' => $fuelFilter,
                    'min_price' => $minPrice ?: null,
                    'max_price' => $maxPrice ?: null,
                ]));
                $url = BASE_URL . 'vehicles.php?category=' . $cat['id'] . ($otherParams ? '&' . $otherParams : '');
            ?>
                <a href="<?php echo $url; ?>" class="category-chip <?php echo $categoryFilter == $cat['id'] ? 'active' : ''; ?>">
                    <i class="fas fa-car"></i>
                    <?php echo htmlspecialchars($cat['name']); ?>
                    <span class="chip-count"><?php echo $catCount; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <div class="empty-state">
                <i class="fas fa-car"></i>
                <h4>No Vehicles Found</h4>
                <p>No vehicles match your current filters. Try adjusting your search criteria.</p>
                <a href="<?php echo BASE_URL; ?>vehicles.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php else: ?>
            <div class="vehicles-grid">
                <?php foreach ($vehicles as $vehicle): 
                    $image = getVehiclePrimaryImage($vehicle['id']);
                ?>
                    <div class="vehicle-card hover-lift">
                        <div class="vehicle-card-image">
                             <img src="<?php echo $image ?: BASE_URL . 'assets/images/vehicles/default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                  onerror="this.src='<?php echo BASE_URL; ?>assets/images/vehicles/default.jpg'">
                             <span class="vehicle-badge">
                                 <i class="fas fa-car"></i>
                                 <?php echo htmlspecialchars($vehicle['category_name']); ?>
                             </span>
                             <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $vehicle['status'])); ?>">
                                 <?php echo htmlspecialchars($vehicle['status']); ?>
                             </span>
                        </div>
                        <div class="vehicle-card-body">
                            <h3><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h3>
                            <div class="category">
                                <i class="fas fa-car"></i>
                                <?php echo htmlspecialchars($vehicle['category_name']); ?>
                            </div>
                            <div class="vehicle-features">
                                <span class="vehicle-feature"><i class="fas fa-cog"></i> <?php echo $vehicle['transmission']; ?></span>
                                <span class="vehicle-feature"><i class="fas fa-gas-pump"></i> <?php echo $vehicle['fuel_type']; ?></span>
                                <span class="vehicle-feature"><i class="fas fa-users"></i> <?php echo $vehicle['seating_capacity']; ?> Seats</span>
                                <span class="vehicle-feature"><i class="fas fa-calendar"></i> <?php echo $vehicle['year']; ?></span>
                            </div>
                            <div class="vehicle-card-footer">
                                <div class="vehicle-price">
                                    KSh <?php echo number_format($vehicle['price_per_day'], 2); ?>
                                    <span>/day</span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-outline btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
</body>
</html>
