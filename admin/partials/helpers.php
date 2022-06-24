<?php

/*
 * Log Message
 * 
 * @params string, string
 */
function sm_log_message($filename, $message) {
  $upload_dir = wp_upload_dir()['basedir'] . '/source-migrator-logs';
  if ( ! is_dir($upload_dir) ) {
    mkdir( $upload_dir, 0700 );
  }
  fopen( $filename, "w" );
  // $msg = "\n"; 
  // $msg .= $message;
  file_put_contents( $upload_dir . "/" . $filename, $message, FILE_APPEND );
}

function sm_get_db_settings() {
  $db_settings = get_option('source_migrator_remote_db');
  $db_settings = json_decode($db_settings);
  if (empty($db_settings)) {
    return false;
  }
  if (!isset($db_settings->user) || !isset($db_settings->pass) || !isset($db_settings->name) || !isset($db_settings->host)) {
    return false;
  }
  return $db_settings;
}

function sm_get_remote_db($db_settings = false) {
  // $db_settings = sm_get_db_settings();
  if (!$db_settings) {
    return false;
  }
  $remote_db = new wpdb($db_settings->user, $db_settings->pass, $db_settings->name, $db_settings->host);
  return $remote_db;
}

/*
 * Get the Import Total Count
 * @params $post_type integer
 * @returns integer on success, boolean false on fail
 */
function sm_get_sm_import_post_ids($post_type, $include_only_ids = false, $limit = '-1', $offset = 0) {
  try {
    $db_settings = sm_get_db_settings();
    $remote_db = sm_get_remote_db($db_settings);
    if (!$remote_db) {
      return false;
    }
    // select all IDs by post_type if published
    $table = $db_settings->table_prefix . 'posts';
    $sql = "SELECT ID FROM $table WHERE post_type = '$post_type' AND post_status = 'publish'";
    if ($include_only_ids) {
      $sql .= " AND ID IN ($include_only_ids)";
    }
    if ($limit !== '-1' && $limit !== '' && $limit !== 0) {
      $sql .= " LIMIT " . $limit;
    }
    if ($offset !== 0 && $offset !== "0" && $offset !== '') {
      $sql .= " OFFSET " . $offset;
    }
    return $remote_db->get_results($sql);
  } catch (Exception $e) {
    sm_log_message('import-posts.txt',  "Caught exception: " . $e->getMessage() . " \n");
    return false;
  }
}

/*
 * Import Post
 * 
 * @param $post object 
 * @returns $new_post_id integer on success, false boolean on fail
 */
function sm_import_post($sm_import_post_id, $match_taxonomies = false) {
  try {
    $db_settings = sm_get_db_settings();
    $remote_db = sm_get_remote_db($db_settings);
    if (!$remote_db) {
      return ['success' => false, 'msg' => 'Couldn\'t connect to external database.'];
    }
    $table = $db_settings->table_prefix . 'options';
    $remote_site_url = $remote_db->get_var("SELECT option_value FROM $table WHERE option_name = 'siteurl'");
    // select post by post_id from remote
    $table = $db_settings->table_prefix . 'posts';
    $sql = "SELECT * FROM $table WHERE ID = $sm_import_post_id";
    $post = $remote_db->get_row($sql);

    $pardot_form_url = false;
    $new_post_content = $post->post_content;
    if ($post->post_type === 'resource_hub') {
      // Parse Content - Remove Pardot Form URL, Get Pardot Form URL
      $response = sm_parse_remote_content_get_pardot_form_url($post->post_content);
      $new_post_content = $response['content'];
      $pardot_form_url = $response['pardot_form_url'];
    }

    // create new post item
    $new_post_item = array(
      // 'post_author' => 1, // wpengine // TODO: get user by id if name matches name then good
      'post_author' => 2, // totalcsr - 2 (trenton)
      'post_date' => $post->post_date,
      'post_date_gmt' => $post->post_date_gmt,
      'post_title' => $post->post_title,
      'post_excerpt' => $post->post_excerpt,
      'post_content' => $new_post_content,
      'post_name' => $post->post_name,
      'post_type' => $post->post_type,
      'post_modified' => $post->post_modified,
      'post_modified_gmt' => $post->post_modified_gmt,
      'post_status' => 'publish',
      'comment_status' => 'closed',
    );
    $new_post_id = wp_insert_post($new_post_item, true);
    if (is_wp_error($new_post_id)) {
      return ['success' => false, 'msg' => 'Failed to create new post.'];
    }

    /*
     * Import New Post Content Images
     */
    sm_import_new_post_content_images($new_post_id, $new_post_content); // get_the_content($new_post_id)

    $acf_fields = sm_get_acf_fields_by_post_type($post->post_type);
    // TODO: Yoast fields should be an input field
    $yoast_fields = array(
      '_yoast_wpseo_content_score', 
      '_yoast_wpseo_focuskeywords',
      '_yoast_wpseo_meta-robots-noindex',
      '_yoast_wpseo_metadesc',
      '_yoast_wpseo_title'
    );
    $table = $db_settings->table_prefix . 'postmeta';
    $sql = "SELECT * FROM $table WHERE post_id = $sm_import_post_id";
    $remote_post_meta_rows = $remote_db->get_results($sql);
    foreach($remote_post_meta_rows as $post_meta) {
      // acf fields
      if ($acf_fields && !empty($acf_fields)) {
        foreach($acf_fields as $field) {
          if ($field['key'] === $post_meta->meta_key) {
            $new_meta_value = $post_meta->meta_value;
            if ($field['type'] === 'image') {
              $remote_attachment_id = $post_meta->meta_value;
              if ($remote_attachment_id) {
                $remote_attachment_file_path = sm_get_remote_attachment_file_path_by_attachment_id($db_settings, $remote_db, $remote_site_url . '/wp-content/uploads', $remote_attachment_id);
                $new_file_path = sm_download_file_by_url($remote_attachment_file_path, true);
                $new_meta_value = sm_create_attachment_by_file_url($new_file_path, $new_post_id);
                // var_dump($remote_attachment_id, $remote_attachment_file_path, $new_file_path);
              }
            } elseif ($field['type'] === 'taxonomy') {
              // Customer Story Taxonomy field type
              $remote_taxonomies = sm_get_remote_taxonomies_by_post_type($post->post_type);
              $new_taxonomies = sm_get_new_taxonomies_by_post_type($post->post_type);
              $remote_term_slug = false;
              foreach($remote_taxonomies as $remote_taxonomy) {
                if ($remote_taxonomy['taxonomy'] === $post_meta->meta_key) {
                  // Tax terms
                  foreach($remote_taxonomy['terms'] as $remote_term) {
                    if ($remote_term['id'] === (int)$post_meta->meta_value) {
                      $remote_term_slug = $remote_term['slug'];
                    }
                  }
                }
              }
              if ($post->post_type === 'customer_story' && $remote_term_slug === 'brands-agencies') {
                $new_meta_value = 47; // agencies 47
              } elseif ($post->post_type === 'customer_story' && $remote_term_slug === 'mcns') {
                $new_meta_value = 17; // brands 17
              } elseif ($post->post_type === 'resource_hub' && $remote_term_slug === 'whitepapers') {
                $new_meta_value = 12; // reports 12
              } elseif ($remote_term_slug) {
                foreach($new_taxonomies as $new_taxonomy) {
                  if ($new_taxonomy['taxonomy'] === $post_meta->meta_key) {
                    foreach($new_taxonomy['terms'] as $new_term) {
                      if ($remote_term_slug === $new_term['slug']) {
                        $new_meta_value = $new_term['id'];
                      }
                    }
                  }
                }
              }
            }
            // Updated ACF field
            update_field($post_meta->meta_key, $new_meta_value, $new_post_id); // $field['key'] === $post_meta->meta_key
          }
        }
      }
      // yoast seo
      if (in_array($post_meta->meta_key, $yoast_fields)) {
        update_post_meta($new_post_id, $post_meta->meta_key, $post_meta->meta_value);
      }
    }

    /*
     * Research & Guide Pardof Form URL (ACF)
     */
    if ($post->post_type === 'resource_hub' && $pardot_form_url) {
      update_field('form_url', $pardot_form_url, $new_post_id);
    }

    /*
     * Import/Assign Featured Image
     */
    try {
      // Get Remote Featured Image Attachment ID
      if ($post->post_type === 'customer_story' || $post->post_type === 'resource_hub') {
        $table = $db_settings->table_prefix . 'postmeta';
        $remote_attachment_id = $remote_db->get_var("SELECT meta_value FROM $table WHERE post_id = $sm_import_post_id AND meta_key = '_thumbnail_image'");
      } else {
        $remote_attachment_id = sm_get_remote_attachment_id_by_meta_key($db_settings, $remote_db, $sm_import_post_id, '_thumbnail_id');
      }
      if ($remote_attachment_id) {
        $remote_attachment_file_path = sm_get_remote_attachment_file_path_by_attachment_id($db_settings, $remote_db, $remote_site_url . '/wp-content/uploads', $remote_attachment_id);

        // if database name is totalcsr_wp223 - in file_path replace `wp-content` with `onboarding`
        if ($db_settings->name === 'totalcsr_wp223') {
          $remote_attachment_file_path = str_replace('wp-content', 'onboarding', $remote_attachment_file_path);
        }

        // var_dump('we only want to assign if file path is legit', '$remote_attachment_file_path', $remote_attachment_file_path);
        if ($remote_attachment_file_path) {
          // Assign Featured Image
          $new_file_path = sm_download_file_by_url($remote_attachment_file_path, true);
          // var_dump('test', $new_file_path, $new_post_id);
          $new_attachment_id = sm_create_attachment_by_file_url($new_file_path, $new_post_id);
          set_post_thumbnail($new_post_id, $new_attachment_id);
        }
      }
    } catch (Exception $e) {
      return ['success' => false, 'msg' => 'Failed to import media, msg: ' . $e];
    }

    if ($match_taxonomies) {
      /*
      * Assign Taxonomies (Weirdly if the post has a taxonomy acf field, then this just works by default)
      if ($post->post_type !== 'customer_story' && $post->post_type !== 'resource_hub' && $post->post_type !== 'press') {
        sm_assign_taxonomies($db_settings, $remote_db, $sm_import_post_id, $post->$post_type, $new_post_id);
      }
      */
      $sm_import_post_type = $post->post_type;
      if ($post->post_type === 'post') {
        sm_assign_taxonomies($db_settings, $remote_db, $sm_import_post_id, $sm_import_post_type, $new_post_id);
      }
    }

    return ['success' => true, 'msg' => 'Successfully imported post.', 'new_post_id' => $new_post_id];
  } catch (Exception $e) {
    return ['success' => false, 'msg' => 'Something went wrong.'];
  }
}

/*
 * Get ACF Fields by Post Type
 * 
 * @return Array
 */
function sm_get_acf_fields_by_post_type($post_type) {
  // Tubular ACF fields
  if ($post_type === 'press') {
    return array(
      ['key' => 'publisher', 'type' => 'text'],
      ['key' => 'publishDate', 'type' => 'text'],
      ['key' => 'sourceUrl', 'type' => 'text'],
    );
   } else if ($post_type === 'customer_story') {
    return array(
      ['key' => 'customer-story-category', 'type' => 'taxonomy'],
      // 'customer-story-topic', // TODO: Delete this acf field after import
      // 'customer-story-country', // TODO: Delete this acf field after import
      ['key' => 'thumbnail', 'type' => 'group'],
        ['key' => 'thumbnail_image', 'type' => 'image'],
        ['key' => 'thumbnail_caption', 'type' => 'text'],
      ['key' => 'photo', 'type' => 'group'],
        ['key' => 'photo_image', 'type' => 'image'],
        ['key' => 'photo_caption', 'type' => 'text'],
      ['key' => 'video_url', 'type' => 'text'],
      // ['key' => 'related_articles', 'type' => 'relationship'], // not possible to assign on import, since these relationships wouldn't exist
    );
  } else if ($post_type === 'resource_hub') {
    return array(
      ['key' => 'resource-hub-category', 'type' => 'taxonomy'],
      // ['key' => 'resource-hub-topic', 'type' => 'taxonomy'], // TODO: Delete this acf field after import
      // ['key' => 'resource-hub-country', 'type' => 'taxonomy'], // TODO: Delete this acf field after import
      ['key' => 'thumbnail', 'type' => 'group'],
        ['key' => 'thumbnail_image', 'type' => 'image'],
        ['key' => 'thumbnail_caption', 'type' => 'text'],
      ['key' => 'photo', 'type' => 'group'],
        ['key' => 'photo_image', 'type' => 'image'],
        ['key' => 'photo_caption', 'type' => 'text'],
      ['key' => 'video_url', 'type' => 'text'],
      // ['key' => 'related_articles', 'type' => 'relationship'], // not possible to assign on import, since these relationships wouldn't exist
    );
  }
  return false;
}

function sm_get_remote_attachment_id_by_meta_key($db_settings, $remote_db, $post_id, $meta_key) {
  // Get the remote imported post _thumbnail_id
  $table = $db_settings->table_prefix . 'postmeta';
  $sql = "SELECT meta_value FROM $table WHERE post_id = $post_id AND meta_key = '$meta_key'";
  // var_dump($sql);
  $attachment_id = $remote_db->get_var($sql);
  if (!$attachment_id) {
    return false;
  }
  return $attachment_id;
}

function sm_get_remote_attachment_file_path_by_attachment_id($db_settings, $remote_db, $remote_uploads_dir, $attachment_id) {
  // _wp_attachment_metadata
  // $table = $db_settings->table_prefix . 'postmeta';
  // $sql = "SELECT meta_value FROM $table WHERE post_id = $_thumbnail_id AND meta_key = '_wp_attachment_metadata'";
  // $attachment_metadata = $remote_db->get_var($sql);
  // get the file url
  $table = $db_settings->table_prefix . 'postmeta';
  $sql = "SELECT meta_value FROM $table WHERE post_id = $attachment_id AND meta_key = '_wp_attached_file'";
  $file_folder_name = $remote_db->get_var($sql);
  if (!$file_folder_name) {
    return false;
  }
  return $remote_uploads_dir . '/' . $file_folder_name;
}

function sm_download_file_by_url($file_url, $move_to_uploads_directory_based_on_url) {
  if ( ! function_exists( 'download_url' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
  }
  $tmp_file = download_url( $file_url );

  preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $file_url, $matches);
  $file_name = basename($matches[0]);

  // create file upload folder
  preg_match("/uploads\/\d\d\d\d/", $file_url, $matches); // uploads/yyyy
  $year = str_replace('uploads/', '', $matches[0]); // yyyy
  preg_match("/uploads\/\d\d\d\d\/\d\d\//", $file_url, $matches); // uploads/yyyy/mm/
  $month = str_replace('/', '', preg_replace('/uploads\/\d\d\d\d/', '', $matches[0])); // mm

  $upload_dir = wp_upload_dir()['basedir'] . '/' . $year . '/' . $month;
  // if directory does NOT exist
  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0700, true); 
  }
  // Sets file final destination.
  // $new_file_path = ABSPATH . 'wp-content/uploads/' . $year . $month . $file_name;
  $new_file_path = $upload_dir . '/' . $file_name;
  // Copies the file to the final destination and deletes temporary file.
  copy( $tmp_file, $new_file_path );
  @unlink( $tmp_file );
  return $new_file_path;
}

function sm_create_attachment_by_file_url($file_url, $post_id) {
  $file_name = basename($file_url);
  $wp_filetype = wp_check_filetype($file_name, null );
  $attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => sanitize_file_name($file_name),
    'post_content' => '',
    'post_status' => 'inherit'
  );
  $attach_id = wp_insert_attachment($attachment, $file_url, $post_id);
  if (!function_exists('wp_generate_attachment_metadata')) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
  }
  // TODO: fix - HMMM? this is suppose to generate the wp size, but inside the media library the images are just showing a document icon
  $imagenew = get_post( $attach_id );
  $fullsizepath = get_attached_file( $imagenew->ID );
  $attach_data = wp_generate_attachment_metadata($attach_id, $fullsizepath);
  wp_update_attachment_metadata($attach_id, $attach_data);
  return $attach_id;
}

function sm_assign_taxonomies($db_settings, $remote_db, $sm_import_post_id, $sm_import_post_type, $new_post_id) {
  $post_taxonomies = sm_get_remote_taxonomies_by_post_type($sm_import_post_type);
  if (!empty($post_taxonomies)) {
    foreach($post_taxonomies as $this_taxonomy) {
      $taxonomy_name = $this_taxonomy['taxonomy'];
      // get remote terms
      $table = $db_settings->table_prefix . 'term_relationships';
      $sql = "SELECT term_taxonomy_id FROM $table WHERE object_id = $sm_import_post_id";
      // var_dump('$sql', $sql);
      $remote_term_ids = $remote_db->get_results($sql);

      // get remote term slugs by term ids
      $remote_term_slugs = array();
      $table = $db_settings->table_prefix . 'terms';
      foreach ( $remote_term_ids as $remote_term_id ) {
        $remote_term_id = $remote_term_id->term_taxonomy_id;
        $sql = "SELECT * FROM $table WHERE term_id = $remote_term_id";
        $remote_term_details = $remote_db->get_results($sql);
        $remote_term_slugs[] = $remote_term_details[0]->slug;
      }
      
      $new_taxonomies = sm_get_new_taxonomies_by_post_type($sm_import_post_type);

      foreach ($remote_term_slugs as $term_slug) {
        foreach ($new_taxonomies as $new_taxonomy) {
          foreach ( $new_taxonomy['terms'] as $new_term) {
            if ($new_term['slug'] === $term_slug) {
              wp_set_post_terms($new_post_id, $new_term['id'], $taxonomy_name, true);
            }
          }
        }
      }
    }
  }
}

function sm_get_remote_taxonomies_by_post_type($remote_post_type) {
  if ($remote_post_type === 'post') {
    // (Legacy TOTAL CSR) totalcsr_wp223
    return array(
      [
        'taxonomy' => 'category',
        'terms' => array(
          ['id' => 1087, 'slug' => 'agency-owner-articles'],
          // ['id' => 1143, 'slug' => 'agency-spotlights'], //
          ['id' => 36, 'slug' => 'agency-staff-articles'],
          // ['id' => 372, 'slug' => 'company-updates'],
          ['id' => 1972, 'slug' => 'helpful-tips-for-employees'],
          ['id' => 1975, 'slug' => 'helpful-tips-for-hiring'],
          ['id' => 1973, 'slug' => 'helpful-tips-for-leadership'],
          ['id' => 1974, 'slug' => 'helpful-tips-for-onboarding'],
          ['id' => 1976, 'slug' => 'helpful-tips-for-training'],
          ['id' => 1208, 'slug' => 'other-articles'],
          ['id' => 1977, 'slug' => 'seo'],
        )
      ],
    );
  } else if ($remote_post_type === 'press') {
    return []; // none
  } else if ($remote_post_type === 'customer_story') {
    return array(
      [
        'taxonomy' => 'customer-story-category',
        'terms' => array(
          ['id' => 1329, 'slug' => 'brands-agencies'],
          ['id' => 1330, 'slug' => 'mcns'],
          ['id' => 1331, 'slug' => 'media'],
        ),
      ],
    );
  } else if ($remote_post_type === 'resource_hub') {
    return array(
      [
        'taxonomy' => 'resource-hub-category',
        'terms' => array(
          ['id' => 1317, 'slug' => 'reports'], // Reports
          ['id' => 1318, 'slug' => 'videos'], // Videos
          ['id' => 1319, 'slug' => 'webinars'], // Webinars
          ['id' => 1320, 'slug' => 'whitepapers'], // Whitepapers
          ['id' => 2314, 'slug' => 'market-snapshot'], // Market Snapshot
        ),
      ],
    );
  }
  return false;
}

function sm_get_new_taxonomies_by_post_type($new_post_type) {
  if ($new_post_type === 'post') {
    // (Legacy TOTAL CSR) totalcsr_wp223
    return array(
      [
        'taxonomy' => 'category',
        'terms' => array(
          [
            'id' => 34, // Pantheon Live
            // 'id' => 21, // Localhost
            'slug' => 'agency-owner-articles'
          ],
          [
            'id' => 36, // Pantheon Live
            'slug' => 'agency-staff-articles'
          ],
          [
            'id' => 37, // Pantheon Live
            // 'id' => 21, // Localhost
            'slug' => 'helpful-tips-for-employees'
          ],
          [
            'id' => 38, // Pantheon Live
            'slug' => 'helpful-tips-for-hiring'
          ],
          [
            'id' => 39, // Pantheon Live
            // 'id' => 26, // Localhost
            'slug' => 'helpful-tips-for-leadership'
          ],
          [
            'id' => 40, // Pantheon Live
            'slug' => 'helpful-tips-for-onboarding'
          ],
          [
            'id' => 41, // Pantheon Live
            'slug' => 'helpful-tips-for-training'
          ],
          [
            'id' => 42, // Pantheon Live
            'slug' => 'other-articles'
          ],
          [
            'id' => 43, // Pantheon Live
            'slug' => 'seo'
          ],
        )
      ],
    );
  } else if ($new_post_type === 'press') {
    return []; // none
  } else if ($new_post_type === 'customer_story') {
    return array(
      [
        'taxonomy' => 'customer-story-category',
        'terms' => array(
          ['id' => 47, 'slug' => 'agencies'],
          ['id' => 17, 'slug' => 'brands'],
          ['id' => 11, 'slug' => 'media'],
        ),
      ],
      // [
      //   'taxonomy' => 'industry',
      //   'terms' => array(
      //     ...
      //   ),
      // ],
    );
  } else if ($new_post_type === 'resource_hub') {
    return array(
      [
        'taxonomy' => 'resource-hub-category',
        'terms' => array(
          ['id' => 34, 'slug' => 'event-presentations'], // Event Presentations
          ['id' => 12, 'slug' => 'reports'], // Reports & Guides
          ['id' => 16, 'slug' => 'market-snapshot'], // Snapshots
          ['id' => 13, 'slug' => 'videos'], // Videos
          ['id' => 14, 'slug' => 'webinars'], // Webinars
        ),
      ],
    );
  }
  return false;
}

/*
 * Parse Remote Content Get Pardot Form URL
 * 
 * @return Array (string, string) - Post content string, Pardot form URL
 */
// TODO: this function should also accomodate looking form <iframe> pardot as well as shortcode
function sm_parse_remote_content_get_pardot_form_url($remote_post_content) {
  $return_this = array(
    'content' => $remote_post_content,
    'pardot_form_url' => false,
  );
  // Find the position of the first occurrence of a substring in a string
  $pardot_shortcode_starting_position = strpos($remote_post_content , '[pardotForm');
  if (!$pardot_shortcode_starting_position) {
    return $return_this;
  }
  // TODO: May need to instead get the ending positoin of the string after the first initial pardot found
  $pardot_shortcode_ending_position = strpos($remote_post_content , '"]');
  if (!$pardot_shortcode_ending_position) {
    return $return_this;
  }
  $pardot_shortcode_length = ($pardot_shortcode_ending_position - $pardot_shortcode_starting_position) + 2;
  // Parse Pardot Shortcode
  $pardot_shortcode = substr($remote_post_content, $pardot_shortcode_starting_position, $pardot_shortcode_length);
  // echo '$pardot_shortcode: ' . $pardot_shortcode . '<hr>';
  $content_without_pardot_shortcode = str_replace($pardot_shortcode, '', $remote_post_content);
  $return_this['content'] = $content_without_pardot_shortcode;
  $return_this['pardot_form_url'] = sm_get_string_between($pardot_shortcode, '[pardotForm src="', '" width="');
  return $return_this;
}

function sm_get_string_between($string, $start, $end) {
  $string = ' ' . $string;
  $ini = strpos($string, $start);
  if ($ini == 0) return '';
  $ini += strlen($start);
  $len = strpos($string, $end, $ini) - $ini;
  return substr($string, $ini, $len);
}

function sm_import_new_post_content_images($new_post_id, $new_post_content_html) {
  $doc = new DOMDocument();
  @$doc->loadHTML($new_post_content_html);
  $images_tag = $doc->getElementsByTagName('img');
  if (empty($images_tag)) {
    return false;
  }
  $old_images_src = array();
  foreach ($images_tag as $tag) {
    $old_images_src[] = $tag->getAttribute('src');
  }
  if (empty($old_images_src)) {
    return false;
  }
  $new_images_src = array();
  foreach ($old_images_src as $image_src) {
    $new_image_src = sm_download_file_by_url($image_src, true);
    $attach_id = sm_create_attachment_by_file_url($new_image_src, $new_post_id);
    $new_images_src[] = wp_get_attachment_url($attach_id);
  }
  if (empty($new_images_src)) {
    return false;
  }
  // Replace old_image with new_image
  foreach ($new_images_src as $key => $new_image_src) {
    $new_post_content_html = str_replace($old_images_src[$key], $new_image_src, $new_post_content_html);
  }
  $new_new_post = array(
    'ID' => $new_post_id,
    'post_content' => $new_post_content_html,
  );
  $result = wp_update_post($new_new_post, true);
  // echo '<pre>';
  // var_dump($new_new_post, $result);
  // wp_die();
  if (is_wp_error($result)) {
    sm_log_message('import-posts.txt', 'Failed to update post with new content data.');
    return false;
  }
  return true;
}
