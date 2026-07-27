<?php
add_action('wp_enqueue_scripts', 'anna_kalynyuk___norml_studio_theme_enqueue_scripts');
function anna_kalynyuk___norml_studio_theme_enqueue_scripts(){
    wp_enqueue_style('anna-kalynyuk---norml-studio-theme-parent-css', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('anna-kalynyuk---norml-studio-theme-child-css', get_stylesheet_uri());
}

function vertical_menu_shortcode($atts) {
    $atts = shortcode_atts(array(
        'menu' => 'Меню',
    ), $atts);

    $menu = wp_nav_menu(array(
        'menu' => $atts['menu'],
        'container' => 'div',
        'container_class' => 'custom-vertical-menu',
        'menu_class' => 'vertical-menu',
        'echo' => false
    ));

    return $menu;
}
add_shortcode('vertical_menu', 'vertical_menu_shortcode');

add_filter( 'gform_form_args', 'filter_gravity_forms_force_ajax' );

/**
 * Modifies the Gravity Forms form settings to set "ajax" to true.
 * 
 * @param array $form_args An array of Form Arguments when adding it to a page/post (like the ID, Title, AJAX or not, etc.).
 * 
 * @return array
 */
function filter_gravity_forms_force_ajax( $form_args ) {
    $form_args['ajax'] = true;
 
    return $form_args;
}




