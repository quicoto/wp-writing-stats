<?php
/*
Plugin Name: Writing Stats
Plugin URI:
Description: Shows stats on your writing
Author: Ricard Torres
Version: 1.0.0
Author URI: http://php.quicoto.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Register a custom menu page.
 */
function wpdocs_register_my_custom_menu_page(){
  add_menu_page(
      __( 'Custom Menu Title', 'textdomain' ),
      'Writing Stats',
      'manage_options',
      'writing-stats',
      'my_custom_menu_page',
      'dashicons-chart-pie',
      99
  );
}
add_action( 'admin_menu', 'wpdocs_register_my_custom_menu_page' );

function _wordCountByYear($year) {
  $data = array();

  $args = array(
    'post_type' => 'post',
    'ignore_sticky_posts' => 1,
    'year'  => $year,
  );
  $the_query = new WP_Query( $args );
  if ( $the_query->have_posts() ) :
    while ( $the_query->have_posts() ) : $the_query->the_post();
        $clean_content = strip_tags(get_the_content());

        // Year long stats
        $data[$year]['id'] = get_the_id();
        $data[$year]['word_count'] = str_word_count($clean_content);

        // Month by Month stats
        // global $wpdb;

        for ($month = 1; month <= 12; $month++) {
          $month_word_count = 0;

          $post_list = get_posts(array(
            'post_type' => 'post',
            'ignore_sticky_posts' => 1,
            'month'  => $month,
          ) );

          foreach ( $post_list as $post ) {
            $clean_content = strip_tags(get_the_content($post->ID));
            $month_word_count += str_word_count($clean_content);
          }

          $data[$year][$month] = $month_word_count;
        }
        wp_reset_postdata();

    endwhile;
  endif;
  wp_reset_postdata();

  return $data;
}

/**
* Display a custom menu page
*/
function my_custom_menu_page(){
  global $wpdb;

  $posts_per_year = $wpdb->get_results(
		"SELECT YEAR(post_date) as year, count(ID) as count
            FROM {$wpdb->posts}
            WHERE post_status = 'publish' AND post_type = 'post' GROUP BY year
            ORDER BY year DESC",
		OBJECT_K
  );


   ?>
   <div class="wrap">

  <pre>
    <ul>
      <?php
        // Get all the posts for each year
        foreach ( $posts_per_year as $year_object ) {
          echo '<h3>' . $year_object->year . '</h3>';
          echo '<li>';
            print_r(_wordCountByYear($year_object->year));
          echo '</li>';
        } ?>
    </ul>
</pre>

   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:['corechart']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ["Month", "Words"],
          ["January", 2000],
          ["February", 3000],
          ["Gold", 1500],
          ["Platinum", 4000]
        ]);

        var view = new google.visualization.DataView(data);
        view.setColumns([0, 1,
                        { calc: "stringify",
                          sourceColumn: 1,
                          type: "string",
                          role: "annotation" }]);

        var options = {
          backgroundColor: '#f1f1f1',
          bar: {groupWidth: "95%"},
          legend: { position: "none" },
        };
        var chart = new google.visualization.ColumnChart(document.getElementById("columnchart_values"));
        chart.draw(view, options);
    }
    </script>

    <section>
      <h2>Words written in <?=date("Y")?></h2>
      <div id="columnchart_values" style="width: 100%; height: 300px;"></div>
    </section>

   </div>
   <?php
}