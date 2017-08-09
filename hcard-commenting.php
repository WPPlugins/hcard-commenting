<?php
/*
Plugin Name: hCard-Commenting
Plugin URI: http://notizblog.org/projects/wp-hcard-commenting/
Description: This Plugin allows your users to easily fill out your comment forms using an hCard, it should work for the most themes without any changes, if not, simply add &lt;?php hcard_commenting_link() ?&gt; to your theme where you want the link to be displayed.
Author: Matthias Pfefferle
Author URI: http://notizblog.org
Version: 0.7
*/

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );


if (!class_exists('hKit')) {
  require_once('lib/hkit.class.php');
}

function hcard_commenting_link() {
	echo '<a id="hcard_enabled_link" href="http://microformats.org/wiki/hCard">(hCard Enabled)</a>' .
       '<span id="ajax-loader" style="display: none;">Loading hCard</span>';
}

if (isset($wp_version)) {
  add_filter('query_vars', array('hCardCommenting', 'query_vars'));
  add_action('parse_query', array('hCardCommenting', 'parse_hcard'));
  add_action('init', array('hCardCommenting', 'init'));
  //add_filter('generate_rewrite_rules', array('hCardCommenting', 'rewrite_rules'));

  add_action('wp_head', array('hCardCommenting', 'style'), 5);
}

class hCardCommenting {

  function hCardCommenting() { }

  function init() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'openid' );
    wp_enqueue_script( 'hcard-commenting', WP_PLUGIN_URL . '/hcard-commenting/js/hcard-commenting.js.php', array('jquery') );
  }

  /**
   * Define the rewrite rules
   */
  function rewrite_rules($wp_rewrite) {
    $new_rules = array(
      'hcard_url/(.+)' => 'index.php?hcard_url=' . $wp_rewrite->preg_index(1)
    );
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
  }

  function parse_hcard() {
  	global $wp_query, $wp_version;

  	$url = $wp_query->query_vars['hcard_url'];

    $status = '200';
    $ct = 'text/plain';

    if( isset( $url )) {
      if (phpversion() > 5) {
        $hkit = new hKit();
        $result = $hkit->getByURL('hcard', $url);
      } else {
        $hcard = file_get_contents('http://tools.microformatic.com/query/php/hkit/' . urldecode($url));
        $result = unserialize ($hcard);
      }

      $repcard = null;

      if (count($result) != 0) {
        if (count($result) == 1) {
          $repcard = $result[0];
        } else {
          foreach ($result as $card) {
            if (array_search($url, $card) == true || @$card['uid'] == $url) {
              $repcard = $card;
            }
          }
        }

        if (!$repcard) {
          $repcard = $result[0];
        }

        $o = hCardCommenting::create_json($repcard);
        $ct = 'application/x-javascript';
      } else {
        $o = '404 Not Found';
        $status = '404';
      }

      switch($status) {
        case '400':
          $header = "HTTP/1.0 400 Bad Request";
          break;
        case '404':
          $header = "HTTP/1.0 404 Not Found";
          break;
        case '200':
          $header = 'Content-type: '.$ct.' charset=utf-8';
          break;
        default:
          $header = "HTTP/1.0 200 OK";
          break;
      }

      header($header);
      echo $o;
      exit;
    }
  }

  function create_json($hcard) {
    // if there is more than one url
    $hcard["url"] = hCardCommenting::get_url($hcard["url"]);
    // if there is more than one email address, take the first
    $hcard["email"] = is_array($hcard["email"]) ? $hcard["email"][0] : $hcard["email"];

    if ($hcard) {
      $jcard =  '{"vcard": {';
      $jcard .= '"fn": "'.$hcard["fn"].'", ';
      $jcard .= '"email": "'.$hcard["email"].'", ';
      $jcard .= '"url": "'.$hcard["url"].'"}}';
    } else {
      $jcard = null;
    }
    return $jcard;
  }

  function get_url($url) {
    if (is_array($url)) {
      /*foreach ($url as $u) {
        echo $u;
        if (preg_match_all("((http://|https://)[^ ]+)", $u, $match)) {
          return $u;
        }
      }*/
      return $url[0];
    } else {
      return $url;
    }
  }

  /**
   * Include internal stylesheet.
   *
   * @action: wp_head, login_head
   */
  function style() {
    $css_path = WP_PLUGIN_URL . '/hcard-commenting/css/hcard-commenting.css';
    echo '<link rel="stylesheet" type="text/css" href="'.$css_path.'" />';
  }

  /**
   * Add 'hcard_url' as a valid query variables.
   */
  function query_vars($vars) {
    $vars[] = 'hcard_url';

    return $vars;
  }
}
?>
