<?php
function act_feed(){
  global $wpdb, $options_act;
  extract($options_act);
  $act_feed = wp_cache_get( 'act_feed' );
  if (!$act_feed) {
    $date = gmdate('r', strtotime($wpdb->get_var("SELECT MAX(act_date) FROM ".$wpdb->prefix."activity")));
    $wp_url = get_bloginfo('wpurl');
    $act_not_in = '';
    if(!$act_feed_connect)  { $act_not_in .= "'CONNECT', "; }
    if(!$act_feed_comments) { $act_not_in .= "'COMMENT_ADD', 'COMMENT_EDIT', 'COMMENT_DEL', "; }
    if(!$act_feed_posts)    { $act_not_in .= "'POST_ADD', 'POST_EDIT', 'POST_DEL', "; }
    if(!$act_feed_profiles) { $act_not_in .= "'PROFILE_EDIT', "; }
    if(!$act_feed_links)    { $act_not_in .= "'LINK_ADD', "; }
    $act_types = array(
                  'CONNECT'     => __('New visit', 'wp-activity'),
                  'POST_ADD'    => __('New post', 'wp-activity'),
                  'POST_EDIT'   => __('Post edited', 'wp-activity'),
                  'POST_DEL'    => __('Post deleted', 'wp-activity'),
                  'PROFILE_EDIT'=> __('Profile edited', 'wp-activity'),
                  'COMMENT_ADD' => __('New comment', 'wp-activity'),
                  'COMMENT_EDIT'=> __('Comment edited', 'wp-activity'),
                  'COMMENT_DEL' => __('Comment deleted', 'wp-activity'),
                  'LINK_ADD'    => __('New link', 'wp-activity')
                      );
    $sql="SELECT u.display_name as display_name, user_nicename, u.id as id, act_type, act_date, act_params, a.id as act_id, a.user_id as user_id FROM ".$wpdb->prefix."activity AS a, ".$wpdb->prefix."users AS u WHERE a.user_id = u.id AND a.act_type NOT IN (".$act_not_in."'LOGIN_FAIL', 'ACCESS_DENIED') ORDER BY a.act_date DESC";
    if ( $items = $wpdb->get_results($sql)){
      $cache = '<?xml version="1.0" encoding="utf-8"?>';
      $cache .= '<rss version="2.0"	xmlns:content="http://purl.org/rss/1.0/modules/content/"	xmlns:wfw="http://wellformedweb.org/CommentAPI/"	xmlns:dc="http://purl.org/dc/elements/1.1/"	xmlns:atom="http://www.w3.org/2005/Atom"	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"	>';
      $cache .= '<channel>';
      $cache .= '<title>'.attribute_escape(strip_tags(html_entity_decode(sprintf(__('%s activity RSS Feed', 'wp-activity'), get_bloginfo('name'))))).'</title>';
      $cache .= '<link>'.$wp_url.'</link>';
      $cache .= '<description><![CDATA['.sprintf(__('User events of %s', 'wp-activity'), get_bloginfo('name')).']]></description>';
      $cache .= '<lastBuildDate>'.$date.'</lastBuildDate>';
      $cache .= '<language>'.get_bloginfo('language').'</language>';
      foreach ( (array) $items as $item ){
        $act_prep = act_prepare($item, 'rss');
        $act_desc = $act_prep['user'].' '.$act_prep['text'].' '.$act_prep['params'];
        $cache .='<item>';
        $cache .='<title>'.$act_types[$act_prep['type']].'</title>';
        $cache .='<pubDate>'.$act_prep['date'].'</pubDate>';
        $cache .='<description><![CDATA[<p>'.attribute_escape(strip_tags(html_entity_decode($act_desc))).'</p>]]></description>';
        $cache .='<content:encoded><![CDATA[<div style="float:left; margin:1em">'.get_avatar($item->user_id,40).'</div><p>'.$act_desc.'</p><div style="clear:both;"></div>]]></content:encoded>';
        $cache .='<dc:creator>'.$item->display_name.'</dc:creator>';
        $cache .='<link>'.$wp_url.'</link>';
        $cache .='</item>';
      }
      $cache .='</channel>';
      $cache .='</rss>';
    }
    wp_cache_set( 'act_feed', $cache, '3600' );
    echo $cache;
  }else{
    echo $act_feed;
  }
}
?>
