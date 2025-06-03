<?php
// filepath: /home/ajcanjun/Local Sites/latinosrealstate/app/public/wp-content/plugins/agentes-busqueda-plugin/api-endpoints.php

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/search', array(
        'methods' => 'GET',
        'callback' => 'custom_search_endpoint',
        'permission_callback' => '__return_true', // Permitir acceso público
    ));
});

function custom_search_endpoint($request) {
    $search_term = sanitize_text_field($request->get_param('s')); // Término de búsqueda
    $post_type = $request->get_param('post_type') ? sanitize_text_field($request->get_param('post_type')) : array('agentes', 'contratistas', 'inversionistas'); // Tipos de post
    $codigo_postal = sanitize_text_field($request->get_param('codigo_postal')); // Código postal
    $estado = sanitize_text_field($request->get_param('estado')); // Estado

    // Configurar los argumentos de la consulta
    $meta_query = array('relation' => 'AND');

    if (!empty($codigo_postal)) {
        $meta_query[] = array(
            'key' => 'codigo_postal',
            'value' => $codigo_postal,
            'compare' => 'LIKE',
        );
    }

    if (!empty($estado)) {
        $meta_query[] = array(
            'key' => 'estado',
            'value' => $estado,
            'compare' => 'LIKE',
        );
    }

    // Configurar los argumentos de la consulta
    $args = array(
        'post_type' => $post_type,
        's' => $search_term, // Búsqueda por nombre
        'posts_per_page' => -1, // Obtener todos los resultados
        'meta_query' => $meta_query, // Filtros por meta campos
    );

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Obtener los datos del post
            $results[] = array(
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'link' => get_permalink(),
                'category' => get_post_type(), // Categorizar por tipo de post
                'is_pro_user' => get_field('is_pro_user'),
                'imagen_perfil' => get_field('imagen_perfil')['sizes']['medium'] ?? '',
                'mobile' => get_field('mobile') ?? '',
                'ciudad' => get_field('ciudad') ?? '',
                'estado' => get_field('estado') ?? '',
                'link_sitio_web' => get_field("link_sitio_web"),
                'codigo_postal' => get_field('codigo_postal') ?? '',
                'link_casa_1' => get_field("link_casa_1") ?? '',
                'link_casa_2' => get_field("link_casa_2") ?? '',
                'link_casa_3' => get_field("link_casa_3") ?? '',
                'link_casa_4' => get_field("link_casa_4") ?? '',
                'servicio_1' => get_field("servicio_1") ?? '',
                'servicio_2' => get_field("servicio_2") ?? '',
                'servicio_3' => get_field("servicio_3") ?? '',
                'servicio_4' => get_field("servicio_4") ?? '',
                'facebook_link' => get_field('facebook_link') ?? '',
                'instagram_link' => get_field('instagram_link') ?? '',
                'tiktok_link' => get_field('tiktok_link') ?? '',
                'link_podcast' => get_field('link_podcast') ?? '',
                'numero_podcast' => get_field('numero_podcast') ?? '',
            );
        }
    }

    wp_reset_postdata();

    return rest_ensure_response($results);
}