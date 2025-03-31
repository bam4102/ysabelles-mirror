<?php

/**
 * Product edit form view
 * @var object $product The product being edited
 * @var array $categories Available product categories
 * @var string $variationsHtml HTML for size variations if any
 */
?>
<link href="assets/css/products/product-edit.css" rel="stylesheet">

<form id="editProductForm" class="needs-validation" novalidate>
    <div class="row">
        <!-- Name -->
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productName" class="form-label">Name</label>
                <input type="text" class="form-control" id="productName" name="nameProduct"
                    value="<?= htmlspecialchars($product->nameProduct ?? '') ?>" required>
            </div>
        </div>
        <!-- Type -->
        <div class="col-md-3">

            <div class="mb-3">
                <label for="productGender" class="form-label">Type</label>
                <select class="form-select" id="productGender" name="genderProduct" required>
                    <option value="">Select Type</option>
                    <?php
                    $genderTypes = [
                        'MENSWEAR' => 'MENSWEAR',
                        'WOMENSWEAR' => 'WOMENSWEAR',
                        'BOYS' => 'BOYS',
                        'GIRLS' => 'GIRLS',
                        'WEDDING' => 'WEDDING',
                        'ACCESSORIES' => 'ACCESSORIES'
                    ];
                    foreach ($genderTypes as $value => $label):
                    ?>
                        <option value="<?= $value ?>" <?= ($product->genderProduct === $value) ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <!-- Category -->
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productCategory" class="form-label">Category</label>
                <select class="form-select" id="productCategory" name="categoryID" required>
                    <option value="">Select Category</option>
                    <?php
                    if (!empty($categories)):
                        foreach ($categories as $cat):
                            if (!$product->genderProduct || $cat['genderCategory'] === $product->genderProduct):
                    ?>
                                <option value="<?= htmlspecialchars($cat['categoryID']) ?>"
                                    <?= ($cat['categoryID'] == $product->categoryID) ? 'selected' : '' ?>
                                    data-code="<?= htmlspecialchars($cat['categoryCode']) ?>">
                                    <?= htmlspecialchars($cat['productCategory']) ?> (<?= htmlspecialchars($cat['categoryCode']) ?>)
                                </option>
                    <?php
                            endif;
                        endforeach;
                    endif;
                    ?>
                </select>
                <input type="hidden" name="typeProduct" value="<?= htmlspecialchars($product->typeProduct ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productLocation" class="form-label">Location</label>
                <select class="form-select" id="productLocation" name="locationProduct" required>
                    <option value="">Select Location</option>
                    <?php
                    $locations = [
                        'BACOLOD CITY',
                        'DUMAGUETE CITY',
                        'ILOILO CITY',
                        'SAN CARLOS CITY',
                        'CEBU CITY'
                    ];
                    foreach ($locations as $location):
                    ?>
                        <option value="<?= $location ?>" <?= ($product->locationProduct === $location) ? 'selected' : '' ?>>
                            <?= $location ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productSize" class="form-label">Size</label>
                <input type="text" class="form-control" id="productSize" name="sizeProduct"
                    value="<?= htmlspecialchars($product->sizeProduct ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="bustProduct" class="form-label">Bust</label>
                <input type="text" class="form-control" id="bustProduct" name="bustProduct"
                    value="<?= htmlspecialchars($product->bustProduct ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="waistProduct" class="form-label">Waist</label>
                <input type="text" class="form-control" id="waistProduct" name="waistProduct"
                    value="<?= htmlspecialchars($product->waistProduct ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="lengthProduct" class="form-label">Length</label>
                <input type="text" class="form-control" id="lengthProduct" name="lengthProduct"
                    value="<?= htmlspecialchars($product->lengthProduct ?? '') ?>">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="statusDamaged" name="damageProduct" value="1" <?= $product->damageProduct ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusDamaged">
                        Damaged
                    </label>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="statusSold" name="soldProduct" value="1" <?= $product->soldProduct ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusSold">
                        Sold
                    </label>
                </div>
            </div>

        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="statusReturned" name="returnedProduct" value="1" <?= $product->returnedProduct ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusReturned">
                        Released
                    </label>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="statusNew" name="isNew" value="1" <?= $product->isNew ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusNew">
                        New
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productCode" class="form-label">Code</label>
                <input type="text" class="form-control" id="productCode" name="codeProduct"
                    value="<?= htmlspecialchars($product->codeProduct ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productPrice" class="form-label">Price</label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="productPrice" name="priceProduct"
                        value="<?= htmlspecialchars($product->priceProduct ?? '') ?>" step="0.01" min="0">
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productColor" class="form-label">Color</label>
                <input type="text" class="form-control" id="productColor" name="colorProduct"
                    value="<?= htmlspecialchars($product->colorProduct ?? '') ?>">
            </div>
        </div>
        <div class="col-md-3">
            <div class="mb-3">
                <label for="productDescription" class="form-label">Description</label>
                <textarea class="form-control" id="productDescription" name="descProduct" rows="3"><?= htmlspecialchars($product->descProduct ?? '') ?></textarea>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <?= $variationsHtml ?>
        <div class="row mt-3">
            <div class="col-md-12 mb-3">
                <div class="product-image-container">
                    <div class="current-images mb-3">
                        <h6>Current Photos (<?= count($product->images) ?>/5)</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($product->images as $image): ?>
                                <div class="position-relative product-image-wrapper">
                                    <img src="<?= htmlspecialchars($image->pictureLocation) ?>"
                                        alt="<?= htmlspecialchars($product->nameProduct ?? 'Product') ?>"
                                        class="img-thumbnail product-image-detail"
                                        data-original-url="<?= htmlspecialchars($image->pictureLocation) ?>"
                                        style="width: 100px; height: 100px; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 delete-image"
                                        data-image-id="<?= htmlspecialchars($image->pictureID) ?>"
                                        <?= $image->isPrimary ? 'disabled' : '' ?>>
                                        <i class="bi bi-x"></i>
                                    </button>
                                    <?php if ($image->isPrimary): ?>
                                        <span class="badge bg-primary position-absolute bottom-0 start-0 m-1">Primary</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if (count($product->images) < 5): ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <div class="flex-grow-1">
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="newImages" name="newImages[]"
                                            accept="image/jpeg, image/png, image/gif, image/webp" multiple
                                            style="display: none;" data-max-files="<?= 5 - count($product->images) ?>">
                                        <button type="button" class="btn btn-danger" id="addImageBtn">
                                            <i class="bi bi-plus-lg"></i> Add Photo (<?= 5 - count($product->images) ?> remaining)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted d-block mb-2">
                                Upload up to <?= 5 - count($product->images) ?> more photos. Supported formats: JPEG, PNG, GIF, WebP. Max size: 5MB each.
                                <br>You can also drag and drop images directly onto the drop zone below.
                            </small>
                            <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Maximum number of images (5) reached. Delete an existing image before adding a new one.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <input type="hidden" name="productID" value="<?= htmlspecialchars($product->productID) ?>">
    </div>
</form>