<?php
/*
Plugin Name: Category Tags Index Widget
Plugin URI: https://blog2k.ru/index_widget
Description: The simplest way to automatically create indexes for your tags or category page as widget on your site
Version: 1.0.0
Author: Evgenii Zhirnov
Author URI: https://blog2k.ru
Text Domain: wp_index_widget
 */

class WP_Index_Widget extends WP_Widget {
  public function __construct() {
    parent::__construct(
      '',
      __('Category or tags index list', 'wp-index-widget'),
      array('description' => __('Index of current category or tag', 'wp-index-widget'))
    );			

    $this->define_hooks();
  }

  function define_hooks() {
    add_action('plugin_loaded', array($this, 'wp_index_widget_load_textdomain'));
	}

  function widget( $args, $instance ) {
    if (is_singular()) {
      return;
    }

    $term     = get_queried_object();
    $isSearch = is_search();

    if ( ! $isSearch && ! is_object( $term ) ) {
      return;
    }

    $posts_per_page = (int) get_option( 'posts_per_page' );

    $isCat = is_category( $term );
    $isTag = is_tag( $term );

    if ( ! ( $isCat || $isTag || $isSearch ) ) {
      return;
    }

    $post_type = $isSearch ? 'any' : 'post';

    // --- Ключ кэша: уникален для категории / метки / поискового запроса ---
    $cache_key = 'posts_' . md5( serialize( array(
      'cat'  => $isCat ? $term->slug : '',
      'tag'  => $isTag ? $term->slug : '',
      'type' => $post_type,
      's'    => $isSearch ? get_query_var( 's' ) : '',
    ) ) );

    $posts = wp_cache_get( $cache_key, 'index-widget' );

    if ( false === $posts ) {
      $posts = get_posts( array(
        'category_name'    => $isCat ? $term->slug : '',
        'tag'              => $isTag ? $term->slug : '',
        'post_type'        => $post_type,
        'post_status'      => 'publish',
        'numberposts'      => 500,
        'suppress_filters' => true,
        'cache_results'    => true,
      ) );
      wp_cache_set( $cache_key, $posts, 'index-widget', HOUR_IN_SECONDS );
    }

    if ( empty( $posts ) ) {
      return;
    }

    // --- Заголовок виджета ---
    $title = '';

    if ( $isCat ) {
      $title = 'Список постов в категории "';

      $parentId = $term->parent;
      if ( $parentId ) {
        $parentTerm = get_term( $parentId );
        if ( $parentTerm && ! is_wp_error( $parentTerm ) ) {
          $parentLink = get_term_link( $parentTerm );
          if ( ! is_wp_error( $parentLink ) ) {
            $title .= '<a href="' . esc_url( $parentLink ) . '">';
            $title .= esc_html( $parentTerm->name );
            $title .= '</a>';
            $title .= esc_attr( ' > ' );
          }
        }
      }
      $title .= esc_html( $term->name );
      $title .= '"';
    }
    elseif ( $isTag ) {
      $title .= sprintf( __( 'Список постов с меткой "%s"', 'index-widget' ), esc_html( $term->name ) );
      if ( ! empty( $term->description ) ) {
        $title .= sprintf( ' (%s)', esc_html( $term->description ) );
      }
    }
    elseif ( $isSearch ) {
      $title .= sprintf(
        __( 'Список постов по запросу "%s"', 'index-widget' ),
        esc_html( get_query_var( 's' ) )
      );
    }

    echo $args['before_widget'];

    echo $args['before_title'];
    echo $title;
    echo $args['after_title'];

    echo '<ul>';

    $current_page = (int) get_query_var( 'paged' );
    if ( 0 === $current_page ) {
      $current_page = 1;
    }

    $i = 0;
    foreach ( $posts as $p ) {
      $li_cls = '';
      $a_cls  = '';

      if ( $i > 0 && 0 === $i % $posts_per_page ) {
        $li_cls = 'class="separator"';
      }

      $url   = get_permalink( $p );
      $ptitle = get_the_title( $p );
      $page  = intdiv( $i, $posts_per_page ) + 1;

      if ( $page === $current_page ) {
        $url   = '#post-' . $p->ID;
        $a_cls = 'class="current-page"';
      }

      echo '<li ' . $li_cls . '><a ' . $a_cls . 'href="' . esc_url( $url ) . '">' . esc_html( $ptitle ) . '</a></li>';
      $i++;
    }

    echo '</ul>';

    echo $args['after_widget'];
  }

  function wp_index_widget_load_textdomain() {
    load_plugin_textdomain(
      'wp-index-widget',
      false,
      dirname(plugin_basename(__FILE__)) . '/languages'
    );

  }
}

function register_index_widget() {
  register_widget('Wp_Index_Widget');
}
add_action('widgets_init', 'register_index_widget');

?>
