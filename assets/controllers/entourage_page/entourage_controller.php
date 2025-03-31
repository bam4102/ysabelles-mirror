<?php
/**
 * Entourage Controller
 * Handles requests for entourage data using the ProductModel
 */

// Include database connection and model
require_once __DIR__ . '/../../controllers/db.php';
require_once __DIR__ . '/../../models/ProductModel.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Parse request path to determine the endpoint
$requestPath = $_SERVER['REQUEST_URI'] ?? '';
$requestParts = explode('/', trim($requestPath, '/'));
$endpoint = end($requestParts);

try {
    // Check if PDO object is valid
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Database connection not established or invalid");
    }

    // Initialize product model
    $productModel = new ProductModel($pdo);
    
    // Check if this is a comprehensive data request (all data in one call)
    if (isset($_GET['comprehensive'])) {
        // This is a request for ALL entourage data in a single connection
        // Get all entourage sets
        $entourageSets = $productModel->getProductEntourage([
            'limit' => 0,
            'offset' => 0,
            'orderBy' => 'nameEntourage',
            'sortDirection' => 'ASC',
            'onlyActive' => true
        ]);
        
        if (empty($entourageSets)) {
            echo json_encode([
                'sets' => [],
                'imagesMap' => [],
                'message' => 'No entourage sets found'
            ]);
            exit;
        }
        
        // Create a list of all entourage IDs to fetch images for
        $allEntourageIds = array_column($entourageSets, 'entourageID');
        
        // Also gather all product IDs related to these entourages
        $allProductIds = [];
        foreach ($entourageSets as $set) {
            if (!empty($set['products'])) {
                foreach ($set['products'] as $product) {
                    $allProductIds[] = $product['productID'];
                }
            }
        }
        
        // Create a map to store images by entourage ID
        $imagesMap = [];
        
        // Fetch all images for all entourages in a single query if we have entourage IDs
        if (!empty($allEntourageIds)) {
            $placeholders = implode(',', array_fill(0, count($allEntourageIds), '?'));
            $imagesStmt = $pdo->prepare("
                SELECT 
                    entourageID,
                    pictureID, 
                    pictureLocation, 
                    isPrimary,
                    isActive,
                    fileType,
                    fileSize,
                    dateAdded
                FROM 
                    picture
                WHERE 
                    entourageID IN ($placeholders)
                    AND isActive = 1
                ORDER BY 
                    entourageID,
                    isPrimary DESC, 
                    dateAdded DESC
            ");
            
            foreach ($allEntourageIds as $index => $entourageID) {
                $imagesStmt->bindValue($index + 1, $entourageID, PDO::PARAM_INT);
            }
            
            $imagesStmt->execute();
            $allEntourageImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize images by entourage ID
            foreach ($allEntourageImages as $image) {
                $entourageID = $image['entourageID'];
                if (!isset($imagesMap[$entourageID])) {
                    $imagesMap[$entourageID] = [];
                }
                $imagesMap[$entourageID][] = $image;
            }
        }
        
        // Fetch all product images in a single query if we have product IDs
        $productImagesMap = [];
        if (!empty($allProductIds)) {
            $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
            $productImagesStmt = $pdo->prepare("
                SELECT 
                    productID,
                    pictureID, 
                    pictureLocation, 
                    isPrimary,
                    isActive,
                    fileType,
                    fileSize,
                    dateAdded
                FROM 
                    picture
                WHERE 
                    productID IN ($placeholders)
                    AND isActive = 1
                ORDER BY 
                    productID,
                    isPrimary DESC, 
                    dateAdded DESC
            ");
            
            foreach ($allProductIds as $index => $productID) {
                $productImagesStmt->bindValue($index + 1, $productID, PDO::PARAM_INT);
            }
            
            $productImagesStmt->execute();
            $allProductImages = $productImagesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize product images by product ID
            foreach ($allProductImages as $image) {
                $productID = $image['productID'];
                if (!isset($productImagesMap[$productID])) {
                    $productImagesMap[$productID] = [];
                }
                $image['fromProduct'] = true;
                $productImagesMap[$productID][] = $image;
                
                // Also add to the entourage images map if this product is in an entourage
                foreach ($entourageSets as $set) {
                    if (!empty($set['products'])) {
                        foreach ($set['products'] as $product) {
                            if ($product['productID'] == $productID) {
                                $entourageID = $set['entourageID'];
                                if (!isset($imagesMap[$entourageID])) {
                                    $imagesMap[$entourageID] = [];
                                }
                                if (!in_array($image, $imagesMap[$entourageID])) {
                                    $imagesMap[$entourageID][] = $image;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Enhance entourage sets with their images
        foreach ($entourageSets as &$set) {
            $entourageID = $set['entourageID'];
            
            // Add entourage images
            $set['images'] = $imagesMap[$entourageID] ?? [];
            
            // Filter to get only direct entourage images (not from products)
            $entourageOnlyImages = array_filter($set['images'], function($img) {
                return !isset($img['fromProduct']);
            });
            
            // Use entourage-only images if available, otherwise fall back to all images
            $set['displayImages'] = !empty($entourageOnlyImages) ? $entourageOnlyImages : $set['images'];
            
            // Enhance product data with images
            if (!empty($set['products'])) {
                foreach ($set['products'] as &$product) {
                    $productID = $product['productID'];
                    $product['images'] = $productImagesMap[$productID] ?? [];
                    
                    // Set primary image for product
                    if (!empty($product['images'])) {
                        $primaryImage = array_filter($product['images'], function($img) {
                            return $img['isPrimary'] == 1;
                        });
                        
                        if (!empty($primaryImage)) {
                            $product['pictureLocation'] = reset($primaryImage)['pictureLocation'];
                        } else {
                            $product['pictureLocation'] = $product['images'][0]['pictureLocation'];
                        }
                    }
                }
            }
        }
        
        // Return all data in a single response
        echo json_encode([
            'sets' => $entourageSets,
            'imagesMap' => $imagesMap,
            'productImagesMap' => $productImagesMap,
            'total' => count($entourageSets)
        ]);
        
    } else if (strpos($requestPath, 'images') !== false && isset($_GET['id'])) {
        // This handles requests for entourage images
        $entourageID = (int)$_GET['id'];
        
        // Fetch images for a specific entourage
        $stmt = $pdo->prepare("
            SELECT 
                pictureID, 
                pictureLocation, 
                isPrimary,
                isActive,
                fileType,
                fileSize,
                dateAdded
            FROM 
                picture
            WHERE 
                entourageID = :entourageID 
                AND isActive = 1
            ORDER BY 
                isPrimary DESC, 
                dateAdded DESC
        ");
        
        $stmt->execute(['entourageID' => $entourageID]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also fetch product images if products are associated with this entourage
        $productImages = [];
        
        // First, check for products linked to this entourage
        $productsStmt = $pdo->prepare("
            SELECT productID FROM product WHERE entourageID = :entourageID
            UNION
            SELECT productID FROM entourage WHERE entourageID = :entourageID AND productID IS NOT NULL
        ");
        
        $productsStmt->execute(['entourageID' => $entourageID]);
        $productIDs = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If we have products, get their images
        if (!empty($productIDs)) {
            $placeholders = implode(',', array_fill(0, count($productIDs), '?'));
            $productImagesStmt = $pdo->prepare("
                SELECT 
                    productID,
                    pictureID, 
                    pictureLocation, 
                    isPrimary,
                    isActive,
                    fileType,
                    fileSize,
                    dateAdded
                FROM 
                    picture
                WHERE 
                    productID IN ($placeholders)
                    AND isActive = 1
                ORDER BY 
                    isPrimary DESC, 
                    dateAdded DESC
            ");
            
            foreach ($productIDs as $index => $productID) {
                $productImagesStmt->bindValue($index + 1, $productID, PDO::PARAM_INT);
            }
            
            $productImagesStmt->execute();
            $productImages = $productImagesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add a source flag to identify these as product images
            foreach ($productImages as &$img) {
                $img['fromProduct'] = true;
            }
            
            // Merge all images together
            $images = array_merge($images, $productImages);
        }
        
        echo json_encode([
            'success' => true,
            'entourageID' => $entourageID,
            'imageCount' => count($images),
            'images' => $images
        ]);
        
    } else if (isset($_GET['id'])) {
        // This handles requests for a specific entourage
        $entourageID = (int)$_GET['id'];
        $entourageSet = $productModel->getEntourageById($entourageID);
        
        if (!$entourageSet) {
            throw new Exception("Entourage set not found");
        }
        
        echo json_encode($entourageSet);
        
    } else {
        // This handles requests for all entourage sets with pagination
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
        
        // Get entourage sets with pagination
        $entourageSets = $productModel->getProductEntourage([
            'offset' => $offset,
            'limit' => $limit,
            'orderBy' => 'nameEntourage',
            'sortDirection' => 'ASC',
            'onlyActive' => true
        ]);
        
        // Check if we got an empty result
        if (empty($entourageSets)) {
            // Try with unlimited results to see if there's any data at all
            $allEntourageSets = $productModel->getProductEntourage([
                'limit' => 0,
                'offset' => 0,
                'orderBy' => 'nameEntourage',
                'sortDirection' => 'ASC',
                'onlyActive' => false
            ]);
            
            if (!empty($allEntourageSets)) {
                // Return the first few sets for testing
                echo json_encode(array_slice($allEntourageSets, 0, min(5, count($allEntourageSets))));
                exit;
            }
        }
        
        // Return entourage sets as JSON
        echo json_encode($entourageSets);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in entourage_controller.php: " . $e->getMessage());
    
    // Return error message
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
