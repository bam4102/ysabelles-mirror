<?php
/**
 * Product Model
 * Handles database operations for products
 */
class ProductModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all products with images
     * 
     * @param array $options Optional parameters (limit, offset, orderBy, sortDirection)
     * @return array Array of products with their images
     */
    public function getProducts($options = []) {
        // Set default options
        $defaultOptions = [
            'limit' => 0,
            'offset' => 0,
            'orderBy' => 'nameProduct',
            'sortDirection' => 'ASC',
            'onlyActive' => true
        ];
        
        // Merge default options with provided options
        $options = array_merge($defaultOptions, $options);
        
        try {
            // Log start of product retrieval
            error_log("Starting product retrieval with options: " . json_encode($options));
            
            // Base query - Modified to get ALL images for each product and include variation data
            $sql = "
                SELECT p.productID, p.nameProduct, p.priceProduct, p.colorProduct, 
                    p.typeProduct, pc.productCategory, p.genderProduct, p.descProduct,
                    p.soldProduct, p.damageProduct, p.counterProduct, p.createdAt,
                    p.bustProduct, p.waistProduct, p.lengthProduct, p.sizeProduct,
                    p.locationProduct, p.codeProduct, p.returnedProduct, p.useProduct, p.isNew,
                    pic.pictureLocation, pic.isPrimary, pic.pictureID,
                    psv.group_id, psv.id as variation_id
                FROM product p
                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
                LEFT JOIN picture pic ON p.productID = pic.productID AND pic.isActive = 1
                LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add filters - only hide sold products, allow damaged products to be shown
            if ($options['onlyActive']) {
                $sql .= " AND p.soldProduct = 0";
                error_log("Applied active filter: excluding sold products, but showing damaged products");
            }
            
            // No GROUP BY - We want all images
            
            // Add ordering
            $sql .= " ORDER BY p." . $this->sanitizeColumnName($options['orderBy']);
            
            if (strtoupper($options['sortDirection']) === 'DESC') {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }
            
            // Add limit if needed
            if ($options['limit'] > 0) {
                $sql .= " LIMIT ?, ?";
                $params[] = (int)$options['offset'];
                $params[] = (int)$options['limit'];
            }
            
            // Log final SQL query for debugging
            error_log("Product query: " . $sql);
            error_log("Query parameters: " . json_encode($params));
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters correctly for LIMIT clause
            if (!empty($params)) {
                for ($i = 0; $i < count($params); $i++) {
                    // PDO params are 1-indexed
                    $paramIndex = $i + 1;
                    $stmt->bindValue($paramIndex, $params[$i], PDO::PARAM_INT);
                }
            }
            
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Retrieved " . count($products) . " product records");
            if (count($products) == 0) {
                // Log more detailed information about the empty result
                error_log("Warning: No products found. This could indicate a database issue or empty product table.");
                
                // Perform a simple count to check if the table has any records
                $countCheck = $this->pdo->query("SELECT COUNT(*) as count FROM product");
                $totalCount = $countCheck->fetch(PDO::FETCH_ASSOC)['count'];
                error_log("Total count in product table: " . $totalCount);
                
                // Check how many are sold
                $soldCheck = $this->pdo->query("SELECT COUNT(*) as count FROM product WHERE soldProduct = 1");
                $soldCount = $soldCheck->fetch(PDO::FETCH_ASSOC)['count'];
                error_log("Count of sold products: " . $soldCount);
                
                // Check how many are damaged
                $damagedCheck = $this->pdo->query("SELECT COUNT(*) as count FROM product WHERE damageProduct = 1");
                $damagedCount = $damagedCheck->fetch(PDO::FETCH_ASSOC)['count'];
                error_log("Count of damaged products: " . $damagedCount);
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            error_log("SQL Query that failed: " . $sql);
            error_log("Query parameters: " . json_encode($params ?? []));
            return [];
        }
    }
    
    /**
     * Get total count of available products
     * 
     * @param bool $onlyActive Whether to count only active products
     * @return int Total number of products
     */
    public function getTotalProductCount($onlyActive = true) {
        try {
            $sql = "
                SELECT COUNT(DISTINCT p.productID) as total
                FROM product p
                WHERE 1=1
            ";
            
            if ($onlyActive) {
                $sql .= " AND p.soldProduct = 0"; // Only exclude sold products
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error counting products: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Sanitize column names to prevent SQL injection via column names
     */
    private function sanitizeColumnName($column) {
        $allowedColumns = [
            'productID', 'nameProduct', 'priceProduct', 'counterProduct', 'createdAt',
            'bustProduct', 'waistProduct', 'lengthProduct', 'sizeProduct', 
            'colorProduct', 'locationProduct', 'genderProduct', 'typeProduct',
            'soldProduct', 'damageProduct', 'returnedProduct', 'isNew'
        ];
        
        if (in_array($column, $allowedColumns)) {
            return $column;
        }
        
        return 'nameProduct'; // Default to nameProduct if invalid column is provided
    }
    
    /**
     * Get products that are variations of the same base product
     * 
     * @param int $productId The product ID to find variations for
     * @param bool $includeInactive Whether to include inactive (sold) products
     * @return array Array of product variations
     */
    public function getProductVariations($productId, $includeInactive = false) {
        try {
            $sql = "
                SELECT p.productID, p.nameProduct, p.priceProduct, p.sizeProduct, p.colorProduct,
                       p.soldProduct, p.damageProduct
                FROM product p
                JOIN product_size_variations psv1 ON p.productID = psv1.product_id
                JOIN product_size_variations psv2 ON psv1.group_id = psv2.group_id
                WHERE psv2.product_id = ?
                AND p.productID != ?
            ";
            
            // Exclude sold products but include damaged ones
            if (!$includeInactive) {
                $sql .= " AND p.soldProduct = 0";
            }
            
            $sql .= " ORDER BY p.nameProduct ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $productId, PDO::PARAM_INT);
            $stmt->bindValue(2, $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($variations) . " variations for product ID " . $productId);
            
            return $variations;
        } catch (PDOException $e) {
            error_log("Error fetching product variations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get transaction history data for availability filtering
     * Retrieves transaction records to determine product availability
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Transaction records with product and date information
     */
    public function getTransactionHistoryForAvailability($startDate = null, $endDate = null) {
        try {
            // First, get all active transactions (products that have been released but not returned)
            $activeTransactionsSql = "
                SELECT ph.productID, t.datePickUp, t.dateReturn, p.sizeProduct, 
                       psv.group_id as variation_group_id
                FROM product_history ph
                JOIN `transaction` t ON ph.transactionID = t.transactionID
                JOIN product p ON ph.productID = p.productID
                LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
                WHERE ph.action_type = 'RELEASE'
                AND NOT EXISTS (
                    SELECT 1 FROM product_history ph2 
                    WHERE ph2.productID = ph.productID 
                    AND ph2.transactionID = ph.transactionID 
                    AND ph2.action_type = 'RETURN'
                )
            ";
            
            $activeStmt = $this->pdo->prepare($activeTransactionsSql);
            $activeStmt->execute();
            $activeTransactions = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Retrieved " . count($activeTransactions) . " active transactions");
            
            // Now get the transaction history
            $sql = "
                SELECT ph.historyID, ph.productID, ph.transactionID, ph.employeeID, 
                       ph.action_type, ph.action_date, t.datePickUp, t.dateReturn,
                       p.sizeProduct, psv.group_id as variation_group_id
                FROM product_history ph
                LEFT JOIN `transaction` t ON ph.transactionID = t.transactionID
                JOIN product p ON ph.productID = p.productID
                LEFT JOIN product_size_variations psv ON p.productID = psv.product_id
                WHERE ph.action_type IN ('RELEASE', 'RETURN')
            ";
            
            // Add date filtering if dates are provided
            $params = [];
            if ($startDate && $endDate) {
                // We need to find products that are available between these dates
                // This means either:
                // 1. Products that have no active transactions in this period
                // 2. Products that have been returned before the start date
                // 3. Products that will be released after the end date
                $sql .= " AND (
                    (ph.action_type = 'RETURN' AND ph.action_date <= ?) 
                    OR 
                    (ph.action_type = 'RELEASE' AND ph.action_date >= ?)
                    OR
                    (ph.productID NOT IN (
                        SELECT DISTINCT ph2.productID 
                        FROM product_history ph2
                        JOIN `transaction` t2 ON ph2.transactionID = t2.transactionID
                        WHERE ph2.action_type = 'RELEASE'
                        AND t2.datePickUp <= ? 
                        AND t2.dateReturn >= ?
                    ))
                )";
                $params[] = $startDate;
                $params[] = $endDate;
                $params[] = $endDate;
                $params[] = $startDate;
            }
            
            $sql .= " ORDER BY ph.productID, ph.action_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters if any
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Retrieved " . count($transactions) . " transaction records for availability filtering" . 
                      ($startDate && $endDate ? " between $startDate and $endDate" : ""));
            
            // Get all product variation groups
            $variationsSql = "
                SELECT psv.product_id, psv.group_id, p.sizeProduct
                FROM product_size_variations psv
                JOIN product p ON psv.product_id = p.productID
                WHERE psv.group_id IS NOT NULL
            ";
            
            $variationsStmt = $this->pdo->prepare($variationsSql);
            $variationsStmt->execute();
            $variations = $variationsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Retrieved " . count($variations) . " product variations");
            
            // Combine active transactions with transaction history and variations
            $result = [
                'active' => $activeTransactions,
                'history' => $transactions,
                'variations' => $variations
            ];
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error fetching transaction history: " . $e->getMessage());
            return [
                'active' => [],
                'history' => [],
                'variations' => []
            ];
        }
    }
    
    /**
     * Get upcoming product reservations within a date range
     * This is useful for checking if products are available for rent between specific dates
     * 
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Products with reservations in the specified date range
     */
    public function getProductReservations($startDate = null, $endDate = null) {
        try {
            // Base query to get all reservations
            $sql = "
                SELECT p.productID, p.nameProduct, p.locationProduct, p.soldProduct, 
                       t.transactionID, t.datePickUp, t.dateReturn, t.clientName,
                       ph.action_type, ph.action_date
                FROM product p
                JOIN product_history ph ON p.productID = ph.productID
                JOIN `transaction` t ON ph.transactionID = t.transactionID
                WHERE 1=1
            ";
            
            $params = [];
            
            // Add date range filtering if dates are provided
            if ($startDate && $endDate) {
                // Find reservations that overlap with the requested period
                // A reservation overlaps if:
                // - The reservation pickup date is before or equal to the requested end date AND
                // - The reservation return date is after or equal to the requested start date
                $sql .= " AND t.datePickUp <= ? AND t.dateReturn >= ?";
                $params[] = $endDate;
                $params[] = $startDate;
            }
            
            // Only include active reservations (ones where the product has been released but not returned)
            $sql .= " AND (
                    (ph.action_type = 'RELEASE' AND NOT EXISTS (
                        SELECT 1 FROM product_history ph2 
                        WHERE ph2.productID = ph.productID 
                        AND ph2.transactionID = ph.transactionID 
                        AND ph2.action_type = 'RETURN'
                    ))
                    OR
                    (ph.action_type = 'RELEASE' AND t.datePickUp >= CURRENT_DATE())
                )";
                
            // Exclude sold products
            $sql .= " AND p.soldProduct = 0";
            
            // Order by date for easier processing
            $sql .= " ORDER BY t.datePickUp, t.dateReturn";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters if any
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
            
            $stmt->execute();
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Retrieved " . count($reservations) . " product reservations" . 
                      ($startDate && $endDate ? " between $startDate and $endDate" : ""));
            
            return $reservations;
            
        } catch (PDOException $e) {
            error_log("Error fetching product reservations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all categories organized by type
     * 
     * @return array Categories organized by type
     */
    public function getCategories() {
        try {
            $sql = "SELECT * FROM productcategory ORDER BY productCategory";
            $stmt = $this->pdo->query($sql);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize categories by their classification
            $categorized = [
                'MENSWEAR' => [],
                'WOMENSWEAR' => [],
                'WEDDING' => [],
                'BOYS' => [],
                'GIRLS' => [],
                'ACCESSORIES' => []
            ];
            
            foreach ($categories as $category) {
                $classification = $category['genderCategory'] ?? 'WOMENSWEAR';
                if (array_key_exists($classification, $categorized)) {
                    $categorized[$classification][] = $category;
                }
            }
            
            return $categorized;
        } catch (PDOException $e) {
            error_log("Error fetching categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all entourage sets with their associated products and pictures
     * 
     * @param array $options Optional parameters (limit, offset, orderBy, sortDirection)
     * @return array Array of entourage sets with their products and pictures
     */
    public function getProductEntourage($options = []) {
        // Set default options
        $defaultOptions = [
            'limit' => 0,
            'offset' => 0,
            'orderBy' => 'nameEntourage',
            'sortDirection' => 'ASC',
            'onlyActive' => true
        ];
        
        // Merge default options with provided options
        $options = array_merge($defaultOptions, $options);
        
        try {
            // Log start of entourage retrieval
            error_log("Starting entourage retrieval with options: " . json_encode($options));
            
            // First query to get all entourage sets
            $entourageQuery = "SELECT entourageID, nameEntourage FROM entourage ORDER BY nameEntourage";
            $entourageStmt = $this->pdo->query($entourageQuery);
            $entourageResults = $entourageStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($entourageResults) . " entourage sets in the database");
            
            // Initialize entourage sets array
            $entourageSets = [];
            foreach ($entourageResults as $row) {
                $entourageSets[$row['entourageID']] = [
                    'entourageID' => $row['entourageID'],
                    'nameEntourage' => $row['nameEntourage'],
                    'products' => [],
                    'pictures' => []
                ];
            }
            
            // Get products associated with entourage sets - Method 1: products that have an entourageID
            $sql1 = "
                SELECT 
                    p.productID,
                    p.entourageID,
                    p.nameProduct,
                    p.priceProduct,
                    p.colorProduct,
                    p.typeProduct,
                    pc.productCategory,
                    p.genderProduct,
                    p.descProduct,
                    p.soldProduct,
                    p.damageProduct,
                    p.counterProduct,
                    p.createdAt,
                    p.bustProduct,
                    p.waistProduct,
                    p.lengthProduct,
                    p.sizeProduct,
                    p.locationProduct,
                    p.codeProduct,
                    p.returnedProduct,
                    p.useProduct,
                    p.isNew
                FROM product p
                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
                WHERE p.entourageID IS NOT NULL
            ";
            
            // Add filters for products
            if ($options['onlyActive']) {
                $sql1 .= " AND (p.soldProduct = 0 OR p.soldProduct IS NULL)";
            }
            
            $stmt1 = $this->pdo->prepare($sql1);
            $stmt1->execute();
            $productsByEntourageId = $stmt1->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($productsByEntourageId) . " products with entourageID");
            
            // Process products that have entourageID
            foreach ($productsByEntourageId as $row) {
                $entourageID = $row['entourageID'];
                
                // Skip if this entourage is not in our set
                if (!isset($entourageSets[$entourageID])) continue;
                
                // Add product
                $product = [
                    'productID' => $row['productID'],
                    'nameProduct' => $row['nameProduct'],
                    'priceProduct' => $row['priceProduct'],
                    'colorProduct' => $row['colorProduct'],
                    'typeProduct' => $row['typeProduct'],
                    'productCategory' => $row['productCategory'],
                    'genderProduct' => $row['genderProduct'],
                    'descProduct' => $row['descProduct'],
                    'soldProduct' => $row['soldProduct'],
                    'damageProduct' => $row['damageProduct'],
                    'counterProduct' => $row['counterProduct'],
                    'createdAt' => $row['createdAt'],
                    'bustProduct' => $row['bustProduct'],
                    'waistProduct' => $row['waistProduct'],
                    'lengthProduct' => $row['lengthProduct'],
                    'sizeProduct' => $row['sizeProduct'],
                    'locationProduct' => $row['locationProduct'],
                    'codeProduct' => $row['codeProduct'],
                    'returnedProduct' => $row['returnedProduct'],
                    'useProduct' => $row['useProduct'],
                    'isNew' => $row['isNew']
                ];
                
                // Only add if not already added
                if (!in_array($product['productID'], array_column($entourageSets[$entourageID]['products'], 'productID'))) {
                    $entourageSets[$entourageID]['products'][] = $product;
                }
            }
            
            // Get products associated with entourage sets - Method 2: entourage records that have a productID
            $sql2 = "
                SELECT 
                    e.entourageID,
                    e.nameEntourage,
                    p.productID,
                    p.nameProduct,
                    p.priceProduct,
                    p.colorProduct,
                    p.typeProduct,
                    pc.productCategory,
                    p.genderProduct,
                    p.descProduct,
                    p.soldProduct,
                    p.damageProduct,
                    p.counterProduct,
                    p.createdAt,
                    p.bustProduct,
                    p.waistProduct,
                    p.lengthProduct,
                    p.sizeProduct,
                    p.locationProduct,
                    p.codeProduct,
                    p.returnedProduct,
                    p.useProduct,
                    p.isNew
                FROM entourage e
                JOIN product p ON e.productID = p.productID
                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
                WHERE e.productID IS NOT NULL
            ";
            
            // Add filters for products
            if ($options['onlyActive']) {
                $sql2 .= " AND (p.soldProduct = 0 OR p.soldProduct IS NULL)";
            }
            
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute();
            $entourageWithProductId = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($entourageWithProductId) . " entourage records with productID");
            
            // Process entourage records that have productID
            foreach ($entourageWithProductId as $row) {
                $entourageID = $row['entourageID'];
                
                // Skip if this entourage is not in our set
                if (!isset($entourageSets[$entourageID])) continue;
                
                // Add product
                $product = [
                    'productID' => $row['productID'],
                    'nameProduct' => $row['nameProduct'],
                    'priceProduct' => $row['priceProduct'],
                    'colorProduct' => $row['colorProduct'],
                    'typeProduct' => $row['typeProduct'],
                    'productCategory' => $row['productCategory'],
                    'genderProduct' => $row['genderProduct'],
                    'descProduct' => $row['descProduct'],
                    'soldProduct' => $row['soldProduct'],
                    'damageProduct' => $row['damageProduct'],
                    'counterProduct' => $row['counterProduct'],
                    'createdAt' => $row['createdAt'],
                    'bustProduct' => $row['bustProduct'],
                    'waistProduct' => $row['waistProduct'],
                    'lengthProduct' => $row['lengthProduct'],
                    'sizeProduct' => $row['sizeProduct'],
                    'locationProduct' => $row['locationProduct'],
                    'codeProduct' => $row['codeProduct'],
                    'returnedProduct' => $row['returnedProduct'],
                    'useProduct' => $row['useProduct'],
                    'isNew' => $row['isNew']
                ];
                
                // Only add if not already added
                if (!in_array($product['productID'], array_column($entourageSets[$entourageID]['products'], 'productID'))) {
                    $entourageSets[$entourageID]['products'][] = $product;
                }
            }
            
            // Now query for pictures by entourage ID
            $entouragePicsSql = "
                SELECT 
                    entourageID,
                    pictureID,
                    pictureLocation,
                    isPrimary,
                    isActive,
                    fileType,
                    fileSize,
                    dateAdded
                FROM picture
                WHERE entourageID IS NOT NULL
                AND isActive = 1
            ";
            
            $entouragePicsStmt = $this->pdo->prepare($entouragePicsSql);
            $entouragePicsStmt->execute();
            $entouragePics = $entouragePicsStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($entouragePics) . " pictures directly associated with entourage sets");
            
            // Process entourage pictures
            foreach ($entouragePics as $pic) {
                $entourageID = $pic['entourageID'];
                
                // Skip if this entourage is not in our set
                if (!isset($entourageSets[$entourageID])) continue;
                
                // Add picture
                $picture = [
                    'pictureID' => $pic['pictureID'],
                    'pictureLocation' => $pic['pictureLocation'],
                    'isPrimary' => $pic['isPrimary'],
                    'isActive' => $pic['isActive'],
                    'fileType' => $pic['fileType'],
                    'fileSize' => $pic['fileSize'],
                    'dateAdded' => $pic['dateAdded']
                ];
                
                // Only add if not already added
                if (!in_array($picture['pictureID'], array_column($entourageSets[$entourageID]['pictures'], 'pictureID'))) {
                    $entourageSets[$entourageID]['pictures'][] = $picture;
                }
            }
            
            // Get product pictures for each entourage product
            foreach ($entourageSets as $entourageID => $entourageSet) {
                if (empty($entourageSet['products'])) continue;
                
                // Extract product IDs
                $productIDs = array_column($entourageSet['products'], 'productID');
                
                if (empty($productIDs)) continue;
                
                // Get product pictures
                $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
                $productPicsSql = "
                    SELECT 
                        productID,
                        pictureID,
                        pictureLocation,
                        isPrimary,
                        isActive,
                        fileType,
                        fileSize,
                        dateAdded
                    FROM picture
                    WHERE productID IN ($placeholders)
                    AND isActive = 1
                ";
                
                $productPicsStmt = $this->pdo->prepare($productPicsSql);
                foreach ($productIDs as $index => $productID) {
                    $productPicsStmt->bindValue($index + 1, $productID, PDO::PARAM_INT);
                }
                
                $productPicsStmt->execute();
                $productPics = $productPicsStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Found " . count($productPics) . " pictures for products in entourage ID: $entourageID");
                
                // Assign pictures to individual products
                foreach ($entourageSet['products'] as &$product) {
                    $product['pictures'] = [];
                    foreach ($productPics as $pic) {
                        if ($pic['productID'] == $product['productID']) {
                            $product['pictures'][] = [
                                'pictureID' => $pic['pictureID'],
                                'pictureLocation' => $pic['pictureLocation'],
                                'isPrimary' => $pic['isPrimary'],
                                'isActive' => $pic['isActive'],
                                'fileType' => $pic['fileType'],
                                'fileSize' => $pic['fileSize'],
                                'dateAdded' => $pic['dateAdded']
                            ];
                        }
                    }
                    
                    // Set main picture for product if available
                    if (!empty($product['pictures'])) {
                        $primaryPic = current(array_filter($product['pictures'], function($pic) {
                            return $pic['isPrimary'] == 1;
                        }));
                        
                        $product['pictureLocation'] = $primaryPic ? $primaryPic['pictureLocation'] : $product['pictures'][0]['pictureLocation'];
                    }
                }
                
                // Also add product pictures to entourage pictures for comprehensive display
                foreach ($productPics as $pic) {
                    $picture = [
                        'pictureID' => $pic['pictureID'],
                        'pictureLocation' => $pic['pictureLocation'],
                        'isPrimary' => $pic['isPrimary'],
                        'isActive' => $pic['isActive'],
                        'fileType' => $pic['fileType'],
                        'fileSize' => $pic['fileSize'],
                        'dateAdded' => $pic['dateAdded'],
                        'fromProduct' => true,
                        'productID' => $pic['productID']
                    ];
                    
                    // Only add if not already added
                    if (!in_array($picture['pictureID'], array_column($entourageSets[$entourageID]['pictures'], 'pictureID'))) {
                        $entourageSets[$entourageID]['pictures'][] = $picture;
                    }
                }
            }
            
            // Apply pagination if needed
            $result = array_values($entourageSets);
            
            if ($options['limit'] > 0) {
                $start = (int)$options['offset'];
                $length = (int)$options['limit'];
                $result = array_slice($result, $start, $length);
            }
            
            error_log("Returning " . count($result) . " entourage sets after processing");
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error fetching entourage sets: " . $e->getMessage());
            error_log("SQL Query that failed: " . ($sql ?? 'Query not generated yet'));
            return [];
        }
    }
    
    /**
     * Get a specific entourage set by ID with its products and pictures
     * 
     * @param int $entourageID The ID of the entourage set to fetch
     * @return array|null The entourage set data or null if not found
     */
    public function getEntourageById($entourageID) {
        try {
            // Log the request
            error_log("Fetching entourage by ID: $entourageID");
            
            // Direct query for specific entourage set by ID
            $sql = "
                SELECT 
                    e.entourageID,
                    e.nameEntourage,
                    p.productID,
                    p.nameProduct,
                    p.priceProduct,
                    p.colorProduct,
                    p.typeProduct,
                    pc.productCategory,
                    p.genderProduct,
                    p.descProduct,
                    p.soldProduct,
                    p.damageProduct,
                    p.counterProduct,
                    p.createdAt,
                    p.bustProduct,
                    p.waistProduct,
                    p.lengthProduct,
                    p.sizeProduct,
                    p.locationProduct,
                    p.codeProduct,
                    p.returnedProduct,
                    p.useProduct,
                    p.isNew,
                    pic.pictureLocation,
                    pic.isPrimary,
                    pic.pictureID
                FROM entourage e
                LEFT JOIN product p ON e.productID = p.productID
                LEFT JOIN productcategory pc ON p.categoryID = pc.categoryID
                LEFT JOIN picture pic ON (
                    (e.entourageID = pic.entourageID OR pic.productID = p.productID) 
                    AND pic.isActive = 1
                )
                WHERE e.entourageID = ?
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $entourageID, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Retrieved " . count($results) . " rows for entourage ID: $entourageID");
            
            if (empty($results)) {
                error_log("No entourage found with ID: $entourageID");
                return null;
            }
            
            // Organize results into an entourage set
            $entourageSet = [
                'entourageID' => $results[0]['entourageID'],
                'nameEntourage' => $results[0]['nameEntourage'],
                'products' => [],
                'pictures' => []
            ];
            
            foreach ($results as $row) {
                // Add product if it exists
                if ($row['productID']) {
                    $product = [
                        'productID' => $row['productID'],
                        'nameProduct' => $row['nameProduct'],
                        'priceProduct' => $row['priceProduct'],
                        'colorProduct' => $row['colorProduct'],
                        'typeProduct' => $row['typeProduct'],
                        'productCategory' => $row['productCategory'],
                        'genderProduct' => $row['genderProduct'],
                        'descProduct' => $row['descProduct'],
                        'soldProduct' => $row['soldProduct'],
                        'damageProduct' => $row['damageProduct'],
                        'counterProduct' => $row['counterProduct'],
                        'createdAt' => $row['createdAt'],
                        'bustProduct' => $row['bustProduct'],
                        'waistProduct' => $row['waistProduct'],
                        'lengthProduct' => $row['lengthProduct'],
                        'sizeProduct' => $row['sizeProduct'],
                        'locationProduct' => $row['locationProduct'],
                        'codeProduct' => $row['codeProduct'],
                        'returnedProduct' => $row['returnedProduct'],
                        'useProduct' => $row['useProduct'],
                        'isNew' => $row['isNew']
                    ];
                    
                    // Only add if not already added
                    if (!in_array($product['productID'], array_column($entourageSet['products'], 'productID'))) {
                        $entourageSet['products'][] = $product;
                    }
                }
                
                // Add picture if it exists
                if ($row['pictureLocation']) {
                    $picture = [
                        'pictureID' => $row['pictureID'],
                        'pictureLocation' => $row['pictureLocation'],
                        'isPrimary' => $row['isPrimary']
                    ];
                    
                    // Only add if not already added
                    if (!in_array($picture['pictureID'], array_column($entourageSet['pictures'], 'pictureID'))) {
                        $entourageSet['pictures'][] = $picture;
                    }
                }
            }
            
            error_log("Successfully processed entourage data for ID: $entourageID");
            error_log("Found " . count($entourageSet['products']) . " products and " . count($entourageSet['pictures']) . " pictures");
            
            return $entourageSet;
            
        } catch (Exception $e) {
            error_log("Error fetching entourage by ID: " . $e->getMessage());
            return null;
        }
    }
} 