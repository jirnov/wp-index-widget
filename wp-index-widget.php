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

  function widget($args, $instance) {

    $term = get_queried_object();
    $isSearch = is_search();

    if (!$isSearch and !is_object($term)) {
      return;
    }

    $posts_per_page = get_option('posts_per_page');

    $isCat = is_category($term);

    $isTag = is_tag($term);

    $isVisible = ($isCat or $isTag or $isSearch);

    if (!$isVisible) {
      return;
    }

    $post_type = $isSearch ? 'any' : 'post';

    $posts = get_posts(
      array(
        'category_name' => $isCat ? $term->slug : '',
        'tag' => $isTag ? $term->slug : '',
        'post_type' => $isSearch ? 'any' : 'post',
        'post_status' => 'publish',
        'numberposts' => 500,
        'suppress_filters' => true,
        'cache_results' => true,
      )
    );

    if (empty($posts)) {
      return;
    }

    $title = '';

    if ($isCat) {
      $title = 'Список постов в категории "';

      $parentId = $term->parent;
      if ($parentId) {
        $parentTerm = get_term($parentId);
        $parentLink = get_term_link($parentTerm);
        $title .= '<a href="' . $parentLink . '">';
        $title .= $parentTerm->name;
        $title .= '</a>';
        $title .= esc_attr(" > ");
      }
      $title .= $term->name;
      $title .= '"';
    }
    elseif($isTag) {
      $title .= sprintf(__('Список постов с меткой "%s"', 'index-widget'), $term->name);
      if (!empty($term->description)) {
        $title .= sprintf(' (%s)',  $term->description);
      }
    }
    elseif($isSearch) {
      $title .= sprintf(__('Список постов по запросу "%s"', 'index-widget'), esc_html($_GET['s']));
    }

    echo $args['before_widget'];

    echo $args['before_title'];
    echo $title;
    echo $args['after_title'];

    echo "<ul>";

    $current_page = get_query_var('paged');
    if (0 == $current_page) {
      $current_page++;
    }

    $i = 0;
    foreach($posts as $p) {
      $li_cls = "";
      $a_cls = "";
      if ($i > 0 && 0 == $i % $posts_per_page) {
        $li_cls = 'class="separator"';
      }
      $url = get_permalink($p);
      $title = get_the_title($p);
      $page = intdiv($i, $posts_per_page) + 1;
      if ($page == $current_page) {
        $url = '#post-'.$p->ID;
        $a_cls = 'class="current-page"';
      }

      echo '<li '.$li_cls.'><a '.$a_cls.'href="'.$url.'">'.$title.'</a></li>';
      $i++;
    }
    echo "</ul>";

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
