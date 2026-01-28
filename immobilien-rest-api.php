<?php

/**
 * Plugin Name: Immobilien REST API
 * Description: REST endpoints for Immobilien (CPT + List/Single endpoints).
 * Version: 1.0.1
 * Author: Benedikt Grether
 * Text Domain: immobilien-rest-api
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ------------------------------------------------------------
 * Constants (Paths)
 * ------------------------------------------------------------
 */
define('KM_IMMO_API_VERSION', '1.0.1');
define('KM_IMMO_API_PATH', plugin_dir_path(__FILE__));
define('KM_IMMO_API_FILE', __FILE__);

/**
 * ------------------------------------------------------------
 * (Optional) Register CPT "immobilie"
 * Entferne diesen Block, wenn du den CPT schon woanders hast.
 * ------------------------------------------------------------
 */
add_action('init', function () {
    // Wenn CPT schon existiert, nichts tun (z.B. wenn ein anderes Plugin/Theme ihn registriert)
    if (post_type_exists('immobilie')) {
        return;
    }

    register_post_type('immobilie', [
        'label'         => __('Immobilien', 'immobilien-rest-api'),
        'public'        => true,

        // Damit Gutenberg/Standard WP REST "post type endpoint" aktiv ist
        'show_in_rest'  => true,

        // Was der Editor unterstützt
        'supports'      => ['title', 'editor', 'thumbnail'],

        'has_archive'   => true,
        'rewrite'       => ['slug' => 'immobilien'],

        // Optional nice defaults
        'menu_icon'     => 'dashicons-admin-multisite',
    ]);
});

/**
 * ------------------------------------------------------------
 * Include REST Endpoint Code
 * ------------------------------------------------------------
 *
 * Erwartete Struktur:
 * /immobilien/api/immobilien-rest-endpoint.php
 */
add_action('plugins_loaded', function () {
    $endpoint_file = KM_IMMO_API_PATH . 'immobilien/api/immobilien-rest-endpoint.php';

    if (file_exists($endpoint_file)) {
        require_once $endpoint_file;
        return;
    }

    /**
     * Wenn Datei fehlt: Admin-Hinweis anzeigen
     * (so merkst du sofort, wenn du auf einem System was vergessen hast)
     */
    if (is_admin()) {
        add_action('admin_notices', function () use ($endpoint_file) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('KM Immobilien REST API: Endpoint file not found:', 'immobilien-rest-api') . ' ';
            echo '<code>' . esc_html($endpoint_file) . '</code>';
            echo '</p></div>';
        });
    }
});
