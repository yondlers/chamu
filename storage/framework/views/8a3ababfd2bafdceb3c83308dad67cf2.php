<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <?php echo e(__('Uploads')); ?>

        </h2>
     <?php $__env->endSlot(); ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        window.existingImages = [
                <?php
                    $count = 0;
                ?>
                <?php for($i = 1; $i <= 10; $i++): ?>
                <?php

                    $img = $listing->listingPictures[0]["image_$i"] ?? null;

                    if ($img)
                    {
                        $count++;
                    }

                ?>
                <?php if($img): ?>
            {
                id: 'server-<?php echo e($i); ?>',
                dataUrl: "<?php echo e(asset('storage/' . $img)); ?>",
                name: "<?php echo e($img); ?>",
                file: null
            },
            <?php endif; ?>
            <?php endfor; ?>
        ];
    </script>

    <body onload="updateImageGrid()" class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Property Images</h1>
            <p class="text-gray-600">Upload up to 10 images and arrange them in your preferred order</p>
        </div>

        <!-- Upload Area -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <div class="p-6">
                <div class="mb-6">
                    <label class="block text-lg font-semibold text-gray-700 mb-4">Upload Images</label>
                    <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p class="text-xl text-gray-600 mb-2">Drop images here or click to browse</p>
                        <p class="text-sm text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                        <input type="file" id="fileInput" multiple accept="image/*" class="hidden">
                    </div>
                </div>

                <!-- Image Counter -->
                <div class="flex justify-between items-center mb-6">
                        <span class="text-sm text-gray-600">
                            <span id="imageCount"><?php echo $count; ?></span> of 10 images uploaded
                        </span>
                    <button id="clearAll" class="text-red-600 hover:text-red-700 text-sm font-medium hidden">
                        Clear All Images
                    </button>
                </div>

                <!-- Image Grid -->
                <div id="imageGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
                    <!-- Images will be dynamically added here -->

                </div>

                <!-- Instructions -->
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                
                

                <!-- Form Data Preview -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Request Data Preview:</h3>
                    <pre id="requestPreview" class="text-xs text-gray-600 bg-white p-3 rounded border overflow-x-auto">
                        {
                            "image_1": "<?php echo e(isset($listing->listingPictures[0]->image_1) && $listing->listingPictures[0]->image_1 ? asset('storage/' . $listing->listingPictures[0]->image_1) : 'null'); ?>",
                            "image_2": "<?php echo e(isset($listing->listingPictures[0]->image_2) && $listing->listingPictures[0]->image_2 ? asset('storage/' . $listing->listingPictures[0]->image_2) : 'null'); ?>",
                            "image_3": "<?php echo e(isset($listing->listingPictures[0]->image_3) && $listing->listingPictures[0]->image_3 ? asset('storage/' . $listing->listingPictures[0]->image_3) : 'null'); ?>",
                            "image_4": "<?php echo e(isset($listing->listingPictures[0]->image_4) && $listing->listingPictures[0]->image_4 ? asset('storage/' . $listing->listingPictures[0]->image_4) : 'null'); ?>",
                            "image_5": "<?php echo e(isset($listing->listingPictures[0]->image_5) && $listing->listingPictures[0]->image_5 ? asset('storage/' . $listing->listingPictures[0]->image_5) : 'null'); ?>",
                            "image_6": "<?php echo e(isset($listing->listingPictures[0]->image_6) && $listing->listingPictures[0]->image_6 ? asset('storage/' . $listing->listingPictures[0]->image_6) : 'null'); ?>",
                            "image_7": "<?php echo e(isset($listing->listingPictures[0]->image_7) && $listing->listingPictures[0]->image_7 ? asset('storage/' . $listing->listingPictures[0]->image_7) : 'null'); ?>",
                            "image_8": "<?php echo e(isset($listing->listingPictures[0]->image_8) && $listing->listingPictures[0]->image_8 ? asset('storage/' . $listing->listingPictures[0]->image_8) : 'null'); ?>",
                            "image_9": "<?php echo e(isset($listing->listingPictures[0]->image_9) && $listing->listingPictures[0]->image_9 ? asset('storage/' . $listing->listingPictures[0]->image_9) : 'null'); ?>",
                            "image_10": "<?php echo e(isset($listing->listingPictures[0]->image_10) && $listing->listingPictures[0]->image_10 ? asset('storage/' . $listing->listingPictures[0]->image_10) : 'null'); ?>",
                        }
                        </pre>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">

                    <form action="<?php echo e(route('listing_pictures.uploadPost', ['listing_id' => $listing->id])); ?>" method="POST" id="formListing" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <button type="submit" id="saveImages" class="w-full block text-center card rounded-lg border bg-gray-800 dark:border-gray-700 text-white py-2">
                            Save Images
                        </button>
                    </form>


                </div>

            </div>
        </div>



    </div>

    <script>
        let uploadedImages = window.existingImages || [];
        const maxImages = 10;

        // DOM elements
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const imageGrid = document.getElementById('imageGrid');
        const imageCount = document.getElementById('imageCount');
        const clearAll = document.getElementById('clearAll');
        const saveImages = document.getElementById('saveImages');
        const previewListing = document.getElementById('previewListing');
        const requestPreview = document.getElementById('requestPreview');
        const successMessage = document.getElementById('successMessage');

        // Initialize Sortable for drag and drop reordering
        let sortable;

        // File input and drop zone events
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', handleDragOver);
        dropZone.addEventListener('drop', handleDrop);
        fileInput.addEventListener('change', handleFileSelect);
        clearAll.addEventListener('click', clearAllImages);
        saveImages.addEventListener('click', saveAllImages);
        previewListing.addEventListener('click', previewPropertyListing);



        function handleDragOver(e) {
            e.preventDefault();
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        }

        function handleDrop(e) {
            e.preventDefault();
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
            const files = Array.from(e.dataTransfer.files);
            processFiles(files);
        }

        function handleFileSelect(e) {
            const files = Array.from(e.target.files);
            processFiles(files);
        }

        function processFiles(files) {
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            const remainingSlots = maxImages - uploadedImages.length;
            const filesToProcess = imageFiles.slice(0, remainingSlots);

            filesToProcess.forEach(file => {
                if (file.size > 10 * 1024 * 1024) { // 10MB limit
                    alert(`${file.name} is too large. Please choose images under 10MB.`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    const imageData = {
                        id: Date.now() + Math.random(),
                        file: file,
                        dataUrl: e.target.result,
                        name: file.name
                    };
                    uploadedImages.push(imageData);
                    updateUI();
                };
                reader.readAsDataURL(file);
            });

            if (filesToProcess.length < imageFiles.length) {
                alert(`Only ${filesToProcess.length} images were added. Maximum ${maxImages} images allowed.`);
            }
        }

        function updateUI() {
            updateImageGrid();
            updateImageCount();
            updateRequestPreview();
            updateButtons();
        }

        function updateImageGrid() {
            imageGrid.innerHTML = '';

            // console.log(1,image);

            uploadedImages.forEach((image, index) => {
                const imageContainer = document.createElement('div');
                imageContainer.className = 'relative group cursor-move bg-white rounded-lg shadow-md overflow-hidden border-2 border-transparent hover:border-blue-300 transition-all';
                imageContainer.dataset.id = image.id;

                imageContainer.innerHTML = `
                            <div class="aspect-square relative">
                                <img src="${image.dataUrl}" alt="${image.name}" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all"></div>

                                <!-- Image number badge -->
                                <div class="absolute top-2 left-2 bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
                                    ${index + 1}
                                </div>

                                <!-- Main photo badge -->
                                ${index === 0 ? '<div class="absolute top-2 right-2 bg-green-600 text-white text-xs font-medium px-2 py-1 rounded">Main</div>' : ''}

                                <!-- Delete button -->
                                <button onclick="removeImage('${image.id}')" class="absolute top-2 right-2 ${index === 0 ? 'top-10' : ''} bg-red-600 hover:bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>

                                <!-- Drag handle -->
                                <div class="absolute bottom-2 right-2 bg-gray-800 bg-opacity-50 text-white rounded p-1 opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="p-2">
                                <p class="text-xs text-gray-600 truncate">${image.name}</p>
                            </div>
                        `;

                imageGrid.appendChild(imageContainer);
            });

            // Reinitialize sortable
            if (sortable) {
                sortable.destroy();
            }

            if (uploadedImages.length > 0) {
                sortable = Sortable.create(imageGrid, {
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onEnd: function(evt) {
                        const movedImage = uploadedImages.splice(evt.oldIndex, 1)[0];
                        uploadedImages.splice(evt.newIndex, 0, movedImage);
                        updateUI();
                    }
                });
            }
        }

        function updateImageCount() {
            imageCount.textContent = uploadedImages.length;
            clearAll.classList.toggle('hidden', uploadedImages.length === 0);
        }

        function updateRequestPreview() {
            const requestData = {
                // listing_id: "12345",
                // team_id: "67890"
            };

            // Add image data to request
            for (let i = 1; i <= maxImages; i++) {
                const imageIndex = i - 1;
                requestData[`image_${i}`] = uploadedImages[imageIndex] ? `[File: ${uploadedImages[imageIndex].name}]` : null;
            }

            requestPreview.textContent = JSON.stringify(requestData, null, 2);
        }

        function updateButtons() {
            saveImages.disabled = uploadedImages.length === 0;
        }

        function removeImage(imageId) {
            uploadedImages = uploadedImages.filter(img => img.id !== imageId);
            updateUI();
        }

        function clearAllImages() {
            if (confirm('Are you sure you want to remove all images?')) {
                uploadedImages = [];
                updateUI();
            }
        }

        function saveAllImages() {
            const formListing = document.getElementById('formListing');

            formListing.addEventListener('submit', (e) => {
                e.preventDefault();

                if (uploadedImages.length === 0) {
                    alert('Please upload at least one image before saving.');
                    return;
                }

                const formData = new FormData(formListing);

                uploadedImages.forEach((image, index) => {
                    formData.append(`image_${index + 1}`, image.file);
                });

                // Fill remaining slots with empty strings
                for (let i = uploadedImages.length + 1; i <= maxImages; i++) {
                    formData.append(`image_${i}`, '');
                }

                // Submit the form data via fetch
                fetch(formListing.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Response:', data);
                        window.location.href = data.redirect_url;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while saving images.');
                    });
            });

        }

        function previewPropertyListing() {
            if (uploadedImages.length === 0) {
                alert('Please upload at least one image to preview the listing.');
                return;
            }

            // Create a simple preview modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                        <div class="bg-white rounded-lg max-w-4xl w-full max-h-full overflow-y-auto">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-2xl font-bold text-gray-800">Property Preview</h2>
                                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    ${uploadedImages.map((image, index) => `
                                        <div class="relative">
                                            <img src="${image.dataUrl}" alt="Property image ${index + 1}" class="w-full h-48 object-cover rounded-lg">
                                            <div class="absolute top-2 left-2 bg-blue-600 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
                                                ${index + 1}
                                            </div>
                                            ${index === 0 ? '<div class="absolute top-2 right-2 bg-green-600 text-white text-xs font-medium px-2 py-1 rounded">Main</div>' : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
            document.body.appendChild(modal);
        }

        // Initialize UI
        updateUI();
    </script>





    <script>
        (function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'96afa940626b73e9',t:'MTc1NDQ5NTEyNC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();
    </script>
    </body>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>

<?php /**PATH /Users/slx/Code/chamu2/resources/views/listing_pictures/edit.blade.php ENDPATH**/ ?>