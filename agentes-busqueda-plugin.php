<?php

/*
    Plugin Name: Campos de Agentes, Contratistas y Banqueros + Buscador
    Description: Se añade campos de agentes, contratistas y banqueros a los usuarios y un buscador para filtrar por estos campos.
    Version: 1.1
    Author: Ajcänjun Dev
*/

class AgentesBusquedaPlugin {
    function __construct() {
        add_action("init", array($this, "extra_post_types"));
        add_filter("the_content", array($this, "ifWrap"));
        wp_enqueue_style("agentsStylesCSS", plugin_dir_url(__FILE__) . "styles.css");
        wp_enqueue_script("fontAwesomeIconsScript", "//kit.fontawesome.com/8179d57828.js", NULL, 1, TRUE );
        wp_enqueue_script(
            'live-search',
            plugin_dir_url(__FILE__) . 'live-search.js',
            array(),
            '1.0',
            true
        );
    
        // Agregar estilos opcionales para los resultados
        wp_enqueue_style(
            'live-search-styles',
            plugin_dir_url(__FILE__) . 'live-search.css'
        );
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
                <input type="text" id="search-input" placeholder="Agentes, Contratistas, Inversionistas" />
                <input type="text" id="codigo-postal-input" placeholder="Código postal" />
                <input type="text" id="estado-input" placeholder="Estado" />
            </section>
            <div id="loading-indicator" style="display: none;">Buscando resultados...</div>
            <div id="search-results"></div>
            ';

        $queryAgentes = new WP_Query(array(
            "post_type" => array("agentes"),
        )); wp_reset_postdata();
        
        if ($queryAgentes->have_posts()) {
            $content .= '<div class="agent-title title-cards-section">
                        <h2>Agentes</h2>
                    </div>
                    <div class="main-cards-section">
                        <section class="agent-container cards-container">
                    ';
                    while($queryAgentes->have_posts()) {
                        $queryAgentes->the_post();
                        if (get_field("facebook_link")) {
                            $facebookIcon = '<a href="' . get_field("facebook_link") . '" class="grid-icon grid-icon-black">
                                                <i class="fa-brands fa-facebook"></i>
                                            </a>';
                        } else {
                            $facebookIcon = '';
                        }
            
                        if (get_field("instagram_link")) {
                            $instagramIcon = '<a href="' . get_field("instagram_link") . '" class="grid-icon grid-icon-black">
                                                <i class="fa-brands fa-instagram"></i>
                                            </a>';
                        } else {
                            $instagramIcon = '';
                        }
            
                        if (get_field("tiktok_link")) {
                            $tiktokIcon = '<a href="' . get_field("tiktok_link") . '" class="grid-icon grid-icon-black">
                                                <i class="fa-brands fa-tiktok"></i>
                                            </a>';
                        } else {
                            $tiktokIcon = '';
                        }
                        
            
                        
                        $content .= '
                                
                        <article class="parent agent-profile">
                            <div class="grid-profile-image">
                            <img
                                src="' . get_field("imagen_perfil")["sizes"]["medium"] . '"
                                alt="Profile Picture"
                                class="profile-picture"
                            />
                            </div>
                            <div class="grid-cell grid-title grid-cell-title">
                            Agente de Bienes Raíces
                            </div>
                            <div class="grid-cell grid-name">' . get_the_title() . '</div>
                            <div class="grid-cell grid-description">
                            ' . get_field("descripcion") . '
                            </div>
                            <div class="grid-cell grid-mobile grid-cell-title">
                            <i class="fa-solid fa-phone-volume grid-icon"></i>
                            </div>
                            <div class="grid-cell grid-number">
                                ' . get_field("mobile") . '
                            </div>
                            <div class="grid-cell grid-language grid-cell-title">Idiomas</div>
                            <div class="grid-cell grid-languages">
                                ' . get_field("idiomas") . '
                            </div>
                            <div class="grid-cell grid-social grid-cell-title">Redes sociales</div>
                            <div class="grid-cell grid-city-title grid-cell-title">Ciudad</div>
                            <div class="grid-cell grid-city-name">
                                ' . get_field("ciudad") . '
                            </div>
                            <div class="grid-cell grid-state-title grid-cell-title">Estado</div>
                            <div class="grid-cell grid-state-name">
                                ' . get_field("estado") . '
                            </div>
                            <div class="grid-cell grid-social-names">
                                ' . $facebookIcon . $instagramIcon
                                . $tiktokIcon . '
                            </div>
                            <div class="grid-cell grid-codepost-title grid-cell-title">
                            Código Postal
                            </div>
                            <div class="grid-cell grid-codepost-numbers">
                                ' . get_field("codigo_postal") . '
                            </div>
                        </article>';               
                    }

        $content .= '</section>
        </div>';
            
        }

        
        

        
        $queryContratistas = new WP_Query(array(
            "post_type" => array("contratistas"),
        )); wp_reset_postdata();

        if ($queryContratistas->have_posts()) {
            $content .= '<div class="contractor-title title-cards-section">
                        <h2>Contratistas</h2>
                    </div>
                    <div class="main-cards-section">
                        <section class="contractor-container cards-container">
                    ';

            while($queryContratistas->have_posts()) {
                $queryContratistas->the_post();
                if (get_field("facebook_link")) {
                    $facebookIcon = '<a href="' . get_field("facebook_link") . '" class="grid-icon grid-icon-black">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>';
                } else {
                    $facebookIcon = '';
                }
    
                if (get_field("instagram_link")) {
                    $instagramIcon = '<a href="' . get_field("instagram_link") . '" class="grid-icon grid-icon-black">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>';
                } else {
                    $instagramIcon = '';
                }
    
                if (get_field("tiktok_link")) {
                    $tiktokIcon = '<a href="' . get_field("tiktok_link") . '" class="grid-icon grid-icon-black">
                                        <i class="fa-brands fa-tiktok"></i>
                                    </a>';
                } else {
                    $tiktokIcon = '';
                }
    
                $content .= '
                    <article class="parent contractor-profile">
                        <div class="grid-profile-image">
                        <img
                            src="' . get_field("imagen_perfil")["sizes"]["medium"] . '"
                            alt="Profile Picture"
                            class="profile-picture"
                        />
                        </div>
                        <div class="grid-cell grid-title grid-cell-title">Contratista</div>
                        <div class="grid-cell grid-name">' . get_the_title() . '</div>
                        <div class="grid-cell grid-description">
                            ' . get_field("descripcion") . '
                        </div>
                        <div class="grid-cell grid-mobile grid-cell-title">
                        <i class="fa-solid fa-phone-volume grid-icon"></i>
                        </div>
                        <div class="grid-cell grid-number">
                            ' . get_field("mobile") . '
                        </div>
                        <div class="grid-cell grid-language grid-cell-title">Idiomas</div>
                        <div class="grid-cell grid-languages">
                            ' . get_field("idiomas") . '
                        </div>
                        <div class="grid-cell grid-social grid-cell-title">Redes sociales</div>
                        <div class="grid-cell grid-city-title grid-cell-title">Ciudad</div>
                        <div class="grid-cell grid-city-name">
                            ' . get_field("ciudad") . '
                        </div>
                        <div class="grid-cell grid-state-title grid-cell-title">Estado</div>
                        <div class="grid-cell grid-state-name">
                            ' . get_field("estado") . '
                        </div>
                        <div class="grid-cell grid-social-names">
                            ' . $facebookIcon . $instagramIcon
                                    . $tiktokIcon . '
                        </div>
                        <div class="grid-cell grid-codepost-title grid-cell-title">
                        Código Postal
                        </div>
                        <div class="grid-cell grid-codepost-numbers">
                            ' . get_field("codigo_postal") . '
                        </div>
                    </article>
                ';            
            }
    
            $content .= '</section></div>';
        }
            

        

        

        $queryInversionistas = new WP_Query(array(
            "post_type" => array("inversionistas"),
        )); wp_reset_postdata();

        if ($queryInversionistas->have_posts()) {
            $content .= '<div class="banker-title title-cards-section">
                        <h2>Inversionistas</h2>
                    </div>
                    <div class="main-cards-section">
                        <section class="banker-container cards-container">
                    ';
            
            while($queryInversionistas->have_posts()) {
                $queryInversionistas->the_post();
                if (get_field("facebook_link")) {
                    $facebookIcon = '<a href="' . get_field("facebook_link") . '" class="grid-icon grid-icon-white">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>';
                } else {
                    $facebookIcon = '';
                }
    
                if (get_field("instagram_link")) {
                    $instagramIcon = '<a href="' . get_field("instagram_link") . '" class="grid-icon grid-icon-white">
                                        <i class="fa-brands fa-instagram"></i>
                                    </a>';
                } else {
                    $instagramIcon = '';
                }
    
                if (get_field("tiktok_link")) {
                    $tiktokIcon = '<a href="' . get_field("tiktok_link") . '" class="grid-icon grid-icon-white">
                                        <i class="fa-brands fa-tiktok"></i>
                                    </a>';
                } else {
                    $tiktokIcon = '';
                }
    
                $content .= '
                    <article class="parent banker-profile">
                        <div class="grid-profile-image">
                        <img
                            src="' . get_field("imagen_perfil")["sizes"]["medium"] . '"
                            alt="Profile Picture"
                            class="profile-picture"
                        />
                        </div>
                        <div class="grid-cell grid-title grid-cell-title">Inversionistas</div>
                        <div class="grid-cell grid-name">' . get_the_title() . '</div>
                        <div class="grid-cell grid-description">
                            ' . get_field("descripcion") . '
                        </div>
                        <div class="grid-cell grid-mobile grid-cell-title">
                            <i class="fa-solid fa-phone-volume grid-icon"></i>
                        </div>
                        <div class="grid-cell grid-number">
                            ' . get_field("mobile") . '
                        </div>
                        <div class="grid-cell grid-language grid-cell-title">Idiomas</div>
                        <div class="grid-cell grid-languages">
                            ' . get_field("idiomas") . '
                        </div>
                        <div class="grid-cell grid-social grid-cell-title">Redes sociales</div>
                        <div class="grid-cell grid-city-title grid-cell-title">Ciudad</div>
                        <div class="grid-cell grid-city-name">
                            ' . get_field("ciudad") . '
                        </div>
                        <div class="grid-cell grid-state-title grid-cell-title">Estado</div>
                        <div class="grid-cell grid-state-name">
                            ' . get_field("estado") . '
                        </div>
                        <div class="grid-cell grid-social-names">
                            ' . $facebookIcon . $instagramIcon
                                    . $tiktokIcon . '
                        </div>
                        <div class="grid-cell grid-codepost-title grid-cell-title">
                        Código Postal
                        </div>
                        <div class="grid-cell grid-codepost-numbers">
                            ' . get_field("codigo_postal") . '
                        </div>
                    </article>
                ';           
            }
    
            $content .= '</section></div>';
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
}

$agentesBusquedaPlugin = new AgentesBusquedaPlugin();

require_once plugin_dir_path(__FILE__) . 'api-endpoints.php';



