<?php
// Include database connection at the beginning to ensure $pdo is available
include_once __DIR__ . '/../db.php';

function handleFileUpload($file, $type, $id)
{
    $target_dir = "pictures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = "pic_" . uniqid() . rand(1000000, 9999999) . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        throw new Exception("File is not an image.");
    }

    // Check file size (5MB limit)
    if ($file["size"] > 5000000) {
        throw new Exception("File is too large (max 5MB).");
    }

    // Allow certain file formats
    if (!in_array($file_extension, ["jpg", "jpeg", "png"])) {
        throw new Exception("Only JPG, JPEG & PNG files are allowed.");
    }

    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        throw new Exception("Failed to upload file.");
    }

    return $target_file;
}



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        try {
            // Debug: Log the POST data
            error_log("DEBUG: Starting product insertion...");
            error_log("POST data: " . print_r($_POST, true));
            
            $pdo->beginTransaction();
            
            // 1. Get category details
            $stmt = $pdo->prepare("SELECT categoryID, genderCategory FROM productcategory WHERE categoryCode = ?");
            $stmt->execute([$_POST['product_type']]);
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Category Data: " . print_r($categoryData, true));

            if (!$categoryData) {
                throw new Exception("Category not found for code: " . $_POST['product_type']);
            }

            // Check if multiple sizes are enabled by seeing if we have size_variations data
            $hasMultipleSizes = false;
            if (!empty($_POST['size_variations']) && $_POST['size_variations'] !== '[]') {
                $hasMultipleSizes = true;
                $variations = json_decode($_POST['size_variations'], true);
                if (is_array($variations) && count($variations) > 0) {
                    // Check if the table exists, if not create it
                    $tableCheck = $pdo->query("SHOW TABLES LIKE 'product_size_variations'");
                    if ($tableCheck->rowCount() == 0) {
                        $pdo->exec("CREATE TABLE product_size_variations (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            group_id INT NOT NULL,
                            product_id INT NOT NULL,
                            nameProduct VARCHAR(255) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (product_id) REFERENCES product(productID) ON DELETE CASCADE
                        )");
                    }
                    
                    // Generate a new group ID for these variations
                    $stmt = $pdo->query("SELECT COALESCE(MAX(group_id), 0) + 1 AS new_group FROM product_size_variations");
                    $groupData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $groupId = $groupData['new_group'];
                    
                    // Process images once and store locations
                    $imageLocations = [];
                    if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
                        foreach ($_FILES['product_images']['name'] as $i => $name) {
                            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }

                            $file = [
                                "name" => $_FILES['product_images']['name'][$i],
                                "type" => $_FILES['product_images']['type'][$i],
                                "tmp_name" => $_FILES['product_images']['tmp_name'][$i],
                                "error" => $_FILES['product_images']['error'][$i],
                                "size" => $_FILES['product_images']['size'][$i]
                            ];

                            try {
                                $picture_location = handleFileUpload($file, 'product', 0);
                                $imageLocations[] = $picture_location;
                            } catch (Exception $e) {
                                error_log("Image upload error: " . $e->getMessage());
                            }
                        }
                    }
                    
                    $productsCreated = 0;
                    
                    // Create separate product for each item
                    foreach ($variations as $variation) {
                        // Skip invalid variations
                        if (empty($variation['size']) || !isset($variation['price']) || !isset($variation['quantity'])) {
                            continue;
                        }
                        
                        $size = $variation['size'];
                        $price = $variation['price'];
                        $quantity = intval($variation['quantity']);
                        $scanCodes = isset($variation['scanCodes']) ? $variation['scanCodes'] : [];
                        
                        // Create multiple products based on quantity
                        for ($i = 0; $i < $quantity; $i++) {
                            if (!isset($scanCodes[$i]) || empty(trim($scanCodes[$i]))) {
                                continue; // Skip if no scan code provided
                            }
                            
                            $scanCode = trim($scanCodes[$i]);
                            
                            // Insert individual product
                            $sql = "INSERT INTO product (
                                nameProduct, typeProduct, colorProduct, sizeProduct, 
                                locationProduct, genderProduct, priceProduct, categoryID,
                                bustProduct, waistProduct, lengthProduct, descProduct, codeProduct, entourageID
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = $pdo->prepare($sql);
                            
                            $stmt->execute([
                                $_POST['product_name'],
                                $_POST['product_type'],
                                $_POST['product_color'],
                                $size,
                                $_POST['product_location'],
                                $categoryData['genderCategory'],
                                $price,
                                $categoryData['categoryID'],
                                $_POST['product_bust'] ?: null,
                                $_POST['product_waist'] ?: null,
                                $_POST['product_length'] ?: null,
                                $_POST['product_description'] ?: null,
                                $scanCode,
                                !empty($_POST['entourage_id']) ? $_POST['entourage_id'] : null
                            ]);
                            
                            $productId = $pdo->lastInsertId();
                            $productsCreated++;
                            
                            // Link to group
                            $stmt = $pdo->prepare("INSERT INTO product_size_variations (group_id, product_id, nameProduct) VALUES (?, ?, ?)");
                            $stmt->execute([$groupId, $productId, $_POST['product_name']]);
                            
                            // Add images to this product
                            foreach ($imageLocations as $location) {
                                $stmt = $pdo->prepare("INSERT INTO picture (productID, pictureLocation) VALUES (?, ?)");
                                $stmt->execute([$productId, $location]);
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['success_message'] = "Created $productsCreated products successfully!";
                    header("Location: utilities_section.php");
                    exit;
                }
            }

            // Regular product insertion (single item)
            $sql = "INSERT INTO product (
                nameProduct,
                typeProduct,
                colorProduct,
                sizeProduct,
                locationProduct,
                genderProduct,
                priceProduct,
                categoryID,
                bustProduct,
                waistProduct,
                lengthProduct,
                descProduct,
                codeProduct,
                entourageID
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            error_log("SQL Query: " . $sql);

            $stmt = $pdo->prepare($sql);

            $params = [
                $_POST['product_name'],            // nameProduct
                $_POST['product_type'],            // typeProduct
                $_POST['product_color'],           // colorProduct
                $_POST['product_size'],            // sizeProduct
                $_POST['product_location'],        // locationProduct
                $categoryData['genderCategory'],    // genderProduct
                $_POST['product_price'],           // priceProduct
                $categoryData['categoryID'],        // categoryID
                $_POST['product_bust'] ?: null,    // bustProduct
                $_POST['product_waist'] ?: null,   // waistProduct
                $_POST['product_length'] ?: null,  // lengthProduct
                $_POST['product_description'] ?: null,      // descProduct
                !empty($_POST['product_scan']) ? $_POST['product_scan'] : null,  // codeProduct
                !empty($_POST['entourage_id']) ? $_POST['entourage_id'] : null  // entourageID
            ];

            error_log("Parameters: " . print_r($params, true));

            $result = $stmt->execute($params);

            if (!$result) {
                error_log("DB Error: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Database error: " . implode(", ", $stmt->errorInfo()));
            }

            $product_id = $pdo->lastInsertId();
            error_log("New product ID: " . $product_id);
            
            // Add entry to product_size_variations table for single product as well
            // Generate a new group ID
            $stmt = $pdo->query("SELECT COALESCE(MAX(group_id), 0) + 1 AS new_group FROM product_size_variations");
            $groupData = $stmt->fetch(PDO::FETCH_ASSOC);
            $groupId = $groupData['new_group'];
            
            // Insert into product_size_variations table
            $stmt = $pdo->prepare("INSERT INTO product_size_variations (group_id, product_id, nameProduct) VALUES (?, ?, ?)");
            $stmt->execute([$groupId, $product_id, $_POST['product_name']]);
            
            error_log("Added single product to product_size_variations with group_id: " . $groupId);
            
            // 3. Handle image uploads
            if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
                foreach ($_FILES['product_images']['name'] as $i => $name) {
                    if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    $file = [
                        "name" => $_FILES['product_images']['name'][$i],
                        "type" => $_FILES['product_images']['type'][$i],
                        "tmp_name" => $_FILES['product_images']['tmp_name'][$i],
                        "error" => $_FILES['product_images']['error'][$i],
                        "size" => $_FILES['product_images']['size'][$i]
                    ];

                    try {
                        $picture_location = handleFileUpload($file, 'product', $product_id);
                        $stmt = $pdo->prepare("INSERT INTO picture (productID, pictureLocation) VALUES (?, ?)");
                        $stmt->execute([$product_id, $picture_location]);
                    } catch (Exception $e) {
                        error_log("Image upload error: " . $e->getMessage());
                        // Continue with other images even if one fails
                    }
                }
            }

            $pdo->commit();
            $_SESSION['success_message'] = "Product added successfully!";
            header("Location: utilities_section.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("ERROR: Product insertion failed: " . $e->getMessage());
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            header("Location: utilities_section.php");
            exit;
        }
    }

    if (isset($_POST['add_category'])) {
        try {
            // Validate categoryCode format (assuming it should be uppercase letters)
            if (empty($_POST['category_name']) || empty($_POST['category_code']) || empty($_POST['genderCat'])) {
                throw new Exception("All fields are required");
            }

            $category_code = strtoupper($_POST['category_code']);
            if (!preg_match('/^[A-Z]+$/', $category_code)) {
                throw new Exception("Category code must contain only letters");
            }

            // Check if category code already exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM productcategory WHERE categoryCode = ?");
            $check_stmt->execute([$category_code]);
            if ($check_stmt->fetchColumn() > 0) {
                throw new Exception("Category code already exists");
            }

            // Insert new category
            $stmt = $pdo->prepare("INSERT INTO productcategory (productCategory, categoryCode, genderCategory) VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['category_name'],
                $category_code,
                $_POST['genderCat']
            ]);

            $success_message = "Category added successfully!";
        } catch (Exception $e) {
            $error_message = "Error adding category: " . $e->getMessage();
        }
    }

    if (isset($_POST['add_entourage'])) {
        try {
            $pdo->beginTransaction();

            // Insert entourage
            $stmt = $pdo->prepare("INSERT INTO entourage (nameEntourage) VALUES (:name)");
            $stmt->execute([':name' => $_POST['entourage_name']]);
            $entourage_id = $pdo->lastInsertId();

            // Handle entourage images
            if (!empty($_FILES['entourage_images']['name'][0])) {
                foreach ($_FILES['entourage_images']['name'] as $key => $value) {
                    if ($_FILES['entourage_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            "name" => $_FILES['entourage_images']['name'][$key],
                            "type" => $_FILES['entourage_images']['type'][$key],
                            "tmp_name" => $_FILES['entourage_images']['tmp_name'][$key],
                            "error" => $_FILES['entourage_images']['error'][$key],
                            "size" => $_FILES['entourage_images']['size'][$key]
                        ];

                        $picture_location = handleFileUpload($file, 'entourage', $entourage_id);

                        // Insert picture record
                        $stmt = $pdo->prepare("
                            INSERT INTO picture (entourageID, pictureLocation) 
                            VALUES (:entourage_id, :location)
                        ");
                        $stmt->execute([
                            ':entourage_id' => $entourage_id,
                            ':location' => $picture_location
                        ]);
                    }
                }
            }

            $pdo->commit();
            $success_message = "Entourage added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }

    //EDIT / UPDATE ENTOURAGE
    if (isset($_POST['update_entourage'])) {
        try {
            $pdo->beginTransaction();

            $entourage_id = $_POST['entourage_id'];
            $new_name = $_POST['entourage_name'];

            // 1. Update the entourage name
            $stmt = $pdo->prepare("UPDATE entourage SET nameEntourage = :name WHERE entourageID = :id");
            $stmt->execute([':name' => $new_name, ':id' => $entourage_id]);

            // 2. Process images marked for removal
            $removed_images = json_decode($_POST['removed_images'], true);
            if (!empty($removed_images) && is_array($removed_images)) {
                foreach ($removed_images as $pic_id) {
                    // Retrieve the picture file path
                    $stmt = $pdo->prepare("SELECT pictureLocation FROM picture WHERE pictureID = :pic_id");
                    $stmt->execute([':pic_id' => $pic_id]);
                    $pic = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Remove the file from the file system, if it exists
                    if ($pic && file_exists($pic['pictureLocation'])) {
                        unlink($pic['pictureLocation']);
                    }

                    // Delete the record from the database
                    $stmt = $pdo->prepare("DELETE FROM picture WHERE pictureID = :pic_id");
                    $stmt->execute([':pic_id' => $pic_id]);
                }
            }

            // 3. Add new images (if any)
            // Count the existing images after deletion
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM picture WHERE entourageID = :id");
            $stmt->execute([':id' => $entourage_id]);
            $existing_count = $stmt->fetchColumn();

            $max_images = 5;
            $remaining_slots = $max_images - $existing_count;

            if (!empty($_FILES['entourage_images']['name'][0]) && $remaining_slots > 0) {
                foreach ($_FILES['entourage_images']['name'] as $key => $value) {
                    // Only process if we have available slots
                    if ($key < $remaining_slots && $_FILES['entourage_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            "name"     => $_FILES['entourage_images']['name'][$key],
                            "type"     => $_FILES['entourage_images']['type'][$key],
                            "tmp_name" => $_FILES['entourage_images']['tmp_name'][$key],
                            "error"    => $_FILES['entourage_images']['error'][$key],
                            "size"     => $_FILES['entourage_images']['size'][$key]
                        ];
                        // Use your existing file upload handler function
                        $picture_location = handleFileUpload($file, 'entourage', $entourage_id);

                        // Insert the new picture record
                        $stmt = $pdo->prepare("INSERT INTO picture (entourageID, pictureLocation) VALUES (:id, :location)");
                        $stmt->execute([':id' => $entourage_id, ':location' => $picture_location]);
                    }
                }
            }

            $pdo->commit();
            $success_message = "Entourage updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Update failed: " . $e->getMessage();
        }
    }
    if (isset($_POST['update_category'])) {
        try {
            $category_id = $_POST['category_id'];
            $category_name = $_POST['category_name'];
            $category_code = strtoupper($_POST['category_code']);
            $genderCat = $_POST['genderCat'];

            $stmt = $pdo->prepare("UPDATE productcategory 
                                  SET productCategory = :name, 
                                      categoryCode = :code, 
                                      genderCategory = :gender 
                                  WHERE categoryID = :id");
            $stmt->execute([
                ':name'   => $category_name,
                ':code'   => $category_code,
                ':gender' => $genderCat,
                ':id'     => $category_id
            ]);
            $success_message = "Category updated successfully!";
        } catch (Exception $e) {
            $error_message = "Error updating category: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variation'])) {
    try {
        $pdo->beginTransaction();
        
        // Prepare data with proper validation
        $variation_typeProduct = filter_input(INPUT_POST, 'variation_typeProduct', FILTER_SANITIZE_STRING) ?: '';
        $variation_nameProduct = filter_input(INPUT_POST, 'variation_nameProduct', FILTER_SANITIZE_STRING) ?: '';
        $variation_size = filter_input(INPUT_POST, 'variation_size', FILTER_SANITIZE_STRING) ?: '';
        $variation_price = filter_input(INPUT_POST, 'variation_price', FILTER_VALIDATE_FLOAT) ?: 0;
        $variation_scan_code = filter_input(INPUT_POST, 'variation_scan_code', FILTER_SANITIZE_STRING) ?: '';
        
        // Validate required fields
        if (empty($variation_typeProduct) || empty($variation_nameProduct)) {
            throw new Exception("Product type and name are required");
        }
        
        // Check if products with the same name already exist to copy details from
        // This query finds existing products with the same name in product_size_variations table
        $existingProductStmt = $pdo->prepare("
            SELECT p.* 
            FROM product p
            JOIN product_size_variations psv ON p.productID = psv.product_id
            WHERE psv.nameProduct = ?
            LIMIT 1
        ");
        $existingProductStmt->execute([$variation_nameProduct]);
        $existingProduct = $existingProductStmt->fetch(PDO::FETCH_ASSOC);
        
        // Default values if no existing product is found
        $entourageID = null;
        $categoryID = null;
        $colorProduct = '';
        $bustProduct = null;
        $waistProduct = null;
        $lengthProduct = null;
        $descProduct = null;
        $locationProduct = 'BACOLOD CITY';
        
        // If there's an existing product, copy its details
        if ($existingProduct) {
            // Copy these columns from existing product: entourageID, categoryID, colorProduct, 
            // bustProduct, waistProduct, lengthProduct, genderProduct, descProduct
            $entourageID = $existingProduct['entourageID'];
            $categoryID = $existingProduct['categoryID'];
            $colorProduct = $existingProduct['colorProduct'];
            $bustProduct = $existingProduct['bustProduct'];
            $waistProduct = $existingProduct['waistProduct'];
            $lengthProduct = $existingProduct['lengthProduct'];
            $descProduct = $existingProduct['descProduct'];
            $genderProduct = $existingProduct['genderProduct'];
            $locationProduct = $existingProduct['locationProduct'];
            
            error_log("Copying details from existing product ID: " . $existingProduct['productID']);
        } else {
            // No existing product with this name, get gender from category
            $genderStmt = $pdo->prepare("SELECT genderCategory FROM productcategory WHERE categoryCode = ?");
            $genderStmt->execute([$variation_typeProduct]);
            $genderData = $genderStmt->fetch(PDO::FETCH_ASSOC);
            $genderProduct = $genderData ? $genderData['genderCategory'] : 'ACCESSORIES';
        }
        
        // Get group ID from existing product or create a new one
        $groupStmt = $pdo->prepare("
            SELECT group_id FROM product_size_variations 
            WHERE nameProduct = ? 
            LIMIT 1
        ");
        $groupStmt->execute([$variation_nameProduct]);
        $groupData = $groupStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get or create group ID
        $groupId = $groupData ? $groupData['group_id'] : (
            $pdo->query("SELECT COALESCE(MAX(group_id), 0) + 1 AS new_group FROM product_size_variations")
                ->fetch(PDO::FETCH_ASSOC)['new_group']
        );
        
        // Insert the new product with copied details
        $productStmt = $pdo->prepare("
            INSERT INTO product (
                nameProduct, sizeProduct, priceProduct, codeProduct, locationProduct, 
                genderProduct, typeProduct, entourageID, categoryID, colorProduct,
                bustProduct, waistProduct, lengthProduct, descProduct
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $productStmt->execute([
            $variation_nameProduct, 
            $variation_size, 
            $variation_price, 
            $variation_scan_code, 
            $locationProduct,
            $genderProduct,
            $variation_typeProduct,
            $entourageID,
            $categoryID,
            $colorProduct,
            $bustProduct,
            $waistProduct,
            $lengthProduct,
            $descProduct
        ]);
        
        $product_id = $pdo->lastInsertId();
        
        // Add to variations table
        $variationStmt = $pdo->prepare("
            INSERT INTO product_size_variations (group_id, product_id, nameProduct)
            VALUES (?, ?, ?)
        ");
        $variationStmt->execute([$groupId, $product_id, $variation_nameProduct]);

        // If first product with this group_id had images, copy first image to new product
        if ($existingProduct) {
            $imageStmt = $pdo->prepare("
                SELECT pictureLocation FROM picture 
                WHERE productID = ? 
                ORDER BY pictureID ASC 
                LIMIT 1
            ");
            $imageStmt->execute([$existingProduct['productID']]);
            $imageData = $imageStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($imageData) {
                // Copy image to new product
                $copyImageStmt = $pdo->prepare("
                    INSERT INTO picture (productID, pictureLocation, dateAdded, isActive)
                    VALUES (?, ?, CURRENT_TIMESTAMP, 1)
                ");
                $copyImageStmt->execute([$product_id, $imageData['pictureLocation']]);
            }
        }

        $pdo->commit();
        $_SESSION['success_message'] = 'Variation added successfully!';
        
        header('Location: ../../../utilities_section.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Error adding variation: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header('Location: ../../../utilities_section.php');
        exit;
    }
}

