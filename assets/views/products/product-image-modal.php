<!-- Product Image Lightbox Modal View -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-labelledby="imageLightboxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageLightboxModalLabel">Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="lightbox-container">
                    <img id="lightboxImage" src="" alt="Product Image" class="img-fluid">
                    <div class="lightbox-nav">
                        <button id="prevImage" class="btn btn-light lightbox-nav-btn">❮</button>
                        <button id="nextImage" class="btn btn-light lightbox-nav-btn">❯</button>
                    </div>
                    <div id="imageCounter" class="image-counter">1 / 1</div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="image-thumbnails" id="imageThumbnails"></div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div> 