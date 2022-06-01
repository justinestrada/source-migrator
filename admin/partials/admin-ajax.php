<?php

add_action('wp_ajax_source_migrator_sm_get_sm_import_post_ids', 'source_migrator_sm_get_sm_import_post_ids');
add_action('wp_ajax_nopriv_source_migrator_sm_get_sm_import_post_ids', 'source_migrator_sm_get_sm_import_post_ids');

function source_migrator_sm_get_sm_import_post_ids() {
  $response = array( 'success' => false );
  if ( ! isset($_POST['action']) || $_POST['action'] !== 'source_migrator_sm_get_sm_import_post_ids'  ) {
    exit( json_encode($response) );
  }
  if (! isset($_POST['sm_import_post_type'])) {
    exit( json_encode($response) );
  }
  $post_type = $_POST['sm_import_post_type'];
  $response['post_ids'] = false;
  $include_only_ids = (isset($_POST['include_only_ids'])) ? $_POST['include_only_ids'] : false;
  $response['post_ids'] = sm_get_sm_import_post_ids($post_type, $include_only_ids, $_POST['limit'], $_POST['offset']);
  $response['success'] = (!empty($response['post_ids'])) ? true : false;
  exit( json_encode($response) );
}

add_action('wp_ajax_source_migrator_sm_import_post', 'source_migrator_sm_import_post');
add_action('wp_ajax_nopriv_source_migrator_sm_import_post', 'source_migrator_sm_import_post');

function source_migrator_sm_import_post() {
  $response = array('success' => false);
  if (!isset($_POST['action']) || $_POST['action'] !== 'source_migrator_sm_import_post'  ) {
    exit( json_encode($response) );
  }
  if (!isset($_POST['sm_import_post_id'])) {
    exit( json_encode($response) );
  }
  $post_id = $_POST['sm_import_post_id'];
  $response = sm_import_post($post_id, $_POST['match_taxonomies']);
  $now = date('Y-m-d H:i:s');
  if ($response['success']) {
    $new_post_id = $response['new_post_id'];
    $msg = $response['msg'];
    sm_log_message('import-posts.txt', "$now | $msg | New post_id: $new_post_id.\n");
    $response['success'] = true;
  } else {
    sm_log_message('import-posts.txt',  "$now | $msg.\n");
    $response['success'] = false;
  }
  exit( json_encode($response) );
}
