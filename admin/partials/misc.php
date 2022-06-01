
<?php
$post_types = array(
  'post' => 'Blog post',
  // 'customer_story' => 'Customer Story',
  // 'resource_hub' => 'Research & Guide',
  // 'press' => 'Press item',
);
$import_log_url = get_site_url() . '/wp-content/uploads/source-migrator-logs/import-content-images.txt'; ?>

<div id="import-alert" class="alert" role="alert" style="display: none;"></div>

<h3>Miscellanous</h3>

<form id="import-content-images" method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator&tab=misc" style="margin-bottom: 1rem;" >
  <h3>Import Content Images</h3>
  <p>Transform recently imported post content into new post content (Replace Media files with new media files). This is typically performed if the initial migration didn't complete entirely. It starts at oldest post first.</p>
  <table class="form-table">
    <tbody>
      <tr>
        <th><label for="post_type_input">Post Type</label></th>
        <td>
          <?php /*
          <input type="text" id="post_type_input" name="sm_import_post_type" class="regular-text" value="<?php echo (isset($_POST['sm_import_post_type'])) ? $_POST['sm_import_post_type'] : ''; ?>" required/>
          */ ?>
          <!-- <p>Ex: press, customer_story, resource_hub, post</p> -->
          <select name="sm_post_type" id="post_type_input" required>
            <?php foreach($post_types as $key => $post_type) { ?>
                <option value="<?php echo $key; ?>"><?php echo $post_type; ?></option>
            <?php } ?>
          </select>
        </td>
      </tr>
      <tr>
        <th><label for="sm_limit">Limit</label></th>
        <td>
          <input type="number" id="sm_limit" name="sm_limit" class="small-text" value="<?php echo (isset($_POST['sm_limit'])) ? $_POST['sm_limit'] : '1'; ?>" min="1" required/>
          <p>Leave blank if you want to import all items.</p>
        </td>
      </tr>
      <tr>
        <th><label for="sm_offset">Offset</label></th>
        <td>
          <input type="number" id="sm_offset" name="sm_offset" class="small-text" value="<?php echo (isset($_POST['sm_offset'])) ? $_POST['sm_offset'] : '0'; ?>" min="0" required/>
          <p>Offset the import, starts importing at oldest.</p>
        </td>
      </tr>
      <tr>
        <th><label for="confirm">Are you sure?</label></th>
        <td>
          <input type="checkbox" id="confirm" name="confirm" value="1" required/> Are you sure?.
        </td>
      </tr>
    </tbody>
  </table>
  <button type="submit" name="action" value="source_migrator_import_content_images" class="button button-primary" >Run Import</button>
</form>

<?php
if (isset($_POST['action']) && $_POST['action'] === 'source_migrator_import_content_images') {
  $post_type = $_POST['sm_post_type'];
  global $wpdb;
  $table = $wpdb->prefix . 'posts';
  $sql = "SELECT ID, post_title, post_content FROM $table WHERE post_type = '$post_type' ORDER BY post_date";
  $limit = $_POST['sm_limit'];
  if ($limit !== '-1' && $limit !== '' && $limit !== 0) {
    $sql .= " LIMIT " . $limit;
  }
  $offset = $_POST['sm_offset'];
  if ($offset !== 0 && $offset !== "0" && $offset !== '') {
    $sql .= " OFFSET " . $offset;
  }
  // var_dump($sql);
  // wp_die();
  $posts = $wpdb->get_results($sql);
  if (!empty($posts)) {
    foreach($posts as $post) {
      $response = sm_import_new_post_content_images($post->ID, $post->post_content);
      if ($response) {
        $msg = 'Post Type: ' . $post_type . ', ID: ' . $post->ID . ', Title: "' . $post->post_title . '" | Successfully transformed content and imported  content images.'; ?>
        <p><?php echo $msg; ?></p>
        <?php
        $now = date('Y-m-d H:i:s');
        sm_log_message('import-content-images.txt', "$now | " . $msg . "\n");
      }
    }
  } else { ?>
    <p>No posts to import content images.</p>
  <?php
  }
  ?>
  <p>Attempted to import content images for <span class="count"><?php echo count($posts); ?></span> posts. Read logs <a href="<?php echo $import_log_url; ?>" target="_blank">here</a>.</p>
  <?php
}
?>

<hr/>

<form method="post" action="<?php echo get_site_url(); ?>/wp-admin/tools.php?page=source-migrator&tab=misc" >
  <h3>Transform CSV of post_names and return post IDs.</h3>
  <button type="submit" name="action" value="transform_post_names_csv" class="button button-primary" >Run Transform</button>
</form>

<?php
function sm_transform_csv_post_names_to_ids($post_names_csv) {
  $post_names_ARRAY = explode(', ', $post_names_csv);
  $db_settings = sm_get_db_settings();
  $remote_db = sm_get_remote_db($db_settings);
  var_dump('post_names COUNT', count($post_names_ARRAY));
  echo '<hr>';
  $table = $db_settings->table_prefix . 'posts';
  $results = array();
  foreach($post_names_ARRAY as $post_name) {
    $sql = "SELECT ID FROM $table WHERE post_status = 'publish' AND post_name = '$post_name'";
    $id = $remote_db->get_var($sql);
    if (is_null($id)) {
      var_dump($sql);
      echo '<hr>';
    }
    $results[] = $id;
  }
  var_dump('ids COUNT', count($results));
  echo '<hr>';
  $results = implode(', ', $results);
  return $results;
}

if (isset($_POST['action']) && $_POST['action'] === 'transform_post_names_csv') {
  $tubular_insights_blog_post_slugs = '7-expert-tips-youtube-suggested-videos, youtube-description-box, advanced-youtube-search-tips, 3-metrics-youtube-success, nike-dream-crazier-ad, average-youtube-views, youtube-aspect-ratio, youtube-live-streaming-brand-guide, custom-thumbnails-best-practice, youtube-watch-time, how-to-create-private-playlist-youtube, black-history-month-videos, video-2021, youtube-celebrities-sponsored-video, brands-music-festivals, start-youtube-show-series, top-fortnite-videos, five-tips-for-creating-product-review-videos-that-wow, slime-videos-on-youtube, rick-lax-facebook-views, conde-nast-successful-video-strategy, top-facebook-video-creators-april-2018, lizzo-social-video, male-beauty-influencers, rupauls-drag-race-social-video, reddit-youtube-hydraulic-press-videos, vsco-girl-brands, loreal-influencer-marketing-campaigns, nike-ads-social-video, facebook-live-video-content-ideas, minecraft-youtube-videos, generation-z-youtube, kelloggs-youtube-roi, disney-online-video-brand-development, best-skincare-brands-social-video, brand-guide-to-igtv-videos, top-youtube-channels-august-2019, strategic-video-insights-espn, hours-minute-uploaded-youtube, consumers-purchase-video, facebook-watch-time-increase, sustainable-fashion-videos, youtube-influencers-sponsored-video, jimmy-fallon-tonight-show-youtube, top-facebook-publishers-march-2019, video-trends-asia-pacific, fortnite-marketing-youtube-facebook, top-youtube-channels-september-2018, how-to-videos, healthy-drinks-social-video, 2019-super-bowl-commercials, latest-k-pop-blackpink, street-food-mukbang-trends, buzzfeed-tasty-sponsored-video, high-end-clothing-brands-part-two, baby-shark-challenge-videos, most-viewed-video-brazil-mexico-september-2019, sponsored-gaming-content-may-2019, toy-companies-christmas, beauty-brands-diversity, ucla-gymnastics-sports-video, measurement-metrics, tseries-vs-pewdiepie, omnia-media-youtube-gaming, facebook-video-publishers-february-2019, gaming-industry-sponsored-content-july-2019, patriots-social-video, veganuary-brands-social-video, 14-insights-2018-fifa-world-cup, fast-food-chains-social-video, barbie-videos-social-video, fifa-world-cup-2018-most-watched-videos, innovation-influencer-marketing, video-entertainment-industry-events, video-game-trailers-2018, most-viewed-nba-teams, stranger-things-season-3-brands, bleacher-report-sponsored-content, valentines-day-videos, interview-with-wave-sports, disney-incredibles-2, esports-videos-sponsored-content, gillette-we-believe-ad, teamtrees-social-video, interview-with-first-media, social-video-trends, top-youtube-channels-june-2018, verticalization-youtube-facebook, 2019-super-bowl-ads, high-end-apparel-companies-part-one, march-madness-videos-2018, top-influencer-campaigns-2018, vogue-beauty-tutorial-video, the-office-social-video, 13b-video-advertising-revenue, top-youtube-channels-january-2019, video-marketing-stock-footage, rags-to-riches-story-jungle-creations, back-to-school-videos, beauty-influencers-disabilities, highlight-reel-sports-media, marijuana-videos, disney-magic-for-brands, typical-gamer-debunked, latin-music-latin-grammys, top-youtube-channels-february-2019, top-youtube-channels-june-2019, video-analytics-10-things-sponsored-video, jungle-creations-sponsored-video-facebook, hip-hop-on-youtube, the-daily-show-social-video, top-videos-most-popular-languages, 2019-fifa-womens-world-cup, interview-with-benefit-cosmetics, interview-with-mattel-digital-video, spanish-portuguese-cooking-videos, taylor-swift-vs-ariana-grande-vs-bts, netflix-social-video-queer-eye, oceans-8-online-video-views, bottle-cap-challenge, brave-bison-beauty-brands, chat-with-brave-bison, performance-insights, sponsored-food-videos-july-2019, abc-news-latest-news-videos, bratzchallenge-youtube, fathers-day-brand-videos, global-video-measurement-alliance, interview-with-group-nine-media, online-video-metrics-brand-advertisers-agencies, pbs-video-billion-views, pride-month-brand-videos, top-facebook-publishers-december-2018, top-sponsored-videos-january-2019, top-youtube-channels-march-2019, ae-networks-tubular, 2019-christmas-ads, brexit-deal-brands, facebook-super-bowl-views, konmari-content-social-video, mothers-day-brand-videos, social-video-uk-europe, womens-day-social-video, european-video-insights, 2019-bet-award-winners, ces-social-video, facebook-video-views-april-2019, game-of-thrones-finale, online-video-metrics-media-companies-publishers-creators, portal-a-branded-content, top-facebook-videos-publishers-january-2019, top-youtube-channels-april-2019, top-youtube-channels-december-2018, transgender-videos, youtube-super-bowl-videos, sponsored-videos-pre-roll-video-ads, media-publishers-influencers-video, jungle-creations-webinar, attn-video-strategy, audience-insights, chat-with-barcroft-studios, fireside-chat-with-whistle, kentucky-derby-brands-publishers, national-donut-day, sdcc-2019-videos, summer-movies-2019, the-bachelorette-videos, video-game-industry-sponsored-august, beyond-youtube-multichannel-networks, multi-platform-networks-mpn, 2019-vmas-winners, european-elections-social-video, first-democratic-debates-videos, glitter-videos-youtube, golden-globes-video-views-2019, holiday-sponsored-videos, k-pop-online-video-views, media-conglomerates-amc-mcclatchy, social-video-power-trends, sponsored-content-april-2019, vidcon-europe-2018, veganuary-videos, gymshark-influencer-storytelling, moonbug-data-kids-shows, tubular-covid-19-update, usa-today-5-tips-media-sellers, vice-media-apac-video-strategy, levis-apac-social-video-strategy-2020, social-chain-media-branded-content';
  $ids = sm_transform_csv_post_names_to_ids($tubular_insights_blog_post_slugs);
  var_dump($ids);
}
?>
