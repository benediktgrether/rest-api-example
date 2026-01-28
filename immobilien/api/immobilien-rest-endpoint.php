<?php

/**
 * ============================================================
 * REST API: Immobilien Endpunkte
 * ============================================================
 *
 * Namespace: km/v1
 *
 * Endpunkte:
 *  - GET /wp-json/km/v1/immobilien
 *  - GET /wp-json/km/v1/immobilien/{id}
 *
 * Zweck:
 *  - Immobilien als JSON ausgeben (Liste + Single)
 *  - Grundlage zum Erweitern (Filter, ACF, Auth, etc.)
 */


/**
 * ------------------------------------------------------------
 * REST Routes registrieren
 * ------------------------------------------------------------
 *
 * rest_api_init wird von WordPress aufgerufen,
 * sobald die REST API initialisiert wird.
 *
 * HIER müssen alle eigenen Endpunkte registriert werden.
 */
add_action('rest_api_init', function () {

    /**
     * --------------------------------------------------------
     * LISTE: GET /wp-json/km/v1/immobilien
     * --------------------------------------------------------
     *
     * Beispiel:
     *  /wp-json/km/v1/immobilien?per_page=10&page=1&search=haus
     */
    register_rest_route('km/v1', '/immobilien', [
        // HTTP Methode
        'methods'  => 'GET',

        // Funktion, die ausgeführt wird
        'callback' => 'km_get_immobilien',

        // Zugriffskontrolle
        // __return_true = öffentlich (keine Auth nötig)
        'permission_callback' => '__return_true',

        // Erlaubte Query-Parameter + Validierung
        'args' => [
            'per_page' => [
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint', // erzwingt positive Integer
            ],
            'page' => [
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'search' => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]);

    /**
     * --------------------------------------------------------
     * SINGLE: GET /wp-json/km/v1/immobilien/{id}
     * --------------------------------------------------------
     *
     * (?P<id>\d+) ist ein Regex:
     *  - id = Name des Parameters
     *  - \d+ = nur Zahlen erlaubt
     *
     * Beispiel:
     *  /wp-json/km/v1/immobilien/123
     */
    register_rest_route('km/v1', '/immobilien/(?P<id>\d+)', [
        'methods'  => 'GET',
        'callback' => 'km_get_immobilie',
        'permission_callback' => '__return_true',
        'args' => [
            'id' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'required'          => true,
            ],
        ],
    ]);
});


/**
 * ------------------------------------------------------------
 * Helper: WP_Post -> Immobilien JSON
 * ------------------------------------------------------------
 *
 * Diese Funktion ist extrem wichtig:
 * - Sie kapselt die Datenstruktur
 * - Du kannst sie später leicht erweitern
 * - Wird für LISTE und SINGLE verwendet
 */
function km_format_immobilie(\WP_Post $post): array
{
    $post_id = (int) $post->ID;

    /**
     * Beschreibung:
     * - kommt aus dem Editor (post_content)
     * - apply_filters('the_content') wendet WP-Filter an
     *   (Shortcodes, Blocks, etc.)
     */
    $beschreibung = apply_filters('the_content', $post->post_content);

    /**
     * Post Meta Felder
     * (z.B. via ACF oder eigene Meta Boxen)
     */
    $kaufpreis    = get_post_meta($post_id, 'kaufpreis', true);
    $zimmer       = get_post_meta($post_id, 'zimmer', true);
    $quadratmeter = get_post_meta($post_id, 'quadratmeter', true);
    $baujahr      = get_post_meta($post_id, 'baujahr', true);

    /**
     * Rückgabe-Struktur
     * Dieses Array wird automatisch zu JSON serialisiert
     */
    return [
        'id'           => $post_id,
        'title'        => get_the_title($post_id),

        // HTML entfernen → reiner Text
        'beschreibung' => wp_strip_all_tags($beschreibung),

        // Typen sauber casten + null bei leeren Werten
        'kaufpreis'    => $kaufpreis !== '' ? (float) $kaufpreis : null,
        'zimmer'       => $zimmer !== '' ? (float) $zimmer : null,
        'quadratmeter' => $quadratmeter !== '' ? (float) $quadratmeter : null,
        'baujahr'      => $baujahr !== '' ? (int) $baujahr : null,

        // Link zum Frontend
        'permalink'    => get_permalink($post_id),
    ];
}


/**
 * ------------------------------------------------------------
 * Controller: Immobilien LISTE
 * ------------------------------------------------------------
 *
 * Wird aufgerufen von:
 *  GET /wp-json/km/v1/immobilien
 */
function km_get_immobilien(\WP_REST_Request $request)
{
    /**
     * Query-Parameter aus der URL lesen
     * (Defaults + Sanitizing kommen aus register_rest_route)
     */
    $per_page = max(1, min(50, (int) $request->get_param('per_page')));
    $page     = max(1, (int) $request->get_param('page'));
    $search   = (string) $request->get_param('search');

    /**
     * WP_Query holt die Immobilien aus der DB
     */
    $query = new \WP_Query([
        'post_type'      => 'immobilie',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,

        // WordPress Standard-Suche (Titel + Content)
        's'              => $search ?: '',
    ]);

    /**
     * WP_Post[] -> Immobilien JSON[]
     */
    $items = array_map('km_format_immobilie', $query->posts);

    /**
     * REST Response mit Paging-Infos
     */
    return new \WP_REST_Response([
        'items'       => $items,
        'page'        => $page,
        'per_page'    => $per_page,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
    ], 200);
}


/**
 * ------------------------------------------------------------
 * Controller: SINGLE Immobilie
 * ------------------------------------------------------------
 *
 * Wird aufgerufen von:
 *  GET /wp-json/km/v1/immobilien/{id}
 */
function km_get_immobilie(\WP_REST_Request $request)
{
    // ID aus dem URL-Parameter
    $id = (int) $request->get_param('id');

    // Post laden
    $post = get_post($id);

    /**
     * Validierung:
     * - existiert?
     * - richtiger Post-Type?
     * - veröffentlicht?
     */
    if (!$post || $post->post_type !== 'immobilie' || $post->post_status !== 'publish') {
        return new \WP_Error(
            'not_found',
            'Immobilie nicht gefunden.',
            ['status' => 404]
        );
    }

    // Erfolgsfall
    return new \WP_REST_Response(km_format_immobilie($post), 200);
}
