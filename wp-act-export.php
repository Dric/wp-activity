<?php
function act_export(){
  global $wpdb;
  if(isset($_POST['act_export']) and check_admin_referer('wp-activity-export','act_export_csv')){
    $act_sqlorderby_sec = '';
    if (isset($_POST['act_type_filter'])){
    $act_type_filter = esc_html($_POST['act_type_filter']);
    $act_user_sel = esc_html($_POST['act_user_sel']);
    $act_data_filter = esc_html($_POST['act_data_filter']);
    if ($act_user_sel <> 'all' and !empty($act_user_sel)){
      if (is_numeric($act_user_sel)){
        $sql_userobject = get_userdata($act_user_sel);
        $sql_username = $sql_userobject->display_name;
        $sqlfilter .= ' AND u.id = '.$act_user_sel;
      }else{
        $sql_username = $act_user_sel;
        $sql_userobject = get_user_by('login', $act_user_sel);
        $sqlfilter .= ' AND u.display_name = "'.$act_user_sel.'"';
        $act_user_sel = $sql_userobject->ID;
      }
      $sqlfilter .= ' AND act_type NOT IN ("LOGIN_FAIL", "ACCESS_DENIED")';
    }
    if ($act_type_filter <> 'all' and !empty($act_type_filter)){
      $sqlfilter .= 'AND act_type = "'.$act_type_filter.'"';
    }
    if (!empty($act_data_filter)){
      $sqlfilter .= ' AND act_params LIKE "%%'.$act_data_filter.'%%"';
    }
    if (($act_type_filter == 'LOGIN_FAIL' or $act_type_filter == 'all') and $act_user_sel <> 'all'){
      $sqlfilter .= ') UNION ALL (SELECT null as display_name, user_id as id, act_type, act_date, act_params, id FROM '.$wpdb->prefix.'activity WHERE act_type = "LOGIN_FAIL" AND SUBSTRING_INDEX(act_params, "###", 1) = "'.$sql_username.'"';
      if (!empty($act_data_filter)){
        $sqlfilter .= ' AND act_params LIKE "%%'.$act_data_filter.'%%"';
      }
    }
  }
  $sqlfilter .= ')';
  if (isset($_POST['act_order_by'])){
    $act_order_by = esc_html($_POST['act_order_by']);
  }else{
    $act_order_by = 'order_date';
  }

  switch ($act_order_by) {
  	case 'order_user' :
  		$sqlorderby = 'display_name ASC, act_date DESC';
  		break;
  	case 'order_type' :
  		$sqlorderby = 'act_type ASC, act_date DESC';
  		break;
  	case 'order_date' :
  	default :
  		$sqlorderby = 'act_date DESC';
  		break;
  }

    $act_recent_sql  = "(SELECT u.display_name as display_name, u.id as id, act_type, act_date, act_params, a.id as act_id FROM ".$wpdb->prefix."activity AS a, ".$wpdb->users." AS u WHERE a.user_id = u.id ".$sqlfilter." ORDER BY ".$sqlorderby;
    if ( $logins = $wpdb->get_results($wpdb->prepare($act_recent_sql))){
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private",false);
      header("Content-Type: application/csv-tab-delimited-table; charset=utf-8");
      header("Content-Disposition: attachment; filename=wp-activity.csv");
      header("Content-Transfer-Encoding: binary");
      echo __("Date", 'wp-activity').';'.__("User", 'wp-activity').';'.__("Event Type", 'wp-activity').';'.__("Applies to", 'wp-activity').";\n";
      foreach ( (array) $logins as $act ){
        $act_id_tab[] = $act->act_id;
        $act_prep = act_prepare ($act, 'csv');
        echo $act_prep['date'].';'.$act_prep['user'].';'.$act_prep['type'].';'.$act_prep['params'];
        echo "\n"; 
      }
      //delete exported data if requested
      if ($_POST['act_del_exported'] == true ){
        $act_del = implode(",", $act_id_tab);
        $del_sql = "DELETE FROM ".$wpdb->prefix."activity WHERE id IN(".$act_del.")";
        $wpdb->query($wpdb->prepare($del_sql));
      }
    }else{
      echo 'Zombie frenzy ! They gonna eat our brains ! ...No, in fact something goes wrong with the sql query : '.$wpdb->print_error();
    }
  }else{
    echo "Alien Invasion ! We all gonna die ! ...No, in fact this is a security check failure.";
  }
  die();
}
?>