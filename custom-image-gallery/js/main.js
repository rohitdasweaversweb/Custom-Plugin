jQuery(document).ready(function (jQuery) {
    /////////////////************************************* */

    var imageUrls = [];
    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

    // Trigger the WordPress Media Uploader
    jQuery('#select-images-button').click(function (e) {
        e.preventDefault();
        var mediaUploader = wp.media({
            title: 'Select Images',
            button: {
                text: 'Add to Gallery'
            },
            multiple: true // Allow multiple image selection
        }).on('select', function () {
            var attachments = mediaUploader.state().get('selection').toArray();
            var invalidFiles = false;

            attachments.forEach(function (attachment) {
                var imageUrl = attachment.attributes.url;
                var fileType = attachment.attributes.mime;

                // Validate file type
                if (!allowedTypes.includes(fileType)) {
                    invalidFiles = true;
                    jQuery('#status-message').html('<p style="color:red;">Invalid file type. Only JPG, JPEG, and PNG files are allowed.</p>');
                    setTimeout(() => {
                        jQuery('#status-message').html('');
                    }, 5000);
                    return;
                }

                // Check if the image URL is already in the imageUrls array
                if (!imageUrls.includes(imageUrl)) {
                    imageUrls.push(imageUrl);
                    jQuery('#selected-images').append(
                        '<div class="image-container" style="position: relative; display: inline-block; margin: 5px;">' +
                        '<img src="' + imageUrl + '" width="100" style="display: block;" />' +
                        '<input type="text" class="img-title" name="img_tittle[]" placeholder="Image Title" required />' +
                        '<textarea class="img-description" name="img_des[]" placeholder="Image Description" rows="2" required style="width: 100%;"></textarea>' +
                        '<button class="remove-image" style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">&times;</button>' +
                        '</div>'
                    );
                }
            });

            if (!invalidFiles) {
                // Update the hidden field with image URLs
                jQuery('#image_urls').val(imageUrls.join('***'));
            }
        }).open();
    });

    // Handle image removal
    jQuery(document).on('click', '.remove-image', function () {
        var imageContainer = jQuery(this).closest('.image-container');
        var imageSrc = imageContainer.find('img').attr('src');

        // Remove the image from the array of image URLs
        imageUrls = imageUrls.filter(function (url) {
            return url !== imageSrc;
        });

        // Update the hidden input field with the new image URLs
        jQuery('#image_urls').val(imageUrls.join('***'));

        // Remove the image element from the page
        imageContainer.remove();
    });

    // Form submission via AJAX
    jQuery('#image-upload-form').submit(function (e) {
        e.preventDefault();

        // Validate if at least two images are selected
        if (imageUrls.length < 2) {
            jQuery('#status-message').html('<p style="color:red;">Please select at least two images.</p>');
            setTimeout(() => {
                jQuery('#status-message').html('');
            }, 2000);
            return;
        }

        var titles = jQuery('input[name="img_tittle[]"]').map(function () {
            return jQuery(this).val().trim();
        }).get();

        var descriptions = jQuery('textarea[name="img_des[]"]').map(function () {
            return jQuery(this).val().trim();
        }).get();

        var valid = true;

        // Check if all titles are provided
        for (var i = 0; i < titles.length; i++) {
            if (!titles[i]) {
                valid = false;
                jQuery('#status-message').html('<p style="color:red;">Title is required for each image.</p>');
                break;
            }
        }

        if (!valid) return;

        var formData = {
            action: 'my_image_upload_action',
            tittle: jQuery('#tittle').val(),
            description: jQuery('#description').val(),
            image_urls: imageUrls.join('***'), // Pass image URLs properly
            img_tittle: titles.join('***'),     // Store all titles
            img_des: descriptions.join('***')   // Store all descriptions
        };

        // AJAX request to handle the form submission
        jQuery.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: formData,
            success: function (response) {
                if (response.success) {
                    jQuery('#status-message').html('<p style="color:green;">' + response.message + '</p>');
                    jQuery('#image-upload-form')[0].reset(); // Reset the form on success
                    setTimeout(function () {
                        jQuery('#status-message').html(''); // Clear success message after 5 seconds
                    }, 5000);
                    jQuery('#selected-images').html(''); // Clear selected images
                    imageUrls = []; // Reset the imageUrls array
                } else {
                    jQuery('#status-message').html('<p style="color:red;">' + response.message + '</p>');
                }
            }
        });
    });


    //////////////////**********Form submission via AJAX END******************************* */ // 


    ///////Retrive the List of the images and text also////

    // Load the gallery images on page load


    let currentPage = 1;

    function loadGallery(page = 1) {
        currentPage = page;
        jQuery.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: { action: 'fetch_gallery_images', paged: page },
            success: function (response) {
                const tbody = jQuery('#gallery-table tbody');
                tbody.empty(); // Clear existing rows

                if (response.success && response.data.images && response.data.images.length > 0) {
                    response.data.images.forEach((item, index) => {
                        let imageHtml = item.file_names.map(url => `<img src="${url}" width="100" />`).join('');
                        const itemsPerPage = 2;
                        const displayIndex = (page - 1) * itemsPerPage + index + 1;

                        // Append each gallery item with a preview container
                        tbody.append(`
                        <tr>
                            <td>${displayIndex}</td>
                            <td>${item.tittle}</td>
                            <td>${item.description}</td>
                            <td>${imageHtml}</td>
                            <td>${item.image_tittle}</td>
                            <td>${item.image_des}</td>
                            <td>
                                <button class="edit-btn" 
                                        data-id="${item.id}" 
                                        data-title="${item.tittle}" 
                                        data-description="${item.description}" 
                                        data-image-url="${item.file_names.join('***')}" 
                                        data-image-titles="${item.image_tittle}" 
                                        data-image-descriptions="${item.image_des}">
                                    Edit
                                </button>
                                <button class="delete-btn" data-id="${item.id}">Delete</button>
                            </td>
                            <td>
                                <input type="hidden" class="gallery-id" value="${item.id}">
                                <span class="shortcode" data-gallery-id="${item.id}">[gallery_custom id="${item.id}"]</span>
                            </td>
                        </tr>
                    `);

                        // Render the gallery content for each gallery ID
                        renderGalleryFromShortcode(item.id);
                    });

                    // Generate pagination
                    generatePagination(response.data.pages, page);
                    localStorage.setItem('galleryData', JSON.stringify(response.data.images));
                } else if (response.success && (!response.data.images || response.data.images.length === 0)) {
                    // Display "No images in gallery" message if there are no images
                    tbody.append('<tr><td colspan="8">No images in gallery.</td></tr>');
                    jQuery('#pagination').hide();
                    localStorage.removeItem('galleryData');
                } else {
                    console.error('Failed to load gallery images.');
                    jQuery('#sts-message').html('<p style="color:red;">Failed to load gallery images.</p>');
                    setTimeout(function () {
                        jQuery('#sts-message').html('');
                    }, 3000);
                }
            },
            error: function () {
                console.error('Failed to load gallery images.');
                jQuery('#sts-message').html('<p style="color:red;">Failed to load gallery images.</p>');
                setTimeout(function () {
                    jQuery('#sts-message').html('');
                }, 3000);
            }
        });
    }



    function generatePagination(totalPages, currentPage) {
        const pagination = jQuery('#pagination');
        pagination.empty().show(); // Clear and show pagination

        for (let i = 1; i <= totalPages; i++) {
            pagination.append(`
            <button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}" style="${i === currentPage ? 'color: blue;' : ''}">
                ${i}
            </button>
        `);
        }

        // Add click event for pagination buttons
        jQuery('.page-btn').on('click', function () {
            const page = jQuery(this).data('page');
            loadGallery(page);
        });
    }


    function renderGalleryFromShortcode(galleryId) {
        const shortcode = `[gallery_custom id="${galleryId}"]`;

        // Use AJAX to fetch and render gallery content based on the provided ID
        jQuery.post(ajax_object.ajax_url, {
            action: 'image_shortcode',
            gallery_id: galleryId
        }, function (response) {
            if (response) {
                console.log(`its worked `);

                // jQuery(`#gallery-preview-${galleryId}`).html(response);
            } else {
                jQuery(`#gallery-preview-${galleryId}`).html('<p>No content found.</p>');
            }
        }).fail(function () {
            console.error('AJAX request failed for gallery ID:', galleryId);
        });
    }



    // Load the gallery initially
    loadGallery(currentPage);



    // jQuery('#load-more').on('click', function () {
    //     var button = jQuery(this);
    //     var galleryId = button.data('gallery-id');
    //     var offset = parseInt(button.data('offset'));
        
    //     // Get all images stored in the hidden div
    //     var allImages = JSON.parse(jQuery('#all-images-' + galleryId).text());
    //     var newImages = [];
        
    //     // Loop through all images and select the next batch of 5
    //     for (var i = offset; i < offset + 5; i++) {
    //         if (i < allImages.length) {
    //             newImages.push(allImages[i]);
    //         }
    //     }
        
    //     // Show loading spinner
    //     jQuery('#loading-spinner').show();
    
    //     // If there are new images to display
    //     if (newImages.length > 0) {
    //         newImages.forEach(function (image) {
    //             var fileName = image.file_name;
    //             var title = image.title;
    //             var description = image.description;
    
    //             jQuery('.masonry-grid').append(
    //                 '<div class="grid-item">' +
    //                 '<a data-fancybox="gallery" href="' + fileName + '" data-caption="' + title + ' - ' + description + '">' +
    //                 '<img src="' + fileName + '" alt="' + title + '">' +
    //                 '</a>' +
    //                 '</div>'
    //             );
    //         });
    
    //         // Update the offset for the next batch
    //         button.data('offset', offset + 5);
    
    //         // Hide the button if no more images are left
    //         if (offset + 5 >= allImages.length) {
    //             button.hide();
    //         }
    //     } else {
    //         jQuery('.masonry-grid').append('<p>No more images available.</p>');
    //     }
    
    //     // Hide loading spinner
    //     jQuery('#loading-spinner').hide();
    // });
    
    jQuery('#load-more').on('click', function () {
        var button = jQuery(this);
        var galleryId = button.data('gallery-id');
        var offset = parseInt(button.data('offset'));
        
        // Get all images stored in the hidden div
        var allImages = JSON.parse(jQuery('#all-images-' + galleryId).text());
        var newImages = [];
    
        // Loop through all images and select the next batch of 5
        for (var i = offset; i < offset + 5; i++) {
            if (i < allImages.length) {
                newImages.push(allImages[i]);
            }
        }
    
        // Show loading spinner
        // jQuery('#loading-spinner').show();
    
        // If there are new images to display
        if (newImages.length > 0) {
            newImages.forEach(function (image) {
                var fileName = image.file_name;
                var title = image.title;
                var description = image.description;
    
                jQuery('.masonry-grid').append(
                    '<div class="grid-item">' +
                    '<a data-fancybox="gallery" href="' + fileName + '" data-caption="' + title + ' - ' + description + '">' +
                    '<img src="' + fileName + '" alt="' + title + '">' +
                    '</a>' +
                    '</div>'
                );
            });
    
            // Update the offset for the next batch
            button.data('offset', offset + 5);
    
            // Hide the button if no more images are left
            if (offset + 5 >= allImages.length) {
                button.hide();
            }
        } else {
            jQuery('.masonry-grid').append('<p>No more images available.</p>');
        }
    
        // Hide loading spinner
        // jQuery('#loading-spinner').hide();
    });
    



    ////////********* */ Delete button from list  click handler*************///////////
    jQuery(document).on('click', '.delete-btn', function () {
        const id = jQuery(this).data('id');

        if (confirm('Are you sure you want to delete this image?')) {
            jQuery.ajax({
                type: 'POST',
                url: ajax_object.ajax_url,
                data: { action: 'delete_gallery_image', id: id },
                success: function (response) {
                    if (response.success) {
                        loadGallery(currentPage); // Reload the gallery images

                        // Display success message and clear it after 3 seconds
                        jQuery('#sts-message').html('<p style="color:green;">' + response.data.message + '</p>');
                        setTimeout(function () {
                            jQuery('#sts-message').html(''); // Clear the message after 3 seconds
                        }, 3000);
                    } else {
                        // Display error message for 3 seconds
                        jQuery('#sts-message').html('<p style="color:red;">' + response.data.message + '</p>');
                        setTimeout(function () {
                            jQuery('#sts-message').html(''); // Clear the message after 3 seconds
                        }, 3000);
                    }
                },
                error: function () {
                    // Handle error case
                    jQuery('#sts-message').html('<p style="color:red;">An error occurred while deleting the image.</p>');
                    setTimeout(function () {
                        jQuery('#sts-message').html(''); // Clear the message after 3 seconds
                    }, 3000);
                }
            });
        }
    });




    /////////////////////////////*********Editt buttonn & modal edit ****** */

    // Edit button click handler
    var mediaUploader;

    jQuery(document).on('click', '.edit-btn', function () {
        const id = jQuery(this).data('id');
        const title = jQuery(this).data('title');
        const description = jQuery(this).data('description');

        let imageUrls = jQuery(this).data('image-url').replace(/\*\*\*/g, ',').split(',');
        let imageTitles = jQuery(this).data('image-titles') ? jQuery(this).data('image-titles').split(',') : [];
        let imageDescriptions = jQuery(this).data('image-descriptions') ? jQuery(this).data('image-descriptions').split(',') : [];

        // Populate modal fields
        jQuery('#edit-id').val(id);
        jQuery('#edit-title').val(title);
        jQuery('#edit-description').val(description);
        jQuery('#edit-image-url').val(imageUrls.join(','));

        // Show the images in the modal
        const imagePreviewContainer = jQuery('#edit-image-preview');
        imagePreviewContainer.empty();

        imageUrls.forEach(function (url, index) {
            const imgElement = `
            <div class="img-wrapper" style="position:relative; margin: 5px;">
                <img src="${url}" alt="Image Preview" class="img-fluid" style="max-height: 75px; max-width: 100px; padding: 5px;">
                <button type="button" class="remove-image" data-url="${url}" style="position:absolute; top:0; right:0;">X</button>
                
                <div class="form-group">
                    <label for="existing-image-title-${index}">Existing Image Title</label>
                    <input type="text" id="existing-image-title-${index}" name="existing-image-title[]" class="form-control" placeholder="Existing Image Title" value="${imageTitles[index] || ''}" required>
                </div>
    
                <div class="form-group">
                    <label for="existing-image-description-${index}">Existing Image Description</label>
                    <textarea id="existing-image-description-${index}" name="existing-image-description[]" class="form-control" placeholder="Existing Image Description" required>${imageDescriptions[index] || ''}</textarea>
                </div>
            </div>`;
            imagePreviewContainer.append(imgElement);
        });

        imagePreviewContainer.sortable({
            items: '.img-wrapper',
            update: function () {
                const sortedImageUrls = imagePreviewContainer.children('.img-wrapper').map(function () {
                    return jQuery(this).find('img').attr('src');
                }).get();
                jQuery('#edit-image-url').val(sortedImageUrls.join(','));
            }
        });

        jQuery('#editModal').modal('show');
    });

    jQuery('#select-images').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Images',
            button: {
                text: 'Use these images'
            },
            multiple: true
        });

        mediaUploader.on('select', function () {
            const attachments = mediaUploader.state().get('selection').toJSON();
            const existingImages = jQuery('#edit-image-url').val() ? jQuery('#edit-image-url').val().split(',') : [];

            attachments.forEach(function (attachment) {
                const imageUrl = attachment.url;
                const imgElement = `
                <div class="img-wrapper" style="position:relative; margin: 5px;">
                    <img src="${imageUrl}" alt="Image Preview" class="img-fluid" style="max-height: 75px; max-width: 100px; padding: 5px;">
                    <button type="button" class="remove-image" data-url="${imageUrl}" style="position:absolute; top:0; right:0;">X</button>
                    
                    <div class="form-group">
                        <label for="new-image-title-new">New Image Title</label>
                        <input type="text" id="new-image-title-new" name="new-image-title[]" class="form-control" placeholder="New Image Title" required>
                    </div>
    
                    <div class="form-group">
                        <label for="new-image-description-new">New Image Description</label>
                        <textarea id="new-image-description-new" name="new-image-description[]" class="form-control" placeholder="New Image Description" required></textarea>
                    </div>
                </div>`;

                jQuery('#edit-image-preview').append(imgElement);

                if (!existingImages.includes(imageUrl)) {
                    existingImages.push(imageUrl);
                }
            });

            jQuery('#edit-image-url').val(existingImages.join(','));
        });

        mediaUploader.open();
    });

    jQuery(document).on('click', '.remove-image', function () {
        const imageUrlToRemove = jQuery(this).data('url');
        jQuery(this).closest('.img-wrapper').remove();

        let existingImages = jQuery('#edit-image-url').val().split(',');
        const updatedImages = existingImages.filter(url => url !== imageUrlToRemove);
        jQuery('#edit-image-url').val(updatedImages.join(','));

        jQuery('#edit-status-message').html('<p style="color:green;">Image removed from preview.</p>');
        setTimeout(function () {
            jQuery('#edit-status-message').html('');
        }, 3000);
    });

    jQuery('#edit-form').on('submit', function (e) {
        e.preventDefault();

        jQuery('#edit-status-message').html('');

        const galleryItemId = jQuery('#edit-id').val();
        const title = jQuery('#edit-title').val();
        const description = jQuery('#edit-description').val();
        const imageUrls = jQuery('#edit-image-url').val().split(',');

        if (imageUrls.length < 2) {
            jQuery('#edit-status-message').html('<p style="color:red;">You must upload 2 images.</p>');

            setTimeout(function () {
                jQuery('#edit-status-message').html(''); // Clear the message after 3 seconds
            }, 3000);
            return;
        }

        const allowedTypes = ['jpg', 'jpeg', 'png'];
        const invalidUrls = imageUrls.filter(url => {
            const extension = url.split('.').pop().toLowerCase();
            return !allowedTypes.includes(extension);
        });

        if (invalidUrls.length > 0) {
            jQuery('#edit-status-message').html('<p style="color:red;">Invalid file types: ' + invalidUrls.join(', ') + '. Only JPG, JPEG, and PNG are allowed.</p>');

            setTimeout(function () {
                jQuery('#edit-status-message').html(''); // Clear the message after 3 seconds
            }, 3000);
            return;
        }

        const existingTitles = jQuery('input[name="existing-image-title[]"]').map(function () {
            return jQuery(this).val();
        }).get();

        const existingDescriptions = jQuery('textarea[name="existing-image-description[]"]').map(function () {
            return jQuery(this).val();
        }).get();

        const newTitles = jQuery('input[name="new-image-title[]"]').map(function () {
            return jQuery(this).val();
        }).get();

        const newDescriptions = jQuery('textarea[name="new-image-description[]"]').map(function () {
            return jQuery(this).val();
        }).get();

        const formData = new FormData(this);
        formData.append('gallery_item_id', galleryItemId);
        formData.append('image_url', imageUrls.join(','));
        formData.append('title', title);
        formData.append('description', description);
        formData.append('existing_image_title', existingTitles.join(','));
        formData.append('existing_image_description', existingDescriptions.join(','));
        formData.append('new_image_title', newTitles.join(','));
        formData.append('new_image_description', newDescriptions.join(','));
        formData.append('action', 'edit_gallery_image');

        jQuery.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                // console.log(response,`*****************`);
                // console.log(response); // Check the response structure in console

                // Ensure we have a valid response object with success and data properties
                if (response && response.success === true && response.data && response.data.message) {
                    // Update localStorage on successful update
                    updateLocalStorage(galleryItemId, imageUrls);
                    loadGallery(currentPage);
                    jQuery('#edit-status-message').html('<p style="color:green;">' + response.data.message + '</p>');

                    setTimeout(function () {
                        jQuery('#edit-status-message').html(''); // Clear the message after 3 seconds
                    }, 3000);
                } else {
                    jQuery('#edit-status-message').html('<p style="color:red;">' + response.data.message + '</p>');
                    setTimeout(function () {
                        jQuery('#edit-status-message').html(''); // Clear the message after 3 seconds
                    }, 3000);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                jQuery('#edit-status-message').html('<p style="color:red;">An error occurred while updating the gallery.</p>');
                setTimeout(function () {
                    jQuery('#edit-status-message').html(''); // Clear the message after 3 seconds
                }, 3000);
            }
        });
    });


    // Function to update localStorage
    function updateLocalStorage(galleryItemId, updatedImagesArray) {
        let galleryData = JSON.parse(localStorage.getItem('galleryData')) || [];
        const itemIndex = galleryData.findIndex(item => item.id === parseInt(galleryItemId));

        if (itemIndex > -1) {
            galleryData[itemIndex].file_names = updatedImagesArray; // Update with remaining images
            localStorage.setItem('galleryData', JSON.stringify(galleryData));
        }
    }

    // Close modal and handle focus
    jQuery('#close-modal').on('click', function () {
        document.activeElement.blur(); // Remove focus from active element
        jQuery('#editModal').modal('hide');
        jQuery('#edit-status-message').html(''); // Clear status message
    });

    jQuery('#editModal').on('shown.bs.modal', function () {
        jQuery('#edit-title').focus(); // Focus on the first field
        jQuery(this).removeAttr('inert'); // Enable interaction with modal

        // Ensure the modal can scroll if content exceeds height
        const modalBody = jQuery(this).find('.modal-body');
        if (modalBody[0].scrollHeight > modalBody.innerHeight()) {
            modalBody.css('overflow-y', 'auto'); // Enable scrolling
        }
    });

    jQuery('#editModal').on('hidden.bs.modal', function () {
        jQuery('#open-modal-btn').focus(); // Focus back to trigger button
        jQuery(this).attr('inert', '');
    });



    ///////////////////*********Editt buttonn & modal edit ****** *////////////////////////////////////

    ////////////////////**********Shortcode Section************************* */



});

