<?php

/*
Plugin Name: WP solrsearch
Plugin URI:  http://link to your plugin homepage
Description: This plugin is a basic 
Version:     1.0
Author:      A. Battezzati
Author URI:  
License:     GPL2 etc
License URI: https://link to your plugin license

Copyright YEAR PLUGIN_AUTHOR_NAME (email : your email address)
(Plugin Name) is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
(Plugin Name) is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with (Plugin Name). If not, see (http://link to your plugin license).
*/


function solr_search_template($template)
{
  global $wp_query;
  if ($wp_query->is_search() && $wp_query->is_main_query() && get_query_var('s', false)) {    
    $urlsolr = get_option('myplugin_option_url');
    $limit = get_option('myplugin_option_item_x_page');
    $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
    $search_word = get_query_var('s', false);
    $search_word = trim($search_word);
    $search_word = '(' . implode(' AND ', explode(' ', $search_word)) . ')';
    $query = 'content:' . $search_word;
    $query .= ' OR title:' . $search_word;
    $query = urlencode($query);
    $start = ($pagenum - 1) * $limit;
    $end = $start + $limit;
    //rows=20&end=20&start=0
    $urlsolr .= 'start=' . $start;
    $urlsolr .= '&end=' . $end;
    $urlsolr .= '&rows=' . $limit;
    $urlsolr .= '&q=' . $query;

    $options = [
      "http" => [
        'request_fulluri' => true
      ],
      "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false
      )
    ];

    $user = get_option('myplugin_option_user');
    $passwd = get_option('myplugin_option_password');
    if ($user and $passwd) {
      $auth = base64_encode($user . ':' . $passwd);
      $options['http']['header'] = "Authorization: Basic $auth";
    }

    $context = stream_context_create($options);
    $str_json = @file_get_contents($urlsolr, False, $context);    
    if($str_json === FALSE) { 
      error_log( print_r( $str_json, true ) );
     }
    $json_obj = json_decode($str_json);
    $json_obj = $json_obj->response;
    //print_r($json_obj);                 
    set_query_var('search_solr_result', $json_obj);
    $total = $json_obj->numFound;
    set_query_var('search_solr_total', $total);
    $offset = ($pagenum - 1) * $limit;
    $num_of_pages = ceil($total / $limit);

    $page_links = paginate_links(array(
      'base' => add_query_arg('pagenum', '%#%'),
      'format' => '',
      'prev_text' => __('&laquo;', 'text-domain'),
      'next_text' => __('&raquo;', 'text-domain'),
      'total' => $num_of_pages,
      'current' => $pagenum
    ));
    set_query_var('page_links', $page_links);
  }
  if (!$wp_query->is_search) {
    return $template;
  }
  return dirname(__FILE__) . '/template/solr_search_template.php';
}
add_filter('template_include', 'solr_search_template');


function solr_search_template_formatBytes($size, $precision = 2)
{
  $base = log($size, 1024);
  $suffixes = array('', 'K', 'M', 'G', 'T');

  return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function solr_search_template_truncate($text, $chars = 250)
{
  if (strlen($text) <= $chars) {
    return $text;
  }
  $text = $text . " ";
  $text = substr($text, 0, $chars);
  $text = substr($text, 0, strrpos($text, ' '));
  $text = $text . "...";
  return $text;
}


function myplugin_register_settings()
{
  add_option('myplugin_option_url', '');
  add_option('myplugin_option_user', '');
  add_option('myplugin_option_password', '');
  add_option('myplugin_option_item_x_page', '10');
  register_setting('myplugin_options_group', 'myplugin_option_url', 'myplugin_callback');
  register_setting('myplugin_options_group', 'myplugin_option_user', 'myplugin_callback');
  register_setting('myplugin_options_group', 'myplugin_option_password', 'myplugin_callback');
  register_setting('myplugin_options_group', 'myplugin_option_item_x_page', 'myplugin_callback');
}
add_action('admin_init', 'myplugin_register_settings');

function myplugin_register_options_page()
{
  add_options_page('Solr search options', 'Solr search options', 'manage_options', 'myplugin', 'myplugin_options_page');
}
add_action('admin_menu', 'myplugin_register_options_page');

function myplugin_options_page()
{
?>
  <div>
    <?php screen_icon(); ?>
    <h2>Solr search options</h2>
    <form method="post" action="options.php">
      <?php settings_fields('myplugin_options_group'); ?>
      <table>
        <tr valign="top">
          <th scope="row"><label for="myplugin_option_name">Solr url</label></th>
          <td><input size="100" type="text" id="myplugin_option_name" name="myplugin_option_url" value="<?php echo get_option('myplugin_option_url'); ?>" /> ex: http(s)://[hostname.tld]/solr/[corename]/select? </td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="myplugin_option_user">Username</label></th>
          <td><input type="text" id="myplugin_option_name" name="myplugin_option_user" value="<?php echo get_option('myplugin_option_user'); ?>" /> if solr is under basic auth</td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="myplugin_option_user">Password</label></th>
          <td><input type="text" id="myplugin_option_name" name="myplugin_option_password" value="<?php echo get_option('myplugin_option_password'); ?>" /> if solr is under basic auth </td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="myplugin_option_user">Items x page</label></th>

          <td><input type="text" id="myplugin_option_name" name="myplugin_option_item_x_page" value="<?php echo get_option('myplugin_option_item_x_page'); ?>" /></td>
        </tr>

      </table>
      <?php submit_button(); ?>
    </form>
  </div>
<?php
}

?>
