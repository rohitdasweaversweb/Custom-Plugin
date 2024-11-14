<?php
/**
 * Plugin Name: Custom Image Gallery
 * Description: This is a custom Image gallery
 * Version: 1.0
 * Author:Rohit
 * 
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

function my_plugin_activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_gallery'; // Use prefix for the table name
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        file_names text NOT NULL,
        tittle varchar(255) NOT NULL,
        description text NOT NULL,
        img_tittle varchar(255) NOT NULL, 
        img_des text NOT NULL,            
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'my_plugin_activate');


function my_plugin_deactivate()
{
    global $wpdb, $table_prefix;
    $wp_gallery = $table_prefix . 'image_gallery';

    // Truncate the database table
    $q = "TRUNCATE `$wp_gallery`";
    $wpdb->query($q);

    // Define the custom folder path where images are stored
    $upload_folder = plugin_dir_path(__FILE__) . 'upload_img/';

    // Check if the folder exists
    if (is_dir($upload_folder)) {
        // Get all files in the folder
        $files = glob($upload_folder . '*'); // Get all files within the folder

        // Loop through and delete each file
        foreach ($files as $file) {
            if (is_file($file)) {
                // Delete the file
                unlink($file);
            }
        }
    }
}
register_deactivation_hook(__FILE__, 'my_plugin_deactivate');



function gallery_shorctcode()
{
    return "gallery fun";
}
add_shortcode('cust-img-gallery-12', 'gallery_shorctcode');


// $path=plugins_url('js/main.js',__FILE__);
// $dep=array()
// wp_enqueue_script('custom-js',);

function gallery_enqueue_scripts()
{
    // Enqueue jQuery (automatically provided by WordPress)
    wp_enqueue_script('jquery');

    // Enqueue jQuery UI and its CSS
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

    // Enqueue WordPress media uploader
    wp_enqueue_media();

    // CSS files
    wp_enqueue_style('custom', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.0', 'all');
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
   
    wp_enqueue_style('fancybox', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css');
    
    // JavaScript files
    wp_enqueue_script('main', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery', 'jquery-ui-sortable'), '1.0', true);
    wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'), null, true);

    wp_enqueue_script('masonry-js', 'https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js', array('jquery'), null, true);
   
    wp_enqueue_script('fancy-js', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js', array('jquery'), null, true);


    // Localize the script to include admin-ajax.php URL for AJAX calls
    wp_localize_script('main', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'edit_gallery_nonce' => wp_create_nonce('edit_gallery_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'gallery_enqueue_scripts');
add_action('admin_enqueue_scripts', 'gallery_enqueue_scripts');




////////////Menu- creation/////////////
function img_gallery_menu()
{
    add_menu_page('Custom Gallery', 'Custom Gallery', 'manage_options', 'custom-gallery-page', 'custom_gallery_page_func', 'dashicons-format-gallery', 6);

    add_submenu_page('custom-gallery-page', 'All Gallery', 'All Gallery', 'manage_options', 'custom-gallery-page', 'custom_gallery_page_func');

    add_submenu_page('custom-gallery-page', 'Add New', 'Add New', 'manage_options', 'add-new-page', 'add_new_page_func');
}
add_action('admin_menu', 'img_gallery_menu');




////adding file for function///////
function custom_gallery_page_func()
{
    include plugin_dir_path(__FILE__) . 'admin/main-page.php';
}

function add_new_page_func()
{
    include plugin_dir_path(__FILE__) . 'admin/add-new-image.php';
}



/////////////////*****Submit form *********////////// */
function handle_custom_image_upload_ajax()
{
    global $wpdb, $table_prefix;

    $wp_gallery = $table_prefix . 'image_gallery';
    $allowed_types = array('jpeg', 'jpg', 'png');

    // Check if image URLs are passed
    if (isset($_POST['image_urls']) && !empty($_POST['image_urls'])) {
        $image_urls = explode('***', sanitize_text_field($_POST['image_urls']));
        $titles = explode('***', sanitize_text_field($_POST['img_tittle']));
        $descriptions = explode('***', sanitize_text_field($_POST['img_des']));

        // Validate if at least 2 images are uploaded
        if (count($image_urls) < 2) {
            wp_send_json(array('success' => false, 'message' => 'Please upload at least two images & max 10.'));
        }

        $valid_images = array();
        foreach ($image_urls as $image_url) {
            // Get the file extension to validate the type
            $file_extension = pathinfo($image_url, PATHINFO_EXTENSION);

            // Validate file type
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                wp_send_json(array('success' => false, 'message' => 'Invalid file type detected. Only JPG, JPEG, and PNG are allowed.'));
            }

            $valid_images[] = $image_url;
        }

        if (!empty($valid_images)) {
            // Save images as a comma-separated string
            $image_urls_custom_format = implode(',', $valid_images);

            // Insert into the database
            $inserted = $wpdb->insert($wp_gallery, array(
                'file_names' => $image_urls_custom_format,
                'tittle' => sanitize_text_field($_POST['tittle']),           // Save gallery title
                'description' => sanitize_text_field($_POST['description']),   // Save gallery description
                'img_tittle' => implode(',', array_map('sanitize_text_field', $titles)), // Save image titles
                'img_des' => implode(',', array_map('sanitize_text_field', $descriptions))  // Save image descriptions
            ));

            if ($inserted) {
                wp_send_json(array('success' => true, 'message' => 'Images uploaded successfully!'));
            } else {
                wp_send_json(array('success' => false, 'message' => 'Database insert error.'));
            }
        } else {
            wp_send_json(array('success' => false, 'message' => 'No valid images found.'));
        }
    } else {
        wp_send_json(array('success' => false, 'message' => 'No image URLs provided.'));
    }

    wp_die();
}

add_action('wp_ajax_my_image_upload_action', 'handle_custom_image_upload_ajax');
add_action('wp_ajax_nopriv_my_image_upload_action', 'handle_custom_image_upload_ajax');

/////////////////*****Submit form *********////////// */

/********************************************* */


//////////////////////retive the data of gallery images///////////////
function fetch_gallery_images() {
    global $wpdb, $table_prefix;
    $wp_gallery = $table_prefix . 'image_gallery';

    // Get pagination parameters
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $limit = 2; // Number of items per page
    $offset = ($paged - 1) * $limit;

    // Fetch data from the database with LIMIT and OFFSET
    $results = $wpdb->get_results("SELECT * FROM $wp_gallery LIMIT $limit OFFSET $offset", ARRAY_A);
    $total_results = $wpdb->get_var("SELECT COUNT(*) FROM $wp_gallery");

    $gallery_images = array();

    if (!empty($results)) {
        foreach ($results as $result) {
            $image_file = $result['file_names'];
            $image_array = explode(',', $image_file);
            $image_array = array_map('trim', $image_array);

            $gallery_images[] = array(
                'id' => $result['id'],
                'tittle' => $result['tittle'],
                'description' => $result['description'],
                'image_tittle' => $result['img_tittle'],
                'image_des' => $result['img_des'],
                'file_names' => $image_array
            );
        }

        // Send the data back with total count for pagination
        wp_send_json_success(array(
            'images' => $gallery_images,
            'total' => $total_results,
            'pages' => ceil($total_results / $limit)
        ));
    } else {
        wp_send_json_success(array('message' => 'No images in gallery.'));
    }

    wp_die();
}

add_action('wp_ajax_fetch_gallery_images', 'fetch_gallery_images');
add_action('wp_ajax_nopriv_fetch_gallery_images', 'fetch_gallery_images');



// Delete gallery image
function delete_gallery_image()
{
    global $wpdb;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id > 0) {
        $table_name = $wpdb->prefix . 'image_gallery'; // Your custom table name
        $delete_result = $wpdb->delete($table_name, ['id' => $id]);

        if ($delete_result) {
            // Return a proper success message
            wp_send_json_success(['message' => 'Image successfully deleted.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete the image.']);
        }
    } else {
        wp_send_json_error(['message' => 'Invalid image ID.']);
    }

    wp_die();
}
add_action('wp_ajax_delete_gallery_image', 'delete_gallery_image');
add_action('wp_ajax_nopriv_delete_gallery_image', 'delete_gallery_image');




// ///////********Edit gallery image***********/////////////////

add_action('wp_ajax_edit_gallery_image', 'edit_gallery_image');
add_action('wp_ajax_nopriv_edit_gallery_image', 'edit_gallery_image');

function edit_gallery_image() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_gallery'; // Verify table name

    // Check for required fields
    if (!isset($_POST['gallery_item_id']) || !isset($_POST['image_url'])) {
        wp_send_json_error(['message' => 'Required fields are missing.']);
        return;
    }

    // Sanitize inputs
    $gallery_item_id = intval($_POST['gallery_item_id']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $image_urls = explode(',', sanitize_text_field($_POST['image_url']));
    $existing_titles = isset($_POST['existing_image_title']) ? array_map('sanitize_text_field', explode(',', $_POST['existing_image_title'])) : [];
    $existing_descriptions = isset($_POST['existing_image_description']) ? array_map('sanitize_textarea_field', explode(',', $_POST['existing_image_description'])) : [];
    $new_titles = isset($_POST['new_image_title']) ? array_map('sanitize_text_field', explode(',', $_POST['new_image_title'])) : [];
    $new_descriptions = isset($_POST['new_image_description']) ? array_map('sanitize_textarea_field', explode(',', $_POST['new_image_description'])) : [];

    // Validate image URLs
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $invalid_urls = [];
    
    foreach ($image_urls as $url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_types)) {
            $invalid_urls[] = $url;
        }
    }

    if (!empty($invalid_urls)) {
        wp_send_json_error(['message' => 'Invalid file types: ' . implode(', ', $invalid_urls) . '. Only JPG, JPEG, and PNG are allowed.']);
        return;
    }

    // Prepare values for update
    $image_titles_custom_format = implode(',', array_merge($existing_titles, $new_titles));
    $image_descriptions_custom_format = implode(',', array_merge($existing_descriptions, $new_descriptions));

    // Construct the raw SQL query
    $query = "
        UPDATE $table_name 
        SET file_names = '" . esc_sql(implode(',', $image_urls)) . "',
            tittle = '" . esc_sql($title) . "',
            description = '" . esc_sql($description) . "',
            img_tittle = '" . esc_sql($image_titles_custom_format) . "',
            img_des = '" . esc_sql($image_descriptions_custom_format) . "' 
        WHERE id = " . intval($gallery_item_id);

        // var_dump($query);
    // Execute the raw query
    $result = $wpdb->query($query);

    // Check the result of the query
    if ($result === false) {
        wp_send_json_error(['message' => 'Error updating the gallery in the database.']);
    } elseif ($result === 0) {
        wp_send_json_error(['message' => 'No changes made. Check if the ID exists or if the data is the same.']);
    } else {
        wp_send_json_success(['message' => 'Gallery item updated successfully.']);
    }
}



////////////////////**********Shortcode Section************************* */////////////////////////


// Function to get gallery images from the database
function get_gallery_images($gallery_id) {
    global $wpdb, $table_prefix;

    // Define your table name
    $wp_gallery = $table_prefix . 'image_gallery';

    // Prepare the query to fetch images for the specified gallery ID
    $query = $wpdb->prepare("SELECT * FROM $wp_gallery WHERE id = %d", $gallery_id);

    // Execute the query and fetch results
    $images = $wpdb->get_results($query, ARRAY_A);

    return $images; // Return the array of images
}

/******************** */

// function fetch_gallery_images_by_id($atts) {
//     $atts = shortcode_atts(array(
//         'id' => '0',
//     ), $atts);

//     $gallery_id = intval($atts['id']);

//     $loading_gif = plugin_dir_url(__FILE__) . 'image/loader.svg'; 


//     // Check if the gallery ID is valid
//     if ($gallery_id <= 0) {
//         return '<p>Invalid gallery ID.</p>';
//     }

//     $images = get_gallery_images($gallery_id);

//     // Initialize total_images count
//     $total_images = 0;

//     // Count total images
//     foreach ($images as $image) {
//         $file_names = explode(',', $image['file_names']);
//         $total_images += count($file_names); // Count each image
//     }

//     if ($total_images === 0) {
//         return '<p>No images found for this gallery.</p>';
//     }

//     $output = '<div class="masonry-container"><div class="masonry-grid">';

//     // Show only the first 5 images initially
//     $images_shown = 0;
//     $visible_images = [];
//     foreach ($images as $image) {
//         $file_names = explode(',', $image['file_names']);
//         $img_titles = explode(',', $image['img_tittle']);
//         $img_descriptions = explode(',', $image['img_des']);

//         foreach ($file_names as $index => $file_name) {
//             if ($images_shown < 5) {
//                 $title = isset($img_titles[$index]) ? $img_titles[$index] : '';
//                 $description = isset($img_descriptions[$index]) ? $img_descriptions[$index] : '';
//                 $output .= '<div class="grid-item">
//                 <a data-fancybox="gallery" href="' . esc_url($file_name) . '"  
//                    data-caption="' . esc_html($title) . ' - ' . esc_html($description) . '">
//                     <img src="' . esc_url($file_name) . '" alt="' . esc_attr($title) . '">
//                 </a>
//             </div>';
            
//                 $images_shown++;
//             }
//             // Prepare all image data for JavaScript
//             $visible_images[] = [
//                 'file_name' => esc_url($file_name),
//                 'title' => esc_html($title),
//                 'description' => esc_html($description)
//             ];
//         }
//     }

//     $output .= '</div>'; // Close masonry-grid

//     // Add Load More button if there are more than 5 images
//     if ($total_images > 5) {
//         $output .= '<button id="load-more" data-gallery-id="' . esc_attr($gallery_id) . '" data-offset="5">Load More</button>';
//     }

//     $output .= '<div id="loading-spinner" style="display: none; text-align: center;">
//     <img src="' . esc_url($loading_gif) . '" alt="Loading..." />
// </div>';

//     // Hidden div to store all images
  
//     $output .= '<div id="all-images-' . esc_attr($gallery_id) . '" style="display: none;">' . 
//               htmlspecialchars(json_encode($visible_images), ENT_QUOTES, 'UTF-8') . 
//            '</div>';


//     $output .= '</div>'; // Close masonry-container
//     return $output;
// }
// add_shortcode('gallery_custom', 'fetch_gallery_images_by_id');




function fetch_gallery_images_by_id($atts) {
    $atts = shortcode_atts(array(
        'id' => '0',
    ), $atts);

    $gallery_id = intval($atts['id']);
    $loading_gif = plugin_dir_url(__FILE__) . 'image/loader.svg'; 

    // Check if the gallery ID is valid
    if ($gallery_id <= 0) {
        return '<p>Invalid gallery ID.</p>';
    }

    $images = get_gallery_images($gallery_id);
    $total_images = 0;

    // Initialize the array to hold visible images
    $visible_images = [];

    foreach ($images as $image) {
        $file_names = explode(',', $image['file_names']);
        $img_titles = explode(',', $image['img_tittle']);
        $img_descriptions = explode(',', $image['img_des']);

        foreach ($file_names as $index => $file_name) {
            $title = isset($img_titles[$index]) ? $img_titles[$index] : '';
            $description = isset($img_descriptions[$index]) ? $img_descriptions[$index] : '';

            // Prepare each image's data for JavaScript
            $visible_images[] = [
                'file_name' => esc_url($file_name),
                'title' => esc_html($title),
                'description' => esc_html($description)
            ];
        }
    }

    if (empty($visible_images)) {
        return '<p>No images found for this gallery.</p>';
    }

    $output = '<div class="masonry-container"><div class="masonry-grid">';

    // Show only the first 5 images initially
    $images_shown = 0;
    foreach ($visible_images as $image) {
        if ($images_shown < 5) {
            $output .= '<div class="grid-item">
                <a data-fancybox="gallery" href="' . $image['file_name'] . '"  
                   data-caption="' . $image['title'] . ' - ' . $image['description'] . '">
                    <img src="' . $image['file_name'] . '" alt="' . $image['title'] . '">
                </a>
            </div>';
            $images_shown++;
        }
    }

    $output .= '</div>'; // Close masonry-grid

    // Add Load More button if there are more than 5 images
    if (count($visible_images) > 5) {
        $output .= '<button id="load-more" data-gallery-id="' . esc_attr($gallery_id) . '" data-offset="5">Load More</button>';
    }

    $output .= '<div id="loading-spinner" style="display: none; text-align: center;">
        <img src="' . esc_url($loading_gif) . '" alt="Loading..." />
    </div>';

    // Hidden div to store all images
    $output .= '<div id="all-images-' . esc_attr($gallery_id) . '" style="display: none;">' . 
              htmlspecialchars(json_encode($visible_images), ENT_QUOTES, 'UTF-8') . 
           '</div>';

    $output .= '</div>'; // Close masonry-container
    return $output;
}
add_shortcode('gallery_custom', 'fetch_gallery_images_by_id');


// AJAX handler to process shortcode requests
function image_shortcode_ajax() {
    if (isset($_POST['gallery_id'])) {
        $gallery_id = intval($_POST['gallery_id']); // Get gallery ID from POST data
        print_r($gallery_id);
        echo fetch_gallery_images_by_id(['id' => $gallery_id]); // Use the gallery ID directly in fetch function
    } else {
        echo '<p>Error: No gallery ID provided.</p>';
        error_log('Error: No gallery ID provided in AJAX request.');
    }
    wp_die();
}
add_action('wp_ajax_image_shortcode', 'image_shortcode_ajax');
add_action('wp_ajax_nopriv_image_shortcode', 'image_shortcode_ajax');
