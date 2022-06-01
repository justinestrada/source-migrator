<?php
/*
function sm_import_posts($post_type, $args = array()) {
  $return_this = array('success' => true, 'imported_count' => 0);
  try {
    $remote_db = sm_get_remote_db();
    if (!$remote_db) {
      return false;
    }
    $imported_count = 0;
    $sql = "SELECT ID FROM wp_posts WHERE post_type = '$post_type' AND post_status = 'publish'";
    if (!empty($args) && ($args['limit'] !== '-1' || $args['limit'] !== '')) {
      $sql .= " LIMIT " . $args['limit'];
    }
    $result = $remote_db->get_results($sql);
    foreach ($result as $post) {
      $new_post_id = sm_import_post($post);
      $now = date("Y-m-d H:i:s");
      if ($new_post_id) {
        sm_log_message('import-posts.txt', "$now | | Successfully imported $post->post_type, post_id: $new_post_id.\n");
      } else {
        sm_log_message('import-posts.txt',  "$now | Failed to import $post->post_type.\n");
        continue; // skip to next one
      }
      $return_this['imported_count']++;
    } // foreach ($result as $post)
    $remote_db->close();
    $return_this['success'] = true;
    return $return_this;
  } catch (Exception $e) {
    sm_log_message('import-posts.txt',  "Caught exception: " . $e->getMessage() . " \n");
    $return_this['success'] = false;
  }
}*/