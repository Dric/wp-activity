<?php
/*
    Plugin Name: WP-Activity
    Plugin URI: http://www.driczone.net/blog/plugins/wp-activity
    Description: Monitor and display blog members activity ; track and blacklist unwanted login attemps.
    Author: Dric
    Version: 2.0
    Author URI: http://www.driczone.net
*/

/*  Copyright 2009-2012 Dric  (email : cedric@driczone.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// let's initializing all vars

$act_list_limit = 50; //Change this if you want to display more than 50 items per page in admin list
$strict_logs = false; //If you don't want to keep track of posts authors changes, set this to "true"
$no_admin_mess = false; //If you don't want to get annoyed by admin panel additions
$act_user_filter_max = 25; //If you have less than 25 users (default value), it will display a select field with all users instead of a search field in activity log filter
$act_plugin_version = "2.0"; //don't modify this !

$options_act = get_option('act_settings');
if ( ! defined( 'WP_CONTENT_URL' ) ) {
    if ( defined( 'WP_SITEURL' ) ) {
        define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
    } else {
        define( 'WP_CONTENT_URL', get_bloginfo('wpurl') . '/wp-content' );
    }
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define('ACT_DIR', dirname(plugin_basename(__FILE__)));
define('ACT_URL', WP_CONTENT_URL . '/plugins/' . ACT_DIR . '/');

//Plugin can be translated, just put the .mo language file in the /lang directory
load_plugin_textdomain('wp-activity', ACT_URL . 'lang/', ACT_DIR . '/lang/');


add_action('init', 'act_init_process');

function act_init_process() {
  global $act_plugin_version, $options_act;
  if ($options_act['act_version'] != $act_plugin_version) {
      act_install();
  }
  if ($options_act['act_feed_display']) {
    require_once(WP_PLUGIN_DIR.'/'.ACT_DIR.'/wp-activity-feed.php');
    add_feed('act-feed', 'act_feed');
  }
  if (isset($_POST['act_export'])) {
    require_once(WP_PLUGIN_DIR.'/'.ACT_DIR.'/wp-act-export.php');
    act_export();
  }
}

function act_desactive() {
  flush_rewrite_rules();
  wp_clear_scheduled_hook('act_cron_daily');
}

function act_install() {
  global $wpdb, $act_plugin_version, $options_act;
  wp_schedule_event(time(), 'daily', 'act_cron_daily');
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  $table = $wpdb->prefix."activity";
  $act_structure = "CREATE TABLE `".$table."` (
                   `id` int(9) NOT NULL auto_increment,
                   `user_id` bigint(20) NOT NULL,
                   `act_type` varchar(20) NOT NULL,
                   `act_date` datetime default NULL,
                   `act_params` text,
                   UNIQUE KEY `id` (`id`),
                   KEY `user_id` (`user_id`),
                   KEY `act_date` (`act_date`)
                   );";
  dbDelta($act_structure);
  $new_options_act['act_prune'] = '5000';
  $new_options_act['act_feed_display'] = false;
  $new_options_act['act_date_format'] = 'yyyy/mm/dd';
  $new_options_act['act_date_relative']= true;
  $new_options_act['act_connect']= true;
  $new_options_act['act_profiles']= true;
  $new_options_act['act_posts']= true;
  $new_options_act['act_comments']= true;
  $new_options_act['act_links']= true;
  $new_options_act['act_feed_connect']= false;
  $new_options_act['act_feed_profiles']= true;
  $new_options_act['act_feed_posts']= true;
  $new_options_act['act_feed_comments']= true;
  $new_options_act['act_feed_links']= true;
  $new_options_act['act_icons']= 'g';
  $new_options_act['act_old']= true;
  $new_options_act['act_prevent_priv']= false;
  $new_options_act['act_log_failures']= false;
  $new_options_act['act_author_path']= 'author';
  $new_options_act['act_blacklist_on']= false;
  $new_options_act['act_auto_bl']= false;
  $new_options_act['act_auto_bl_n']= '5';
  $new_options_act['act_bl_wplog']= true;
  $new_options_act['act_refresh']= false;
  $new_options_act['act_r_interval']= '1800';
  $new_options_act['act_version'] = $act_plugin_version;
  add_option('act_settings', $new_options_act);

  if ($options_act['act_version'] != $act_plugin_version) {
      act_desactive();
      if (version_compare($options_act['act_version'], '1.4', '<')) {
          $options_act['act_author_path']= 'author';
      }
      if (version_compare($options_act['act_version'], '1.7', '<')) {
          $options_act['act_blacklist_on']= false;
          $options_act['act_bl_wplog']= true;
      }
      if (version_compare($options_act['act_version'], '1.8', '<')) {
          $options_act['act_auto_bl']= false;
          $options_act['act_auto_bl_n']= '5';
      }
      if (version_compare($options_act['act_version'], '2.0', '<')) {
          $options_act['act_refresh']= false;
          $options_act['act_r_interval']= '1800';
      }
      $options_act['act_version'] = $act_plugin_version;
      update_option('act_settings', $options_act);
  }
  flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'act_install' );
register_deactivation_hook(__FILE__, 'act_desactive');
add_action('act_cron_daily', 'act_cron');

function act_cron($prune_limit='') {
  global $wpdb, $options_act, $plugin_page;
  if ($prune_limit == '') {
    $prune_limit = $options_act['act_prune'];
  } else {
    $ret = true;
  }
  $act_count = $wpdb->get_var("SELECT count(ID) FROM ".$wpdb->prefix."activity");
  $act_delete = $act_count - $prune_limit;
  if ($act_delete > 0) {
    if ($ret == true) {
      if ($wpdb->query("DELETE FROM ".$wpdb->prefix."activity ORDER BY id ASC LIMIT ".$act_delete)) {
          return true;
      } else {
          return false;
      }
    } else {
      $wpdb->query("DELETE FROM ".$wpdb->prefix."activity ORDER BY id ASC LIMIT ".$act_delete);
    }
  }
}

//attaching to action hooks
if ($options_act['act_connect']) {
  add_action('wp_login', 'act_session', 10, 2);
  add_action('auth_cookie_valid', 'act_session', 10, 2);
  add_action('wp_logout', 'act_reinit');
}
if ($options_act['act_profiles'] ) {
  add_action('profile_update', 'act_profile_edit');
  add_action('user_register', 'act_new_user');
}
if ($options_act['act_posts']) {
  add_action('publish_post', 'act_post_add');
  add_action('post_updated', 'act_post_update');
  add_action('delete_post', 'act_post_del');
}
if ($options_act['act_comments']) {
  add_action('comment_post', 'act_comment_add');
  add_action('edit_comment', 'act_comment_edit');
  add_action('delete_comment', 'act_comment_del');
}
if ($options_act['act_links']) {
  add_action('add_link', 'act_link_add');
}
if ($options_act['act_log_failures'] ) {
  add_action('wp_login_failed', 'act_login_failed');
}

function act_header() {
  $altcss = TEMPLATEPATH.'/wp-activity.css';
  echo '<link type="text/css" rel="stylesheet" href="';
  if (@file_exists($altcss)) {
      echo get_bloginfo('stylesheet_directory').'/';
  } else {
      echo ACT_URL;
  }
  echo 'wp-activity.css" />';

}
add_action('wp_head', 'act_header');

function act_profile_option() {
  global $wpdb, $user_ID, $options_act;
  $act_private = get_user_meta($user_ID, 'act_private');
  ?>
  <h3><?php _e('Activity events', 'wp-activity');
  ?></h3>
  <table>
  <tr>
  <th><?php _e('Hide my activity :', 'wp-activity');
  ?></th>
  <td><input type="checkbox" id="act_private" name="act_private" <?php if ($act_private) {
      echo 'checked="checked"';
  }?> value="true" /> <?php _e('If selected, this option makes you become invisible in activity events.', 'wp-activity');
  ?></td>
  </tr>
  </table>
  <?php
}
if (!$options_act['act_prevent_priv']) {
  add_action('show_user_profile', 'act_profile_option');
}

function act_real_ip() {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from share internet
      $ip=$_SERVER['HTTP_CLIENT_IP'];
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //to check ip is pass from proxy
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  else {
      $ip=$_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}

function act_login_failed($act_user='') {
  global $wpdb, $options_act;
  if ($act_user) {
    $user_ID = 1; //event has to be linked to a wp user.
    $no_add = false;
    $act_time=current_time('mysql', true);
    $ip = act_real_ip();
    $bwps = get_option("BWPS_options"); //Compatibility check for Better-WP-Security Plugin that do wp_login_failed action hook even if login is successful...
    if (!empty($bwps)){
      $sql = "SELECT user_id, act_params FROM ".$wpdb->prefix."activity WHERE act_type = 'CONNECT' AND act_date <= DATE_SUB(NOW(), INTERVAL 2 SECOND)";
      $act_prec_res = $wpdb->get_results($sql);
      foreach ($act_prec_res as $act_prec_id){
        $act_prec = get_userdata($act_prec_id->user_id);
        if ($act_user == $act_prec->display_name and $act_prec_id->act_params == $ip){
          $no_add = true;
        }
      }
    }
    if (!$no_add){
      $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'LOGIN_FAIL', '".$act_time."', '".$act_user."###".$ip."')");
      if ($options_act['act_auto_bl'] and $options_act['act_blacklist_on']){
        $sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."activity WHERE act_type='LOGIN_FAIL' AND SUBSTRING_INDEX(act_params, '###', -1) = '".$ip."' AND act_date >= DATE_SUB(NOW(), INTERVAL 2 DAY)";
        $act_count_attempts = $wpdb->get_var($sql);
        if ($act_count_attempts > $options_act['act_auto_bl_n']){
          $act_bl_ip_array = explode("\n", trim($options_act['act_blacklist']));
          $no_add_n = false;
          foreach ($act_bl_ip_array as $act_bl_ip) {
            if ($act_bl_ip == $ip){$no_add_n = true;}
          }
          if (!$no_add_n){
            if ( substr($options_act['act_blacklist'], -2) != "\n"){
              $options_act['act_blacklist'] .= "\n";
            }
            $options_act['act_blacklist'] .= $ip;
            update_option('act_settings', $options_act);
          }
        }
      }
    }
  }
}

function act_profile_update() {
  global $user_ID, $_POST;
  update_usermeta($user_ID,'act_private',isset($_POST['act_private']) ? true : false);
}
add_action('personal_options_update', 'act_profile_update');

function act_session($arg='', $userlogin='') {
  global $wpdb, $options_act;
  if ( is_numeric($userlogin->ID) ) {
      $user_ID = $userlogin->ID;
  } else {
      $userlogin = get_user_by('login', $arg);
      if ($userlogin->ID) {
          $user_ID = $userlogin->ID;
      } else {
          $user_ID = '';
      }
  }
  if (!empty($user_ID) and !get_usermeta($user_ID, 'act_private') and !$_COOKIE['act_logged']) {
    $ip = act_real_ip();
    $act_time=current_time('mysql', true);
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'CONNECT', '".$act_time."', '".$ip."')");
    setcookie('act_logged',time());
  }
}
function act_reinit() {
  if ($_COOKIE['act_logged']) {
      setcookie ("act_logged", "", time() - 3600);
  }
}

function act_new_user($user_id) {
  global $wpdb, $options_act;
  $act_time=current_time('mysql', true);
  $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date) VALUES($user_id, 'NEW_USER', '".$act_time."')");
}

function act_profile_edit($act_user) {
    global $wpdb, $user_ID, $options_act;
    if (!get_usermeta($user_ID, 'act_private')) {
        $act_time=current_time('mysql', true);
        $sql="INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date) VALUES($user_ID, 'PROFILE_EDIT', '".$act_time."')";
        $wpdb->query($sql);
    }
}

function act_post_del($act_post){
  global $wpdb, $user_ID, $options_act;
  $act_post_meta=get_post($act_post);
  if ($act_post_meta and $act_post_meta->post_status != 'inherit'){
      $wpdb->query("UPDATE ".$wpdb->prefix."activity SET act_params = '".$act_post_meta->post_title."' WHERE act_type IN ('POST_ADD','POST_EDIT', 'COMMENT_ADD') and act_params = ".$act_post);
    if (!get_usermeta($user_ID, 'act_private')) {
      $act_time=current_time('mysql', true);
      $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'POST_DEL', '".$act_time."', '".$act_post_meta->post_title."###".$act_post."')");
    }
  }
}

function act_post_update($act_post){
  global $wpdb, $user_ID, $options_act;
  if (!get_usermeta($user_ID, 'act_private')) {
    $act_time=current_time('mysql', true);
    $act_post_meta = get_post($act_post);
    if ($act_post_meta->status_post == 'publish'){
      $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'POST_EDIT', '".$act_time."', ".$act_post.")");
    }
  }
}

function act_post_add($act_post) {
    global $wpdb, $user_ID, $options_act;
    if (!get_usermeta($user_ID, 'act_private')) {
        $act_time=current_time('mysql', true);
        $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'POST_ADD', '".$act_time."', $act_post)");
    }
}

function act_comment_add($act_comment) {
    global $wpdb, $user_ID, $options_act;
    if (!get_usermeta($user_ID, 'act_private') and $user_ID <> 0) {
        $act_time=current_time('mysql', true);
        $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID,'COMMENT_ADD', '".$act_time."', $act_comment)");
    }
}

function act_comment_edit($act_comment){
  global $wpdb, $user_ID, $options_act;
  if (!get_usermeta($user_ID, 'act_private')) {
    $act_time=current_time('mysql', true);
    $act_comment_meta = get_comment($act_comment);
    $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'COMMENT_EDIT', '".$act_time."', $act_comment)");
  }
}

function act_comment_del($act_comment) {
  global $wpdb, $user_ID, $options_act;
  $act_comment_meta=get_comment($act_comment);
  if ($act_comment_meta->comment_approved != 'spam' ){
    $act_post_meta = get_post($act_comment_meta->comment_post_ID );
    $wpdb->query("UPDATE ".$wpdb->prefix."activity SET act_params = '".$act_comment_meta->comment_post_ID."###$act_comment' WHERE act_type IN ('COMMENT_ADD', 'COMMENT_EDIT') and act_params = ".$act_comment);
    if (!get_usermeta($user_ID, 'act_private')) {
      $act_time=current_time('mysql', true);
      $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'COMMENT_DEL', '".$act_time."', '".$act_post_meta->post_title."###".$act_comment."###".$act_comment_meta->comment_post_ID."')");
    }
  }
}

function act_link_add($act_link) {
    global $wpdb, $user_ID, $options_act;
    if (!get_usermeta($user_ID, 'act_private')) {
        $act_time=current_time('mysql', true);
        $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES($user_ID, 'LINK_ADD', '".$act_time."', $act_link)");
    }
}

function act_last_connect($act_user='', $act_notext='') {
  global $wpdb, $options_act, $user_ID;
  if (!$act_user) {
    $act_user = $user_ID;
  }
  if ($options_act['act_connect'] and !get_usermeta($act_user, 'act_private')) {
    $act_last_connect = $wpdb->get_var("SELECT MAX(act_date) FROM ".$wpdb->prefix."activity WHERE user_id = '".$act_user."'");
    if ($act_notext <> 'no_text'){
      echo __("Last logon :", 'wp-activity')." ";
    }
    echo nicetime($act_last_connect);
  }
}

//blacklist baby !
function act_blacklist() {
    global $options_act, $wpdb, $pagenow;
    if ((($options_act['act_bl_wplog'] and $pagenow == 'wp-login.php') or !$options_act['act_bl_wplog']) and !is_user_logged_in()) {
        $act_bl_ip_array = explode("\n", trim($options_act['act_blacklist']));
        $act_client_ip = act_real_ip();
        foreach ($act_bl_ip_array as $act_bl_ip) {
            $act_bl_ip = str_replace(".", "\.", $act_bl_ip);
            $act_bl_ip = str_replace("*", "[0-9\.]*", $act_bl_ip);
            $act_bl_ip = "/^" . trim($act_bl_ip) . "$/";
            if (preg_match($act_bl_ip, $act_client_ip)) {
                $act_time=current_time('mysql', true);
                $wpdb->query("INSERT INTO ".$wpdb->prefix."activity (user_id, act_type, act_date, act_params) VALUES(1, 'ACCESS_DENIED', '".$act_time."', '".$act_client_ip."')");
                Header("HTTP/1.1 403 Forbidden");
                die('403 Forbidden');
            }
        }
    }
}
if ($options_act['act_blacklist_on'] and !$_COOKIE['act_logged']) {
    add_action('init', 'act_blacklist', 1);
}

//display activity in frontend
function act_stream_user($act_user='') {
    global $options_act, $user_ID;
    if (!$act_user) {
        $act_user = $user_ID;
    }
    if (!get_usermeta($act_user, 'act_private')) {
        act_stream('-1', '', true, $act_user);
    }
}

function act_stream_shortcode ($attr) {
    $attr = shortcode_atts(array('number'   => '-1',
                                 'title'    => '',), $attr);
		if ( in_the_loop() ) {
			return act_stream($attr['number'], $attr['title'], true, '');
		} else {
			return null;
		}
}

add_shortcode('ACT_STREAM', 'act_stream_shortcode');

function act_stream($act_number='30', $act_title='', $archive = false, $act_user = '', $act_width = '') {
  global $options_act;
  if ($options_act['act_refresh']){
    act_ajax_javascript($act_number, $options_act['act_r_interval']);
  }
  if ($act_title == '') {
    $act_title= __("Recent Activity", 'wp-activity');
  }
  $act_title .= act_feed_link();
  if ($options_act['act_page_link'] and !$archive) {
    $act_title .= ' <a href="'.get_page_link($options_act['act_page_id']).'" title="'.sprintf(__('%s activity archive', 'wp-activity'),get_bloginfo('name')).'">'.__('Archives', 'wp-activity').'</a>';
  }

  echo '<h2>'.$act_title.'</h2>';

  echo '<div id="act_wrap" ';
  if (!empty($act_width)){
    echo 'style="width:'.$act_width.'px" ';
  }
  echo '>';
  if ($archive == false) {
      echo '<ul id="activity">';
  } else {
      echo '<ul id="activity-archive">';
  }
  act_stream_common($act_number, $act_user, $archive);
  echo '</ul></div>';
}

function act_feed_link(){
  global $options_act;
  $return = '';
  $perm = get_option( 'permalink_structure' );
  if ($options_act['act_feed_display']) {
    if (!empty($perm)){
      $act_feed = get_bloginfo('wpurl').'/act-feed';
    }else{
      $act_feed = get_bloginfo('wpurl').'/?feed=act-feed';
    }
    $return = ' <a href="'.$act_feed.'" title="'.sprintf(__('%s activity RSS Feed', 'wp-activity'),get_bloginfo('name')).'"><img src="'.WP_PLUGIN_URL.'/wp-activity/img/rss.png" alt="" /></a>';
  }
  return $return;
}

function act_ajax_refresh(){
  if ( isset($_GET['act_action']) && $_GET['act_action'] == 'act_refresh' ) {
    $act_number = $_GET['act_number'];
    act_stream_common($act_number);
    die();
  }
}
if ($options_act['act_refresh']){
  add_action('init', 'act_ajax_refresh', 1);
}

function act_ajax_javascript($act_number = '30', $act_refresh_interval = '1800'){
  echo '<script type="text/javascript">
      jQuery(document).ready(function($){
  var load_activity = function() {
    $.ajax({
      type : "GET",
      url : "index.php",
      data : { act_action : "act_refresh",
               act_number : '.$act_number.',
             },
      beforeSend: function() {$("#activity").animate({opacity : "toggle"}, "fast");},
      success : function(response){
        // the server has finished executing PHP and has returned something, so display it!
        $("#activity").html(response);
        $("#activity").animate({opacity : "toggle"}, "slow"); //animation
      }
    });
  };
  var act_refreshId = setInterval(load_activity, '.($act_refresh_interval*1000).');
  $.ajaxSetup({ cache: false });
});</script>';
}

/*
--- display activity in frontend ---
* $act_number = -1 : no limit
* $act_title : title of the box
* $archive : if true, display activity on a page without box
* $act_user : if user id specified, return user's activity only
*/
function act_stream_common($act_number='30', $act_user = '', $archive = false) {
  global $wpdb, $options_act, $user_ID;
  $wp_url = get_bloginfo('wpurl');
  $act_old_class = '';
  $act_old_flag = -1;
  $sql  = "SELECT u.display_name as display_name, user_nicename, u.id as id, act_type, act_date, act_params, a.id as act_id, a.user_id as user_id FROM ".$wpdb->prefix."activity AS a, ".$wpdb->users." AS u WHERE a.user_id = u.id";
  if ($act_user != '') {
      $sql .= " AND a.user_id = '".$act_user."'";
  } else {
      $sql .= " AND act_type NOT IN ('LOGIN_FAIL', 'ACCESS_DENIED')";
  }
  $sql .= " ORDER BY act_date DESC LIMIT ".$act_number;
  if ( $act_logins = $wpdb->get_results( $sql)) {
    foreach ( (array) $act_logins as $act ) {
      if ($options_act['act_old'] and $act_old_flag > 0 and !$archive) {
        $act_old_class = 'act-old';
      } else {
        $act_old_class = '';
      }
      if (!$act_logged[$act->user_id]) {
        $act_logged[$act->user_id]="2029-01-01 00:00:01"; //hope this plugin won't be used anymore at this date...
      }
      if (((strtotime($act_logged[$act->user_id]) - strtotime($act->act_date)) > 60 AND $act->act_type == 'CONNECT') OR $act->act_type != 'CONNECT') {
        echo '<li class="login '.$act_old_class.'">';
        if ($options_act['act_icons']!= 'n') {
          if ($options_act['act_icons']== 'a' and ($act->act_type == 'CONNECT' or $act->act_type == 'PROFILE_EDIT' or $act->act_type == 'NEW_USER')) {
            echo get_avatar( $act->user_id, '16'); ;
          } else {
            $act_icon = WP_PLUGIN_DIR.'/wp-activity/img/'.$act->act_type.'.png';
            if (@file_exists($act_icon)) {
              echo '<img class="activity_icon" alt="" src="'.WP_PLUGIN_URL.'/wp-activity/img/'.$act->act_type.'.png" />';
            }else{
              echo '<img class="activity_icon" alt="" src="'.WP_PLUGIN_URL.'/wp-activity/img/default.png" />';
            }
          }
        }
        if ($act->user_id == $user_ID and $options_act['act_old'] and $act->act_type == 'CONNECT') {
          $act_old_flag++;
        }
        //format event display
        $act_prep = act_prepare($act, 'frontend');
        echo $act_prep['user'].' '.$act_prep['text'].' '.$act_prep['params'].' <span class="activity_date">'.$act_prep['date'].'</span>';
        echo '</li>';
      }
      $act_logged[$act->user_id] = $act->act_date;
    }
  }
}

/*
--- prepare and format event display ---
* $act_raw : object
* Row returned by sql query (u.display_name as display_name,
*                            user_nicename,
*                            u.id as id,
*                            act_type,
*                            act_date,
*                            act_params,
*                            a.id as act_id,
*                            a.user_id as user_id)
* $act_disp : can be 'frontend', 'admin', 'csv' or 'rss'
* Returns array ('class', 'user', 'text', 'params', 'date', 'type')
*/
function act_prepare($act_raw, $act_disp){
  global $options_act, $wpdb;
  $wp_url = get_bloginfo('wpurl');
  switch ($act_disp) {
    case 'admin' :
    case 'csv' :
      $act_date = nicetime($act_raw->act_date, true);
      $act_user = $act_raw->display_name;
      break;
    case 'rss':
      $act_date = gmdate('r', strtotime($act_raw->act_date));
      $act_user = '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act_raw->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act_raw->display_name.'</a>';
      break;
    case 'frontend':
    default:
      $act_date = nicetime($act_raw->act_date);
      $act_user = '<a href="'.$wp_url.'/'.$options_act['act_author_path'].'/'.$act_raw->user_nicename.'" title="'.__('View Profile', 'wp-activity').'">'.$act_raw->display_name.'</a>';
      break;
  }
  switch ($act_raw->act_type) {
    case 'CONNECT':
      ($act_disp == 'admin' or $act_disp == 'csv')? $act_params = $act_raw->act_params : $act_params = '';
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('has logged in.', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    case 'LOGIN_FAIL':
      $act_post_tab = explode ("###", $act_raw->act_params);
      $act_done = array(
                     'class'  => 'activity_warning',
                     'user'   => $act_post_tab[0],
                     'text'   => '',
                     'params' => $act_post_tab[1]
                      );
      break;
    case 'ACCESS_DENIED':
      $act_post_tab = explode ("###", $act_raw->act_params);
      $act_done = array(
                     'class'  => 'activity_warning',
                     'user'   => '',
                     'text'   => '',
                     'params' => $act_post_tab[0]
                      );
      break;
    case 'POST_ADD':
      if (is_numeric($act_raw->act_params)){
        $act_post=get_post($act_raw->act_params);
        if ($act_raw->id != $act_post->post_author and !$strict_logs) { //this is a check if post author has been changed in admin post edition.
          $sql = "UPDATE ".$wpdb->prefix."activity SET user_id = '".$act_post->post_author."' WHERE id = '".$act_raw->id."'";
          $wpdb->query( $sql);
        }
        if($act_disp == 'csv'){
          $act_params = $act_post->post_title;
        }else{
          $act_params = '<a href="'.get_permalink($act_post->ID).'">'.$act_post->post_title.'</a>';
        }
      }else{
        $act_params = $act_raw->act_params;
      }
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('published', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    case 'POST_EDIT':
      if (is_numeric($act_raw->act_params)){
        $act_post=get_post($act_raw->act_params);
        if($act_disp == 'csv'){
          $act_params = $act_post->post_title;
        }else{
          $act_params = '<a href="'.get_permalink($act_post->ID).'">'.$act_post->post_title.'</a>';
        }
      }else{
        $act_params = $act_raw->act_params;
      }
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('edited', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    case 'POST_DEL':
      $act_post_tab = explode ("###", $act_raw->act_params);
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('deleted', 'wp-activity'),
                     'params' => $act_post_tab[0]
                      );
      break;
    case 'COMMENT_ADD':
      if (is_numeric($act_raw->act_params)){
        $act_comment=get_comment($act_raw->act_params);
        $act_post=get_post($act_comment->comment_post_ID);
        if($act_disp == 'csv'){
          $act_params = $act_post->post_title;
        }else{
          $act_params = '<a href="'.get_permalink($act_post->ID).'#comment-'.$act_comment->comment_ID.'">'.$act_post->post_title.'</a>';
        }
      }else{
        $act_comment_tab = explode ("###", $act_raw->act_params);
        if (isset($act_comment_tab[1])){
          $act_post=get_post($act_comment_tab[0]);
          if($act_disp == 'csv'){
            $act_params = $act_post->post_title;
          }else{
            $act_params = '<a href="'.get_permalink($act_post->ID).'">'.$act_post->post_title.'</a>';
          }
        }else{
          $act_params = $act_raw->act_params;
        }
      }
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('commented', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    case 'COMMENT_EDIT':
      if (is_numeric($act_raw->act_params)){
        $act_comment=get_comment($act_raw->act_params);
        $act_post=get_post($act_comment->comment_post_ID);
        if($act_disp == 'csv'){
          $act_params = $act_post->post_title;
        }else{
          $act_params = '<a href="'.get_permalink($act_post->ID).'#comment-'.$act_comment->comment_ID.'">'.$act_post->post_title.'</a>';
        }
      }else{
        $act_comment_tab = explode ("###", $act_raw->act_params);
        if (isset($act_comment_tab[1])){
          $act_post=get_post($act_comment_tab[0]);
          if($act_disp == 'csv'){
            $act_params = $act_post->post_title;
          }else{
            $act_params = '<a href="'.get_permalink($act_post->ID).'">'.$act_post->post_title.'</a>';
          }
        }else{
          $act_params = $act_raw->act_params;
        }
      }
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('edited comment on', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    case 'COMMENT_DEL':
      $act_post_tab = explode ("###", $act_raw->act_params);
      if ($act_post = get_post($act_post_tab[2]) and $act_disp != 'csv'){
        $act_params = '<a href="'.get_permalink($act_post->ID).'">'.$act_post->post_title.'</a>';
      }else{
        $act_params = $act_post->post_title;
      }
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('deleted comment on', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    case 'NEW_USER':
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('has registered.', 'wp-activity'),
                     'params' => $act_raw->act_params
                      );
      break;
    case 'PROFILE_EDIT':
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('has updated his profile.', 'wp-activity'),
                     'params' => $act_raw->act_params
                      );
      break;
    case 'LINK_ADD':
      $act_link = get_bookmark($act_raw->act_params);
      if($act_disp == 'csv'){
        $act_params = $act_link->link_name;
      }else{
        $act_params = '<a href="'.$act_link->link_url.'" title="'.$act_link->link_description.'" target="'.$act_link->link_target.'">'.$act_link->link_name.'</a>.';
      }
      $act_done = array(
                     'class'  => '',
                     'user'   => $act_user,
                     'text'   => __('has added a link to', 'wp-activity'),
                     'params' => $act_params
                      );
      break;
    default:
      break;
  }
  $act_done['date'] = $act_date;
  $act_done['type'] = $act_raw->act_type;
  return $act_done;
}

function nicetime($posted_date, $admin=false, $nohour=false) {
    // Adapted for something found on Internet, but I forgot to keep the url...
    $act_opt=get_option('act_settings');
    $date_relative = $act_opt['act_date_relative'];
    $date_format = $act_opt['act_date_format'];
    $gmt_offset = get_option('gmt_offset');
    if (empty($gmt_offset) and $gmt_offset != 0){
      $timezone = get_option('timezone_string');
      $gmt = date_create($posted_date, timezone_open($timezone));
      $gmt_offset = date_offset_get($gmt) / 3600;
    }
    /*$cur_time_gmt = current_time('timestamp', true);
    $posted_date = gmdate("Y-m-d H:i:s", strtotime($posted_date) + ($gmt_offset * 3600));
    $in_seconds = strtotime($posted_date);*/
    $cur_time_gmt = time();
    $in_seconds = strtotime($posted_date);
    $posted_date = gmdate("Y-m-d H:i:s", strtotime($posted_date) + ($gmt_offset*3600));
    $relative_date = '';
    $diff = $cur_time_gmt - $in_seconds;
    $months = floor($diff/2592000);
    $diff -= $months*2419200;
    $weeks = floor($diff/604800);
    $diff -= $weeks*604800;
    $days = floor($diff/86400);
    $diff -= $days*86400;
    $hours = floor($diff/3600);
    $diff -= $hours*3600;
    $minutes = floor($diff/60);
    $diff -= $minutes*60;
    $seconds = $diff;
    if ($months>0 or !$date_relative or $admin) {
        // over a month old, just show date
        if ((!$date_relative or $admin) and !$nohour) {
            $h = substr($posted_date,10);
        } else {
            $h = '';
        }
        switch ($date_format) {
        case 'dd/mm/yyyy':
            return substr($posted_date,8,2).'/'.substr($posted_date,5,2).'/'.substr($posted_date,0,4).$h;
            break;
        case 'mm/dd/yyyy':
            return substr($posted_date,5,2).'/'.substr($posted_date,8,2).'/'.substr($posted_date,0,4).$h;
            break;
        case 'yyyy/mm/dd':
        default:
            return substr($posted_date,0,4).'/'.substr($posted_date,5,2).'/'.substr($posted_date,8,2).$h;
            break;
        }
    } else {
        if ($weeks>0) {
            // weeks and days
            $relative_date .= ($relative_date?', ':'').$weeks.' '.($weeks>1? __('weeks', 'wp-activity'):__('week', 'wp-activity'));
            $relative_date .= $days>0?($relative_date?', ':'').$days.' '.($days>1? __('days', 'wp-activity'):__('day', 'wp-activity')):'';
        }
        elseif ($days>0) {
            // days and hours
            $relative_date .= ($relative_date?', ':'').$days.' '.($days>1? __('days', 'wp-activity'):__('day', 'wp-activity'));
            $relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.($hours>1? __('hours', 'wp-activity'):__('hour', 'wp-activity')):'';
        }
        elseif ($hours>0) {
            // hours and minutes
            $relative_date .= ($relative_date?', ':'').$hours.' '.($hours>1? __('hours', 'wp-activity'):__('hour', 'wp-activity'));
            $relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.($minutes>1? __('minutes', 'wp-activity'):__('minute', 'wp-activity')):'';
        }
        elseif ($minutes>0) {
            // minutes only
            $relative_date .= ($relative_date?', ':'').$minutes.' '.($minutes>1? __('minutes', 'wp-activity'):__('minute', 'wp-activity'));
        }
        else {
            // seconds only
            $relative_date .= ($relative_date?', ':'').$seconds.' '.($seconds>1? __('seconds', 'wp-activity'):__('second', 'wp-activity'));
        }
    }
    // show relative date and add proper verbiage
    return sprintf(__('%s ago', 'wp-activity'), $relative_date);
}
if (is_admin()) {
    require_once(WP_PLUGIN_DIR.'/'.ACT_DIR.'/wp-act-admin.php');
}

add_action( 'widgets_init', 'WPActivity_load_widgets' );

function WPActivity_load_widgets() {
    register_widget('WpActivity_Widget');
    register_widget('WpActivity_user_Widget');
}
class WpActivity_Widget extends WP_Widget {
  function WpActivity_Widget() {
      /* Widget settings. */
      $widget_ops = array( 'classname' => 'wp-activity', 'description' => __('Display a stream of registered users events', 'wp-activity') );

      /* Widget control settings. */
      $control_ops = array( 'height' => 350, 'id_base' => 'wp-activity' );

      /* Create the widget. */
      $this->WP_Widget( 'wp-activity', __('Wp-Activity Widget', 'wp-activity'), $widget_ops, $control_ops );
  }

  function widget( $args, $instance ) {
      extract( $args );
      $title = apply_filters('widget_title', $instance['title'] );
      $number = $instance['number'];
      $width = $instance['width'];
      echo $before_widget;
      if ( $title )
          $title =  $before_title . $title . $after_title;
      act_stream($number, $title, false, '', $width);
      echo $after_widget;
  }

  function update( $new_instance, $old_instance ) {
      $instance = $old_instance;
      $instance['title'] = strip_tags( $new_instance['title'] );
      $instance['number'] = $new_instance['number'];
      $instance['width'] = $new_instance['width'];
      return $instance;
  }

  function form( $instance ) {
      $options_act = get_option('act_settings');
      $defaults = array( 'title' => __('Recent Activity', 'wp-activity'), 'number' => '30', 'width' => '350');
      $instance = wp_parse_args( (array) $instance, $defaults );
      ?>
      <p>
        <label for ="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title :', 'wp-activity'); ?></label>
        <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
      </p>
      <p>
        <label for ="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Events number :', 'wp-activity'); ?></label>
        <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $instance['number']; ?>" style="width:95%;" />
      </p>
      <p>
        <label for ="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e('Widget width (px) :', 'wp-activity'); ?></label>
        <input id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" value="<?php echo $instance['width']; ?>" style="width:95%;" />
      </p>
      <?php
  }
}
class WpActivity_user_Widget extends WP_Widget {
    function WpActivity_user_Widget() {
        /* Widget settings. */
        $widget_ops = array( 'classname' => 'wp-activity', 'description' => __('Display the logged user own activity', 'wp-activity') );

        /* Widget control settings. */
        $control_ops = array( 'height' => 350, 'id_base' => 'wp-activity-user' );

        /* Create the widget. */
        $this->WP_Widget( 'wp-activity-user', __('Wp-Activity logged user own activity', 'wp-activity'), $widget_ops, $control_ops );
    }

    function widget( $args, $instance ) {
        global $user_ID;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title'] );
        $number = $instance['number'];
        $visitor = $instance['visitor'];

        echo $before_widget;
        if ( $title )
            $title =  $before_title . $title . $after_title;
        if ($user_ID) {
            act_stream($number, $title, false, $user_ID);
        }
        elseif ($visitor=='1') {
            act_stream($number, $title, false, '');
        }
        echo $after_widget;
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['number'] = $new_instance['number'];
        $instance['visitor'] = $new_instance['visitor'];
        return $instance;
    }

    function form( $instance ) {

        $defaults = array( 'title' => __('Your activity', 'wp-activity'), 'number' => '30', 'visitor' => '1');
        $instance = wp_parse_args( (array) $instance, $defaults );
        if ($instance['visitor']=='1') {
            $checkedyes='checked="checked"';
            $checkedno='';
        } else {
            $checkedyes='';
            $checkedno='checked="checked"';
        }
        ?>

        <p>
        <label for ="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title :', 'wp-activity');
        ?></label>
        <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:95%;" />
                  </p>
                  <p>
                  <label for ="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e('Events number :', 'wp-activity');
        ?></label>
        <input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $instance['number']; ?>" style="width:95%;" />
                  </p>
                  <p>
                  <label for ="<?php echo $this->get_field_id( 'visitor' ); ?>"><?php _e('When viewed by visitor', 'wp-activity');
        ?> :</label><br />
        <input type="radio" <?php echo $checkedyes ?> name="<?php echo $this->get_field_name( 'visitor' ); ?>" value="1" /> <?php _e('Display all users activity', 'wp-activity');
        ?><br />
        <input type="radio" <?php echo $checkedno ?> name="<?php echo $this->get_field_name( 'visitor' ); ?>" value="0" /> <?php _e('Display nothing', 'wp-activity');
        ?><br />
        </p>

        <?php
    }
}
?>
