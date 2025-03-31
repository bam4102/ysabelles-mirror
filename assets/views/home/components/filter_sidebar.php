<!-- Filter Sidebar -->
<div class="filter-sidebar" id="filterSidebar">
    <div class="filter-header">
        <h5>Filter Products</h5>
        <div class="d-flex align-items-center">
            <button id="sidebarToggle" class="sidebar-toggle">
                <span></span>
            </button>
            <button type="button" class="btn-close d-lg-none ms-2" id="closeFilterBtn" aria-label="Close"></button>
        </div>
    </div>
    
    <form id="filterForm">
        <!-- Location Filter -->
        <div class="filter-section">
            <h6>Location</h6>
            <select class="form-select" id="locationFilter" name="location">
                <option value="">All Locations</option>
                <option value="BACOLOD CITY">BACOLOD CITY</option>
                <option value="DUMAGUETE CITY">DUMAGUETE CITY</option>
                <option value="ILOILO CITY">ILOILO CITY</option>
            </select>
        </div>
        
        <!-- Date Filter -->
        <div class="filter-section">
            <h6>Date</h6>
            <div class="mb-2">
                <label for="datePickup" class="small">Pick-up Date:</label>
                <input type="date" class="form-control" id="datePickup" name="date_pickup">
            </div>
            <div class="mb-2">
                <label for="dateReturn" class="small">Return Date:</label>
                <input type="date" class="form-control" id="dateReturn" name="date_return">
            </div>
            <small class="text-muted mt-1 d-block">Filter products available between these dates</small>
        </div>
        
        <!-- Size Filter -->
        <div class="filter-section">
            <h6>Size</h6>
            <input type="text" class="form-control" id="sizeFilter" name="size" placeholder="Enter size">
        </div>
        
        <!-- Measurements Filter -->
        <!-- Bust Range Filter -->
        <div class="filter-section">
            <h6>Bust (inches)</h6>
            <div class="range-inputs">
                <input type="number" class="form-control" id="bustMin" name="bust_min" placeholder="Min">
                <span class="range-separator">to</span>
                <input type="number" class="form-control" id="bustMax" name="bust_max" placeholder="Max">
            </div>
        </div>
        
        <!-- Waist Range Filter -->
        <div class="filter-section">
            <h6>Waist (inches)</h6>
            <div class="range-inputs">
                <input type="number" class="form-control" id="waistMin" name="waist_min" placeholder="Min">
                <span class="range-separator">to</span>
                <input type="number" class="form-control" id="waistMax" name="waist_max" placeholder="Max">
            </div>
        </div>
        
        <!-- Length Range Filter -->
        <div class="filter-section">
            <h6>Length (inches)</h6>
            <div class="range-inputs">
                <input type="number" class="form-control" id="lengthMin" name="length_min" placeholder="Min">
                <span class="range-separator">to</span>
                <input type="number" class="form-control" id="lengthMax" name="length_max" placeholder="Max">
            </div>
        </div>
        
        <!-- Price Range Filter -->
        <div class="filter-section">
            <h6>Price Range</h6>
            <div class="range-inputs">
                <input type="number" class="form-control" id="priceMin" name="price_min" placeholder="Min">
                <span class="range-separator">to</span>
                <input type="number" class="form-control" id="priceMax" name="price_max" placeholder="Max">
            </div>
        </div>
        
        <!-- Filter Action Buttons -->
        <div class="filter-actions">
            <button type="button" class="btn btn-secondary" id="resetFiltersBtn">Reset</button>
            <button type="submit" class="btn btn-primary" id="applyFiltersBtn">Apply Filters</button>
        </div>
    </form>
</div>

<!-- Filter Backdrop Overlay (only visible on mobile) -->
<div class="filter-backdrop d-lg-none" id="filterBackdrop"></div>