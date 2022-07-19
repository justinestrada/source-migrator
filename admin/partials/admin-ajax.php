<?php
include_once( ABSPATH . 'wp-admin/includes/image.php' );

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

add_action('wp_ajax_source_migrate', 'source_migrate');
function source_migrate() {
  $response = array('success' => false);
  if (!isset($_POST['action']) || $_POST['action'] !== 'source_migrate'  ) {
    exit( json_encode($response) );
  }

  if (!isset($_POST['auth_token']) || !isset($_POST['site_url']) || !isset($_POST['post_type'])) {
    exit( json_encode($response) );
  }

  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $_POST['site_url'].'/wp-json/source-migrator/export',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query([
      'post_type'=> $_POST['post_type'],
      'auth_token'=> $_POST['auth_token'],
      'matching_type'=> $_POST['matching_type'],
      'slugs'=> $_POST['slugs']
    ])
  ]);

  $response = curl_exec($curl);
  curl_close($curl);
  exit($response);
}

add_action('wp_ajax_get_post_slugs', 'get_post_slugs');
function get_post_slugs() {
  $response = array('success' => false);
  if(!isset($_POST['post_type'])) {
    exit(json_encode($response));
  }
  $posts = get_posts([
    'numberposts' => -1,
    'post_type'   => $_POST['post_type']
  ]);
  $response['data'] = [];
  if ( $posts ) {
    foreach ( $posts as $post ){
      $response['data'][] =  $post->post_name;
    }
  }
  $response['success'] = true;
  exit(json_encode($response));
}

add_action('wp_ajax_save_imported_featued_images', 'save_imported_featued_images');
function save_imported_featued_images() {
  $response = array('success' => false);
  if (!isset($_POST['image_url']) || !isset($_POST['post_id'])) {
    exit( json_encode($response) );
  }
  $imageurl = $_POST['image_url'];
  $postId = $_POST['post_id'];
  $attach_id = save_file_in_wordpress($imageurl, $postId);
  set_post_thumbnail($postId, $attach_id );
  if (isset($_POST['import_content_images']) && $_POST['import_content_images'] == 1) {
    sm_import_new_post_content_images($postId, get_post($postId)->post_content);
  }
  $response['success'] = true;
  exit(json_encode($response));
}

function save_file_in_wordpress($imageurl, $postId) {
  $imagetype = end(explode('/', getimagesize($imageurl)['mime']));
  $uniq_name = date('dmY').''.(int) microtime(true); 
  $filename = $postId.$uniq_name.'.'.$imagetype;
  
  $uploaddir = wp_upload_dir();
  $uploadfile = $uploaddir['path'] . '/' . $filename;
  $contents = file_get_contents($imageurl);
  $savefile = fopen($uploadfile, 'w');
  fwrite($savefile, $contents);
  fclose($savefile);
  
  $wp_filetype = wp_check_filetype(basename($filename), null );
  $attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => $filename,
    'post_content' => '',
    'post_status' => 'inherit'
  );
  
  $attach_id = wp_insert_attachment( $attachment, $uploadfile );
  $imagenew = get_post( $attach_id );
  $fullsizepath = get_attached_file( $imagenew->ID );
  $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
  wp_update_attachment_metadata( $attach_id, $attach_data );
  return $attach_id;
}
add_action('wp_ajax_get_products_sku', 'get_products_sku');
function get_products_sku() {
  $response = array('success' => false, 'data'=>[]);
  $args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
  query_posts( $args );
  if(!have_posts()){
    $response['success'] = true;
    exit($response);
  }
  while (have_posts()) {
    the_post();
    $product = wc_get_product(get_the_ID());
    $sku = $product->get_sku();
    if ($sku) {
      $response['data'][] = $product->get_sku();
    }
  }
  $response['success'] = true;
  exit(json_encode($response));
}

add_action('wp_ajax_save_imported_product_reviews', 'save_imported_product_reviews');
function save_imported_product_reviews() {
  $response = array('success' => false, 'data'=>[]);
  if (!isset($_POST['sku']) || !isset($_POST['reviews'])) {
    exit( json_encode($response) );
  }
  $productId = wc_get_product_id_by_sku($_POST['sku']);
  foreach ($_POST['reviews'] as $review) {
    $review['user_id'] = get_user_by( 'email', $review['comment_author_email'] )->ID;
    $review['comment_post_ID'] = $productId;
    $comment_id = wp_insert_comment($review);
    update_comment_meta( $comment_id, 'rating', $review['rating'] );
  }
  $response['success'] = true;
  exit(json_encode($response));
}

add_action('wp_ajax_source_migrate_reviews', 'source_migrate_reviews');
function source_migrate_reviews() {
  $response = array('success' => false);

  if (!isset($_POST['auth_token']) || !isset($_POST['site_url'])) {
    exit( json_encode($response) );
  }

  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $_POST['site_url'].'/wp-json/source-migrator/export-reviews',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query($_POST)
  ]);

  $response = curl_exec($curl);
  curl_close($curl);
  exit($response);
}

add_action('wp_ajax_source_migrate_users', 'source_migrate_users');
function source_migrate_users() {
  $response = array('success' => false);

  if (!isset($_POST['auth_token']) || !isset($_POST['site_url'])) {
    exit( json_encode($response) );
  }

  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $_POST['site_url'].'/wp-json/source-migrator/export-users',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query($_POST)
  ]);

  $response = curl_exec($curl);
  curl_close($curl);
  exit($response);
}

add_action('wp_ajax_save_imported_users', 'save_imported_users');
function save_imported_users() {
  $response = array('success' => false);
  $data = $_POST['user'];
  $user = $data['user'];

  $response['data'] = $user['data']['user_email'] .'  ,  '. $user['data']['user_login'];
  if($user['data']['user_email'] === null && $user['data']['user_login'] === null) {
    exit(json_encode($response));
  }
  $local_user = get_user_by('email', $user['data']['user_email']);
  
  if(!$local_user) {
    $local_user = get_user_by('login', $user['data']['user_login']);
  }
  
  global $wpdb;
  $current_user_id = get_current_user_id();

  if ($local_user && $local_user->ID === $current_user_id) {
    exit(json_encode($response));
  }
  $local_user_id = $local_user->ID;

  if(!$local_user) {
    $user_table_name = $wpdb->prefix.'users';
    unset($user['data']['ID']);
    $wpdb->insert($user_table_name,$user['data']);
    $local_user_id = $wpdb->insert_id;
  }

  $_user = new WP_User($local_user_id);
  $_user->set_role('subscriber');
  $_user->remove_role('subscriber');
  foreach ($user['roles'] as $key => $r) {
    $_user->add_role($r); 
  }
  $response['meta'] = [];
  foreach ($data['meta'] as $key => $meta) {
    foreach ($meta as $m) {
      if($key !== 'wpuw_capabilities'){
        $m = maybe_unserialize($m);
        $response['meta'][] = $m;
        update_metadata('user',$local_user_id, $key, $m);
      }
    }
  }
  $response['success'] = true;
  exit(json_encode($response));
}

add_action('wp_ajax_source_migrate_coupons', 'source_migrate_coupons');
function source_migrate_coupons() {
  $response = array('success' => false);

  if (!isset($_POST['auth_token']) || !isset($_POST['site_url'])) {
    exit( json_encode($response) );
  }

  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $_POST['site_url'].'/wp-json/source-migrator/export-coupons',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => http_build_query($_POST)
  ]);

  $response = curl_exec($curl);
  curl_close($curl);
  exit($response);
}

add_action('wp_ajax_save_imported_coupons', 'save_imported_coupons');
function save_imported_coupons() {
  $response = array('success' => false);
  $coupon = $_POST['coupon'];
  $meta = $_POST['meta'];
  $site_url = $_POST['site_url'];

  global $wpdb;
  $prev_coupon = get_post($coupon['ID']);
  $local_coupon_id = $prev_coupon->ID;
  $post_table_name = $wpdb->prefix.'posts';

  $coupon['guid'] = str_replace($site_url, get_site_url(), $coupon['guid']);

  if(isset($coupon['filter'])) {
    $coupon['post_content_filtered'] = $coupon['filter'];
    unset($coupon['filter']);
  }

  if(!$local_coupon_id) {
    unset($coupon['ID']);
    $wpdb->insert($post_table_name,$coupon);
    $local_coupon_id = $wpdb->insert_id;
  } else {
    unset($coupon['ID']);
    $wpdb->update( $post_table_name, $coupon, array( 'post_name' => $coupon['post_name'] ) );
  }

  foreach ($meta as $key => $m) {
    foreach ($m as $sm) {
      $sm = maybe_unserialize($sm);
      update_post_meta($local_coupon_id, $key, $m);
    }
  }
  $response['success'] = true;
  exit(json_encode($response));
}