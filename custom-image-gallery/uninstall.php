<?php
if(!defined('WP_UNINSTALL_PLUGIN')){
    die;
}

global $wpdb, $table_prefix;
$wp_gallery = $table_prefix . 'image_gallery';
$q="DROP TABLE `$wp_gallery`";
$wpdb->query($q);