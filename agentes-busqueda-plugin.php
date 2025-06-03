<?php

/*
    Plugin Name: Campos de Agentes, Contratistas y Banqueros + Buscador
    Description: Se añade campos de agentes, contratistas y banqueros a los usuarios y un buscador para filtrar por estos campos.
    Version: 1.2.5
    Author: Ajcänjun Dev
*/

class AgentesBusquedaPlugin {
    function __construct() {
        add_action("init", array($this, "extra_post_types"));
        add_action('init', array($this, 'register_custom_columns_for_post_types'));
        add_action('save_post', array($this, 'handle_save_post') , 10, 3);
        add_action('admin_init', array($this, 'initialize_missing_fields'));
        add_filter("the_content", array($this, "ifWrap"));
        add_action('admin_menu', array($this, "add_membership_submenus"));

        wp_enqueue_style("agentsStylesCSS", plugin_dir_url(__FILE__) . "styles.css");
        wp_enqueue_script("fontAwesomeIconsScript", "//kit.fontawesome.com/8179d57828.js", NULL, 1, TRUE );
        wp_enqueue_script(
            'live-search',
            plugin_dir_url(__FILE__) . 'live-search.js',
            array(),
            '1.0',
            true
        );
        wp_enqueue_script( "slider-agents", plugin_dir_url(__FILE__) . 'slider-agents.js', array(), '1.0', true );
        wp_enqueue_script( "membresia-toggle", plugin_dir_url(__FILE__) . 'membresia-toggle.js', array(), '1.0', true );
    
        // Agregar estilos opcionales para los resultados
        wp_enqueue_style(
            'live-search-styles',
            plugin_dir_url(__FILE__) . 'live-search.css'
        );

        add_action('wp_ajax_toggle_membresia_agente', function() {
            $post_id = intval($_POST['post_id']);
            $activa = $_POST['activa'] == '1' ? 1 : 0;

            // Actualizar el campo de membresía activa
            update_field('membresia_activa', $activa, $post_id);

            // Si se activa la membresía, actualizar la fecha de inicio y calcular la fecha de fin
            if ($activa) {
                $fecha_actual = current_time('Y-m-d'); // Obtener la fecha actual en formato Y-m-d
                update_post_meta($post_id, 'fecha_inicio', $fecha_actual); // Guardar la fecha de inicio

                // Calcular la fecha de fin (6 meses después de la fecha de inicio)
                $dt_inicio = new DateTime($fecha_actual);
                $dt_fin = clone $dt_inicio;
                $dt_fin->modify('+6 months');
                update_post_meta($post_id, 'fecha_fin', $dt_fin->format('Y-m-d')); // Guardar la fecha de fin
            }

            wp_send_json_success();
        });
    }

    function initialize_missing_fields() {
        $post_types = ['agentes', 'contratistas', 'inversionistas'];

        foreach ($post_types as $post_type) {
            $query = new WP_Query(array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ));

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();

                    // Verificar y establecer fecha_inicio
                    $fecha_inicio = get_post_meta($post_id, 'fecha_inicio', true);
                    if (!$fecha_inicio) {
                        $post = get_post($post_id);
                        $fecha_inicio = $post->post_date; // Usar la fecha de creación del post
                        update_post_meta($post_id, 'fecha_inicio', $fecha_inicio);
                    }

                    // Verificar y establecer fecha_fin
                    $fecha_fin = get_post_meta($post_id, 'fecha_fin', true);
                    if (!$fecha_fin) {
                        $dt_inicio = new DateTime($fecha_inicio);
                        $dt_fin = clone $dt_inicio;
                        $dt_fin->modify('+6 months');
                        update_post_meta($post_id, 'fecha_fin', $dt_fin->format('Y-m-d'));
                    }

                    // Verificar y establecer membresia_activa
                    $membresia_activa = get_field('membresia_activa', $post_id);
                    if ($membresia_activa === null) {
                        update_field('membresia_activa', 0, $post_id); // Desactivado por defecto
                    }
                }
                wp_reset_postdata();
            }
        }
    }

    function handle_custom_columns($column, $post_id, $post_type) {
        $meses = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        if ( 'fecha_inicio' === $column ) {
            $fecha_inicio = get_post_meta($post_id, 'fecha_inicio', true);

                    // Si no hay fecha_inicio, usar la fecha de creación del post
            if (!$fecha_inicio) {
                $post = get_post($post_id);
                $fecha_inicio = $post->post_date; // Obtener la fecha de creación del post
            }


            if ($fecha_inicio) {
                $dt_inicio = new DateTime($fecha_inicio);
                $mes = $meses[(int)$dt_inicio->format('n')];
                $fecha_formateada = $mes . '/' . $dt_inicio->format('d/Y');
                echo esc_html($fecha_formateada);
            } else {
                echo 'N/A'; // Mostrar "N/A" si no hay fecha de inicio
            }
        }

        if ( 'fecha_fin' === $column ) {
            $fecha_inicio = get_post_meta($post_id, 'fecha_inicio', true);
            if ($fecha_inicio) {
                $dt_inicio = new DateTime($fecha_inicio);
                $dt_fin = clone $dt_inicio;
                $dt_fin->modify('+6 months');
                $mes = $meses[(int)$dt_fin->format('n')];
                $fecha_formateada = $mes . '/' . $dt_fin->format('d/Y');
                echo esc_html($fecha_formateada);
            } else {
                echo 'N/A';
            }
        }

        if ( 'acciones' === $column ) {
            $checked = get_field('membresia_activa', $post_id) ? 'checked' : '';
            $fecha_inicio = get_post_meta($post_id, 'fecha_inicio', true);
            $fecha_fin = get_post_meta($post_id, 'fecha_fin', true);

            if ($fecha_inicio && $fecha_fin) {
                $dt_fin = new DateTime($fecha_fin);
                $hoy = new DateTime();
                $dias_restantes = $hoy->diff($dt_fin)->days;
                $is_about_to_expire = ($dt_fin > $hoy && $dias_restantes <= 14);
                $extra_class = $is_about_to_expire ? 'slider-about-to-expire' : '';

                echo '
                <label class="switch">
                    <input type="checkbox" class="agente-membresia-switch" data-id="' . $post_id . '" ' . $checked . '>
                    <span class="slider round ' . $extra_class . '"></span>
                </label>
                ';
            } else {
                echo 'N/A';
            }
        }
    }

    function register_custom_columns_for_post_types() {
        $post_types = ['agentes', 'contratistas', 'inversionistas'];

        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", function($columns) {
                $columns['fecha_inicio'] = __( 'Fecha Inicio' );
                $columns['fecha_fin'] = __( 'Fecha Fin');
                $columns['acciones'] = __( '¿Es Miembro?');
                return $columns;
            });

            add_action("manage_{$post_type}_posts_custom_column", function($column, $post_id) use ($post_type) {
                $this->handle_custom_columns($column, $post_id, $post_type); // Usar $this-> para llamar al método
            }, 10, 2);
        }
    }

    function handle_save_post($post_id, $post, $update) {
        $post_types = ['agentes', 'contratistas', 'inversionistas'];

        if (!in_array($post->post_type, $post_types)) {
            return;
        }

        if ($update) {
            return;
        }

        $fecha_inicio = get_post_meta($post_id, 'fecha_inicio', true);
        if (!$fecha_inicio) {
            $fecha_inicio = $post->post_date; // Usar la fecha de creación del post
            update_post_meta($post_id, 'fecha_inicio', $fecha_inicio);

            // Calcular la fecha de fin (6 meses después de la fecha de inicio)
            $dt_inicio = new DateTime($fecha_inicio);
            $dt_fin = clone $dt_inicio;
            $dt_fin->modify('+6 months');
            update_post_meta($post_id, 'fecha_fin', $dt_fin->format('Y-m-d'));
        }
    }


    function ifWrap($content)
    {
        if (is_page("servicios")) {
            return $this->createHTML($content);
        }

        return $content;
    }
    

    function createHTML($content) {

        $content .= '
            <section class="search-bar">
                <input type="text" id="search-input" placeholder="Teclee Agentes, Contratistas o Inversionistas para filtrar" />
                <input type="text" id="codigo-postal-input" placeholder="Código postal" />
                <input type="text" id="estado-input" placeholder="Estado" />
            </section>
            <div id="loading-indicator" style="display: none;">Buscando resultados...</div>
            <div id="search-results"></div>
            ';

        $queryAgentes = new WP_Query(array(
            "post_type" => array("agentes"),
            "meta_query" => array(
                array(
                    "key" => "membresia_activa",
                    "value" => 1,
                    "compare" => "="
                )
            )
        )); wp_reset_postdata();
        
        if ($queryAgentes->have_posts()) {
            $agentCount = 0; // Contador de agentes

            $content .= '
                <div class="agent-title title-cards-section">
                        <h2>Agentes</h2>
                    </div>
                    ';

                    if ($queryAgentes->found_posts > 4) {
                        $content .= '<div class="main-cards-section">';
                    } else {
                        $content .= '<div class="main-cards-section" style="height: unset;">';
                    }
                    


                    while($queryAgentes->have_posts()) {
                        $queryAgentes->the_post();

                        // Generate slider if are more than 4 cards and it's the first of the 4 package-card
                        if ($queryAgentes->found_posts > 4 &&$agentCount % 4 === 0) {
                            $content .= '<div class="slider-agents">
                                <section class="agent-container cards-container">';
                        } else if ($agentCount === 0){
                            // Generate only container if it less than 5 posts.
                            $content .= '
                                <section class="agent-container cards-container">';
                        }

                        $facebookIcon = '';
                        if (get_field("facebook_link")) {
                            $facebookIcon = '<a href="' . get_field("facebook_link") . '" class="grid-icon">
                                                <i class="fa-brands fa-facebook"></i>
                                            </a>';
                        }

                        $instagramIcon = '';
            
                        if (get_field("instagram_link")) {
                            $instagramIcon = '<a href="' . get_field("instagram_link") . '" class="grid-icon">
                                                <i class="fa-brands fa-instagram"></i>
                                            </a>';
                        }

                        $tiktokIcon = '';
            
                        if (get_field("tiktok_link")) {
                            $tiktokIcon = '<a href="' . get_field("tiktok_link") . '" class="grid-icon">
                                                <i class="fa-brands fa-tiktok"></i>
                                            </a>';
                        }
                        
                        $linkCasa1 = '';

                        if(get_field("link_casa_1")) {
                            $linkCasa1 = '<a href="'. get_field("link_casa_1") .'">
                                <i class="fa-solid fa-house"></i>
                            </a>';
                        }

                        $linkCasa2 = '';

                        if(get_field("link_casa_2")) {
                            $linkCasa2 = '<a href="'. get_field("link_casa_2") .'">
                                <i class="fa-solid fa-house"></i>
                            </a>';
                        }

                        $linkCasa3 = '';

                        if(get_field("link_casa_3")) {
                            $linkCasa3 = '<a href="'. get_field("link_casa_3") .'">
                                <i class="fa-solid fa-house"></i>
                            </a>';
                        }

                        $linkCasa4 = '';

                        if(get_field("link_casa_4")) {
                            $linkCasa4 = '<a href="'. get_field("link_casa_4") .'">
                                <i class="fa-solid fa-house"></i>
                            </a>';
                        }

                        $userPictureClassPro = "";
                        $userPictureLabelPro = "";

                        if (get_field("is_pro_user")) {
                            $userPictureClassPro = "profile-picture-container-pro";
                            $userPictureLabelPro = "profile-picture-label-pro";
                        }

                        $buttonPodcast = "";

                        if (get_field("link_podcast")) {
                            $buttonPodcast = '<div class="podcast-button-link-container">
                            <a href=' . get_field("link_podcast") . '" class="podcast-button-link">
                            <div class="podcast-button-main-content">
                                <i class="fa-solid fa-podcast"></i>
                                <span>Podcast #' . get_field("numero_podcast") .'</span>
                            </div>
                            <p class="podcast-button-subtitle">youtube.com</p>
                            </a>
                            </div>
                            ';
                        }
                        
                        $content .= '
                            <article class="modern-card agent-profile">
                                <section class="basic-info">
                                <div class="profile-picture-container ' . $userPictureClassPro . '">
                                <img
                                    src="' . get_field("imagen_perfil")["sizes"]["medium"] . '"
                                    alt="Profile Picture"
                                    class="profile-picture"
                                />
                                <div class="profile-picture-label ' . $userPictureLabelPro . '">Pro</div>
                                </div>
                                <h3 class="modern-card-title">' . get_the_title() . '</h3>
                                <div class="phone-container">
                                    <i class="fa-solid fa-phone-volume"></i>
                                    <span>' . get_field("mobile") . '</span>
                                </div>
                                <div class="social-media">
                                    ' . $facebookIcon . $instagramIcon
                                        . $tiktokIcon . '
                                </div>
                                </section>
                                <section class="main-info">
                                <div class="main-info-presentation">
                                    <h2 class="main-info-title">Agente Inmobiliario</h2>
                                    <div class="website-container">
                                    <i class="fa-solid fa-globe"></i>
                                    <a href="'. get_field("link_sitio_web") . '">'. get_field("link_sitio_web") . '</a>
                                    </div>
                                </div>';

                                if ($linkCasa1 || $linkCasa2 || $linkCasa3 || $linkCasa4) {
                                    $content .= '
                                    <div class="sale-home-container">
                                    <h4>Casas en venta</h4>
                                    <div class="sale-home-icons-container">
                                    ' . $linkCasa1 . $linkCasa2
                                        . $linkCasa3 . $linkCasa4 . '
                                    </div>
                                </div>
                                    ';
                                }

                                $content .= '
                                <div class="main-info-location">
                                    <div class="location-container">
                                    <p><strong>Ciudad:</strong> ' . get_field("ciudad") . '</p>
                                    <p><strong>Estado:</strong> ' . get_field("estado") . '</p>
                                    </div>
                                    <div class="postal-code-container">
                                    <strong>Códigos Postales</strong>
                                    <p>' . get_field("codigo_postal") . '</p>
                                    </div>
                                </div>
                                </section>
                                <section class="podcast-button-section">' .
                                    $buttonPodcast . '
                                </section>
                            </article>
                        ';

                        $agentCount++;

                        // Close slider if are 4 rendered cards

                        if ($queryAgentes->found_posts > 4 && $agentCount % 4 === 0) {
                            $content .= '</section>
                            </div>';
                        }

                        // Close slider if it is the last card

                        if ($queryAgentes->found_posts > 4 && $queryAgentes->found_posts == $agentCount) {
                            $content .= '</section>
                            </div>';
                        }

                        if ($queryAgentes->found_posts <= 4 && $queryAgentes->found_posts == $agentCount) {
                            $content .= '</section>';
                        }
                    
                    }

                    // Add if there are more than 4 cards because of sliders

                    if ($queryAgentes->found_posts > 4) {
                        $content .= '<button class="slider__btn slider__btn--left">
                        <i class="fa fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button class="slider__btn slider__btn--right">
                        <i class="fa fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <div class="dots-agents"></div>';
                    } 

            $content .= '</div>';
            
        }

        
        $queryContratistas = new WP_Query(array(
            "post_type" => array("contratistas"),
            "meta_query" => array(
                array(
                    "key" => "membresia_activa",
                    "value" => 1,
                    "compare" => "="
                )
            )
        )); wp_reset_postdata();

        if ($queryContratistas->have_posts()) {

             // Índice del slider
            $contractorCount = 0; // Contador de agentes

            $content .= '<div class="contractor-title title-cards-section">
                        <h2>Contratistas</h2>
                    </div>
                    ';

            if ($queryContratistas->found_posts > 4) {
                $content .= '<div class="main-cards-section">';
            } else {
                $content .= '<div class="main-cards-section" style="height: unset;">';
            }

            while($queryContratistas->have_posts()) {
                $queryContratistas->the_post();

                // Generate slider if are more than 4 cards and it's the first of the 4 package-card
                if ($queryContratistas->found_posts > 4 &&$contractorCount % 4 === 0) {
                    $content .= '<div class="slider-contractors">
                        <section class="agent-container cards-container">';
                } else if ($contractorCount === 0){
                    // Generate only container if it less than 5 posts.
                    $content .= '
                        <section class="agent-container cards-container">';
                }

                $facebookIcon = '';
                if (get_field("facebook_link")) {
                    $facebookIcon = '<a href="' . get_field("facebook_link") . '" class="grid-icon">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>';
                }

                $instagramIcon = '';
    
                if (get_field("instagram_link")) {
                    $instagramIcon = '<a href="' . get_field("instagram_link") . '" class="grid-icon">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>';
                }

                $tiktokIcon = '';
    
                if (get_field("tiktok_link")) {
                    $tiktokIcon = '<a href="' . get_field("tiktok_link") . '" class="grid-icon">
                                        <i class="fa-brands fa-tiktok"></i>
                                    </a>';
                }   
                
                $servicio1 = '';

                if(get_field("servicio_1")) {
                    $servicio1 = '<li>'. get_field("servicio_1") .'</li>';
                }

                $servicio2 = '';

                if(get_field("servicio_2")) {
                    $servicio2 = '<li>'. get_field("servicio_2") .'</li>';
                }

                $servicio3 = '';

                if(get_field("servicio_3")) {
                    $servicio3 = '<li>'. get_field("servicio_3") .'</li>';
                }

                $servicio4 = '';

                if(get_field("servicio_4")) {
                    $servicio4 = '<li>'. get_field("servicio_4") .'</li>';
                }      
                
                $userPictureClassPro = "";
                $userPictureLabelPro = "";

                if (get_field("is_pro_user")) {
                    $userPictureClassPro = "profile-picture-container-pro";
                    $userPictureLabelPro = "profile-picture-label-pro";
                }

                
                $buttonPodcast = "";

                if (get_field("link_podcast")) {
                    $buttonPodcast = '<div class="podcast-button-link-container">
                    <a href=' . get_field("link_podcast") . '" class="podcast-button-link">
                    <div class="podcast-button-main-content">
                        <i class="fa-solid fa-podcast"></i>
                        <span>Podcast #' . get_field("numero_podcast") .'</span>
                    </div>
                    <p class="podcast-button-subtitle">youtube.com</p>
                    </a>
                    </div>
                    ';
                }
    
                $content .= '
                    <article class="modern-card contractor-profile">
                        <section class="basic-info">
                        <div class="profile-picture-container ' . $userPictureClassPro . '">
                        <img
                            src="' . get_field("imagen_perfil")["sizes"]["medium"] . '"
                            alt="Profile Picture"
                            class="profile-picture"
                        />
                        <div class="profile-picture-label ' . $userPictureLabelPro . '">Pro</div>
                        </div>
                        <h3 class="modern-card-title">' . get_the_title() . '</h3>
                        <div class="phone-container">
                            <i class="fa-solid fa-phone-volume"></i>
                            <span>' . get_field("mobile") . '</span>
                        </div>
                        <div class="social-media">
                            ' . $facebookIcon . $instagramIcon
                                . $tiktokIcon . '
                        </div>
                        </section>
                        <section class="main-info">
                        <div class="main-info-presentation">
                            <h2 class="main-info-title">Contratista</h2>
                            <div class="website-container">
                            <i class="fa-solid fa-globe"></i>
                            <a href="#">'. get_field("link_sitio_web") . '</a>
                            </div>
                        </div>';

                        if ($servicio1 || $servicio2 || $servicio3 || $servicio4) {
                            $content .= '
                            <div class="services-container">
                            <h4>Mis servicios</h4>
                            <ul class="services-list-container">
                            ' . $servicio1 . $servicio2
                                . $servicio3 . $servicio4 . '
                            </ul>
                        </div>
                            ';
                        }

                        $content .= '
                        <div class="main-info-location">
                            <div class="location-container">
                            <p><strong>Ciudad:</strong> ' . get_field("ciudad") . '</p>
                            <p><strong>Estado:</strong> ' . get_field("estado") . '</p>
                            </div>
                            <div class="postal-code-container">
                            <strong>Códigos Postales</strong>
                            <p>' . get_field("codigo_postal") . '</p>
                            </div>
                        </div>
                        </section>
                        <section class="podcast-button-section">' .
                            $buttonPodcast . '
                        </section>
                    </article>
                ';
                
                $contractorCount++;

                // Close slider if are 4 rendered cards

                if ($queryContratistas->found_posts > 4 && $contractorCount % 4 === 0) {
                    $content .= '</section>
                    </div>';
                }

                // Close slider if it is the last card

                if ($queryContratistas->found_posts > 4 && $queryContratistas->found_posts == $contractorCount) {
                    $content .= '</section>
                    </div>';
                }

                // Close section with less than 4 cards
                if ($queryContratistas->found_posts <= 4 && $queryContratistas->found_posts == $contractorCount) {
                    $content .= '</section>';
                }

            }

            // Add if there are more than 4 cards because of sliders

            if ($queryContratistas->found_posts > 4) {
                $content .= '<button class=" slider__btn slider__btn--left-contractor">
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button class="slider__btn slider__btn--right-contractor">
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </button>
            <div class="dots-contractors"></div>';
            } 
    
            $content .= '</div>';
        }
            

        

        

        $queryInversionistas = new WP_Query(array(
            "post_type" => array("inversionistas"),
            "meta_query" => array(
                array(
                    "key" => "membresia_activa",
                    "value" => 1,
                    "compare" => "="
                )
            )
        )); wp_reset_postdata();

        if ($queryInversionistas->have_posts()) {
            $bankerCount = 0; // Contador de agentes

            $content .= '<div class="banker-title title-cards-section">
                        <h2>Inversionistas</h2>
                    </div>
                    ';

                    if ($queryInversionistas->found_posts > 4) {
                        $content .= '<div class="main-cards-section">';
                    } else {
                        $content .= '<div class="main-cards-section" style="height: unset;">';
                    }
            
            while($queryInversionistas->have_posts()) {
                $queryInversionistas->the_post();
                // Generate slider if are more than 4 cards and it's the first of the 4 package-card
                if ($queryInversionistas->found_posts > 4 &&$bankerCount % 4 === 0) {
                    $content .= '<div class="slider-investors">
                        <section class="agent-container cards-container">';
                } else if ($bankerCount === 0){
                    // Generate only container if it less than 5 posts.
                    $content .= '
                        <section class="agent-container cards-container">';
                }

                $facebookIcon = '';
                if (get_field("facebook_link")) {
                    $facebookIcon = '<a href="' . get_field("facebook_link") . '" class="grid-icon">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>';
                }

                $instagramIcon = '';
    
                if (get_field("instagram_link")) {
                    $instagramIcon = '<a href="' . get_field("instagram_link") . '" class="grid-icon">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>';
                }

                $tiktokIcon = '';
    
                if (get_field("tiktok_link")) {
                    $tiktokIcon = '<a href="' . get_field("tiktok_link") . '" class="grid-icon">
                                        <i class="fa-brands fa-tiktok"></i>
                                    </a>';
                }    

                $linkCasa1 = '';

                if(get_field("link_casa_1")) {
                    $linkCasa1 = '<a href="'. get_field("link_casa_1") .'">
                        <i class="fa-solid fa-house"></i>
                    </a>';
                }

                $linkCasa2 = '';

                if(get_field("link_casa_2")) {
                    $linkCasa2 = '<a href="'. get_field("link_casa_2") .'">
                        <i class="fa-solid fa-house"></i>
                    </a>';
                }

                $linkCasa3 = '';

                if(get_field("link_casa_3")) {
                    $linkCasa3 = '<a href="'. get_field("link_casa_3") .'">
                        <i class="fa-solid fa-house"></i>
                    </a>';
                }

                $linkCasa4 = '';

                if(get_field("link_casa_4")) {
                    $linkCasa4 = '<a href="'. get_field("link_casa_4") .'">
                        <i class="fa-solid fa-house"></i>
                    </a>';
                }

                $userPictureClassPro = "";
                $userPictureLabelPro = "";

                if (get_field("is_pro_user")) {
                    $userPictureClassPro = "profile-picture-container-pro";
                    $userPictureLabelPro = "profile-picture-label-pro";
                }

                $buttonPodcast = "";

                if (get_field("link_podcast")) {
                    $buttonPodcast = '<div class="podcast-button-link-container">
                    <a href=' . get_field("link_podcast") . '" class="podcast-button-link">
                    <div class="podcast-button-main-content">
                        <i class="fa-solid fa-podcast"></i>
                        <span>Podcast #' . get_field("numero_podcast") .'</span>
                    </div>
                    <p class="podcast-button-subtitle">youtube.com</p>
                    </a>
                    </div>
                    ';
                }
    
                $content .= '
                    <article class="modern-card banker-profile">
                        <section class="basic-info">
                        <div class="profile-picture-container ' . $userPictureClassPro . '">
                        <img
                            src="' . get_field("imagen_perfil")["sizes"]["medium"] . '"
                            alt="Profile Picture"
                            class="profile-picture"
                        />
                        <div class="profile-picture-label ' . $userPictureLabelPro . '">Pro</div>
                        </div>
                        <h3 class="modern-card-title">' . get_the_title() . '</h3>
                        <div class="phone-container">
                            <i class="fa-solid fa-phone-volume"></i>
                            <span>' . get_field("mobile") . '</span>
                        </div>
                        <div class="social-media">
                            ' . $facebookIcon . $instagramIcon
                                . $tiktokIcon . '
                        </div>
                        </section>
                        <section class="main-info">
                        <div class="main-info-presentation">
                            <h2 class="main-info-title">Inversionista</h2>
                            <div class="website-container">
                            <i class="fa-solid fa-globe"></i>
                            <a href="#">'. get_field("link_sitio_web") . '</a>
                            </div>
                        </div>';

                        if ($linkCasa1 || $linkCasa2 || $linkCasa3 || $linkCasa4) {
                            $content .= '
                            <div class="sale-home-container">
                            <h4>Casas en venta</h4>
                            <div class="sale-home-icons-container">
                            ' . $linkCasa1 . $linkCasa2
                                . $linkCasa3 . $linkCasa4 . '
                            </div>
                        </div>
                            ';
                        }

                        $content .= '
                        <div class="main-info-location">
                            <div class="location-container">
                            <p><strong>Ciudad:</strong> ' . get_field("ciudad") . '</p>
                            <p><strong>Estado:</strong> ' . get_field("estado") . '</p>
                            </div>
                            <div class="postal-code-container">
                            <strong>Códigos Postales</strong>
                            <p>' . get_field("codigo_postal") . '</p>
                            </div>
                        </div>
                        </section>
                        <section class="podcast-button-section">' .
                            $buttonPodcast . '
                        </section>
                    </article>
                ';

                $bankerCount++;

                // Close slider if are 4 rendered cards

                if ($queryInversionistas->found_posts > 4 && $bankerCount % 4 === 0) {
                    $content .= '</section>
                    </div>';
                }

                // Close slider if it is the last card

                if ($queryInversionistas->found_posts > 4 && $queryInversionistas->found_posts == $bankerCount) {
                    $content .= '</section>
                    </div>';
                }

                if ($queryInversionistas->found_posts <= 4 && $queryInversionistas->found_posts == $bankerCount) {
                    $content .= '</section>';
                }
                
                
            }

            // Add if there are more than 4 cards because of sliders

            if ($queryInversionistas->found_posts > 4) {
                $content .= '<button class="slider__btn slider__btn--left-investor">
                <i class="fa fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button class="slider__btn slider__btn--right-investor">
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </button>
            <div class="dots-investors"></div>';
            } 
    
            $content .= '</div></div>';
        }

        

        wp_reset_postdata();

        return $content;
    }

    function extra_post_types() {
        register_post_type("agentes", array(
            "supports" => array("title"),
            "rewrite" => array("slug" => "agentes"),
            'has_archive' => true,
            'public' => true,
            'labels' => array(
                'name' => 'Agentes',
                'add_new_item' => 'Añadir nuevo agente',
                'edit_item' => 'Editar agente',
                'all_items' => 'Todos los agentes',
                'singular_name' => 'Agente'
            ),
            'menu_icon' => 'dashicons-businessperson',
            'show_in_rest' => true
        ));
    
        register_post_type("contratistas", array(
            "supports" => array("title"),
            "rewrite" => array("slug" => "contratistas"),
            'has_archive' => true,
            'public' => true,
            'labels' => array(
                'name' => 'Contratistas',
                'add_new_item' => 'Añadir nuevo contratista',
                'edit_item' => 'Editar contratista',
                'all_items' => 'Todos los contratistas',
                'singular_name' => 'Contratista'
            ),
            'menu_icon' => 'dashicons-media-document',
            'show_in_rest' => true
        ));
    
        register_post_type("inversionistas", array(
            "supports" => array("title"),
            "rewrite" => array("slug" => "inversionistas"),
            'has_archive' => true,
            'public' => true,
            'labels' => array(
                'name' => 'Inversionistas',
                'add_new_item' => 'Añadir nuevo inversionista',
                'edit_item' => 'Editar inversionista',
                'all_items' => 'Todos los inversionistas',
                'singular_name' => 'Inversionista'
            ),
            'menu_icon' => 'dashicons-bank',
            'show_in_rest' => true
        ));
    }

    function add_membership_submenus() {
        $post_types = ['agentes', 'contratistas', 'inversionistas'];

        foreach ($post_types as $post_type) {
            // Submenú de Membresía Activa
            add_submenu_page(
                "edit.php?post_type=$post_type", // Parent menu
                'Membresía Activa',             // Page title
                'Membresía Activa',             // Menu title
                'manage_options',               // Capability
                "active_membership_$post_type", // Menu slug
                function() use ($post_type) {   // Callback function
                    $this->render_membership_page($post_type,1);
                }
            );

            // Submenú de Membresía Expirada
            add_submenu_page(
                "edit.php?post_type=$post_type", // Parent menu
                'Membresía Expirada',           // Page title
                'Membresía Expirada',           // Menu title
                'manage_options',               // Capability
                "expired_membership_$post_type", // Menu slug
                function() use ($post_type) {   // Callback function
                    $this->render_membership_page($post_type,0);
                }
            );
        }
    }

    function render_membership_page($post_type, $membership_status) {
        $query = new WP_Query(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'membresia_activa',
                    'value' => $membership_status,
                    'compare' => '='
                )
            )
        ));

        $title = $membership_status ? 'Membresía Activa' : 'Membresía Expirada';

        echo '<div class="wrap">';
        echo '<h1 style="margin-bottom: 15px;">' . esc_html($title) . ' - ' . ucfirst($post_type) . '</h1>';

        if ($query->have_posts()) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Título</th><th>Fecha de Inicio</th><th>Fecha de Fin</th><th>Acciones</th></tr></thead>';
            echo '<tbody>';

            $meses = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
                7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $fecha_inicio = get_post_meta($post_id, 'fecha_inicio', true);
                $fecha_fin = get_post_meta($post_id, 'fecha_fin', true);

                // Formatear fecha_inicio
                if ($fecha_inicio) {
                    $dt_inicio = new DateTime($fecha_inicio);
                    $mes_inicio = $meses[(int)$dt_inicio->format('n')];
                    $fecha_inicio_formateada = $mes_inicio . '/' . $dt_inicio->format('d/Y');
                } else {
                    $fecha_inicio_formateada = 'N/A';
                }

                // Formatear fecha_fin
                if ($fecha_fin) {
                    $dt_fin = new DateTime($fecha_fin);
                    $mes_fin = $meses[(int)$dt_fin->format('n')];
                    $fecha_fin_formateada = $mes_fin . '/' . $dt_fin->format('d/Y');
                } else {
                    $fecha_fin_formateada = 'N/A';
                }

                // Switch para activar/desactivar membresía
                $checked = get_field('membresia_activa', $post_id) ? 'checked' : '';
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($post_id) . '">' . get_the_title() . '</a></td>';
                echo '<td>' . esc_html($fecha_inicio_formateada) . '</td>';
                echo '<td>' . esc_html($fecha_fin_formateada) . '</td>';
                echo '<td>
                    <label class="switch">
                        <input type="checkbox" class="agente-membresia-switch" data-id="' . $post_id . '" ' . $checked . '>
                        <span class="slider round"></span>
                    </label>
                </td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No hay registros con ' . esc_html($title) . '.</p>';
        }

        echo '</div>';

        wp_reset_postdata();
    }
}

$agentesBusquedaPlugin = new AgentesBusquedaPlugin();

require_once plugin_dir_path(__FILE__) . 'api-endpoints.php';

