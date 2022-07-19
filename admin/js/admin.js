(function( $ ) {
	'use strict';

const ImporterForm = {
	ACFFields: {
		press: [
			{
				'key': 'publisher',
				'type': 'text',
			},
			{
				'key': 'publishDate',
				'type': 'text'
			},
			{
				'key': 'sourceUrl',
				'type': 'text',
			},
		],
		customer_story: [
			{
				'key': 'customer-story-category',
				'type': 'taxonomy',
			},
			{
				'key': 'thumbnail',
				'type': 'group',
			},
			{
				'key': 'thumbnail_image',
				'type': 'image',
			},
			{
				'key': 'thumbnail_caption',
				'type': 'text',
			},
			{
				'key': 'photo',
				'type': 'group',
			},
			{
				'key': 'photo_image',
				'type': 'image',
			},
			{
				'key': 'photo_caption',
				'type': 'text',
			},
			{
				'key': 'video_url',
				'type': 'text',
			},
		],
		resource_hub: [
			{
				'key': 'resource-hub-category',
				'type': 'taxonomy',
			},
			//   ['key' => 'resource-hub-topic', 'type' => 'taxonomy'], // TODO: Delete this acf field after import
			//   ['key' => 'resource-hub-country', 'type' => 'taxonomy'], // TODO: Delete this acf field after import
			{
				'key': 'thumbnail',
				'type': 'group',
			},
			{
				'key': 'thumbnail_image',
				'type': 'image',
			},
			{
				'key': 'thumbnail_caption',
				'type': 'text',
			},
			{
				'key': 'photo',
				'type': 'group',
			},
			{
				'key': 'photo_image',
				'type': 'image',
			},
			{
				'key': 'photo_caption',
				'type': 'text',
			},
			{
				'key': 'video_url',
				'type': 'text',
			},
		],
	},
	onLoad: function() {
		const post_type = $('#post_type_input').val();
		this.loadIncludeOnly(post_type);
		this.setACFByPostType(post_type);
		$('#post_type_input').on('change', function() {
			ImporterForm.loadIncludeOnly($(this).val());
			ImporterForm.setACFByPostType($(this).val());
		});
	},
	setACFByPostType: function(post_type) {
		if (ImporterForm.ACFFields[post_type] && ImporterForm.ACFFields[post_type].length) {
			$('.acf-fields').empty();
			ImporterForm.ACFFields[post_type].forEach(function(field) {
				$('.acf-fields').append('<span class="badge badge-' +  field.type + '">' +  field.key + '</span>');
			});
			$('.acf-tr').show();
		} else {
			$('.acf-tr').hide();
		}
	},
	loadIncludeOnly: function(post_type) {
		if (post_type === 'post') {
			// const include_only_ids = '2783, 4762, 2507, 1397, 7961, 733, 6946, 789, 2977, 1396, 1329, 875, 751'; // tubularlabs.com
			// const include_only_ids = '131686, 130944, 68179, 109505, 135248, 121531, 10143, 133229, 130566, 105998, 105163, 135203, 133759, 134473, 135788, 121471, 134125, 40701, 135120, 135592, 135979, 130068, 135954, 135283, 135245, 131672, 135882, 133235, 135668, 133036, 135449, 133320, 134179, 134039, 135860, 134327, 135825, 134168, 130984, 93616, 133805, 135838, 134516, 135231, 135352, 135906, 134573, 134850, 135125, 135850, 135173, 135323, 134365, 134980, 135801, 135144, 135865, 135541, 135919, 135817, 135139, 135806, 134871, 135761, 135289, 135775, 135159, 135964, 134435, 135688, 135316, 134341, 135856, 135724, 135190, 134866, 135585, 135658, 135207, 135394, 134294, 134232, 135115, 135931, 135522, 135845, 134387, 134544, 135156, 135756, 135271, 135108, 135178, 135187, 135890, 135220, 135795, 134946, 135704, 135927, 135695, 135342, 135739, 135410, 135876, 135297, 135628, 134122, 133590, 135181, 135457, 135654, 135518, 135615, 135303, 135812, 135374, 134292, 134266, 135607, 135640, 135385, 134512, 135784, 135402, 135196, 135536, 135149, 135361, 132364, 135720, 135547, 135117, 135226, 135357, 134195, 135939, 135901, 135163, 135255, 135412, 135213, 135261, 133098, 135552, 135111, 135469, 135334, 132355, 135558, 135217, 135466, 135128, 135472, 135164, 134209, 133720, 134828, 134061, 135439, 135329, 135733, 135387, 135527, 135633, 135578, 135419, 135830, 127693, 125179, 135748, 135491, 135568, 135192, 135099, 135104, 134520, 135762, 134890, 135481, 134093, 135200, 136027, 136058, 136071, 136065, 136051, 136006, 136015'; // tubularinsights.com
			// $('[name="include_only_ids"]').val(include_only_ids);
		} else {
			$('[name="include_only_ids"]').val('');
		}
	},
};

const Importer = {
	sm_import_post_type : '',
	import_limit : '-1',
	import_offset : 0,
	match_taxonomies: false,
	onLoad: function() {
		this.onSubmit($('#import-form'))
    this.onMigrateFeatureImageSubmit($('#import-featured-image-form'))
    this.onMigrateProductReviewsSubmit($('#import-product-reviews-form'))
    this.onMigrateUsersSubmit($('#import-users-form'))
    this.onMigrateCouponsSubmit($('#import-coupons-form'))
	},
	onSubmit: function($form) {
		$form.on('submit', function(e) {
			e.preventDefault();
			// if (!$('[name="confirm"]').is(':checked')) {
			//   alert('Please check to confirm.');
			//   return;
			// }
			$form.find('[type="submit"]').prop('disabled', true);
			Importer.onImport($form);
		});
	},
	onImport: function($form) {
		Importer.sm_import_post_type = $form.find('[name="sm_import_post_type"]').val();
		Importer.include_only_ids = $form.find('[name="include_only_ids"]').val();
		// Importer.import_limit = $form.find('[name="limit"]').val();
		Importer.import_limit = $form.find('[name="sm_limit"]').val();
		Importer.import_offset = $form.find('[name="offset"]').val();
		Importer.match_taxonomies = $form.find('[name="match_taxonomies"]').val();
		// console.log('Importer.sm_import_post_type', Importer.sm_import_post_type);
		// Get Import Post IDs
		this.onGetImportPostIDs().then(function(res) {
			res = JSON.parse(res);
			console.log('onGetImportPostIDs', res);
			const import_total = res.post_ids.length;
			if (res.success) {
				$('#import-results').show();
				$('#import-results .total').text(import_total);
				let import_count = {
					'total': 0,
					'success': 0,
					'failed': 0,
				};
				// For each Post ID import post by post_id
				res.post_ids.forEach(function(post) {
					Importer.importPost(post.ID).then(function(res) {
						res = JSON.parse(res);
						console.log('importPost', res);
						if ( res.success ) {
							import_count.success++;
						} else {
							console.error(res); // Something went wrong
							import_count.failed++;
						}
						import_count.total++;
						Importer.updateProgressBar($form, import_count.total, import_total);

						if (import_count.total >= import_total) {
							Importer.onDoneImporting($form, true, res.msg);
						}
					}).catch(function(err) {
						console.error(err);
						Importer.onDoneImporting($form, false, err);
					});
				});
			} else {
			}
		}).catch(function(err) {
			console.error(err);
			Importer.onDoneImporting($form, false, err);
		});
	},
  onGetImportPostIDs: function() {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
					'sm_import_post_type': Importer.sm_import_post_type,
					'include_only_ids': Importer.include_only_ids,
					'limit': Importer.import_limit,
					'offset': Importer.import_offset,
					'action': 'source_migrator_sm_get_sm_import_post_ids',
				},
				type: 'POST',
				config: { headers: {'Content-Type': 'multipart/form-data' }},
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
	importPost: function(post_id) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
					'sm_import_post_id': post_id,
					'action': 'source_migrator_sm_import_post',
					'match_taxonomies': Importer.match_taxonomies
				},
				type: 'POST',
				config: { headers: {'Content-Type': 'multipart/form-data' }},
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  onMigrateFeatureImageSubmit: function($form) {
		$form.on('submit', function(e) {
			e.preventDefault();
      $form.find('[type="submit"]').prop('disabled', true);
			Importer.onMigrateFeatureImageImport($form);
		});
  },
  onMigrateFeatureImageImport: async function($form) {
    const site_url = $form.find('[name="site_url"]').val();
		const auth_token = $form.find('[name="auth_token"]').val();
    const post_type = $form.find('[name="post_type"]').val();
    const matching_type = $form.find('[name="matching_type"]:checked').val();
    const import_content_images = $form.find('[name="import_content_images"]:checked').val();

    const mapSeries = async ($form, iterable, action) => {
      let totalImported = 0
      for (const data of iterable) {
        totalImported++
        await action(data.post_id, data.image_url[0])
        Importer.updateProgressBar($form,totalImported,iterable.length)
      }
    }

    try {
      let featureImageSlug = {'data':null};
      if (matching_type == 'permalink') {
        featureImageSlug = await Importer.importFeatureImagesSlugs(post_type)
        featureImageSlug = JSON.parse(featureImageSlug)
      }
      let importFeatureImagesRes = await Importer.importFeatureImages(auth_token, site_url, post_type, matching_type, import_content_images, featureImageSlug.data)
      importFeatureImagesRes = JSON.parse(importFeatureImagesRes)
      if (!importFeatureImagesRes || importFeatureImagesRes.error){
        Importer.onDoneImporting($form, false, importFeatureImagesRes.error)
        return
      }

      if(!importFeatureImagesRes.data || !importFeatureImagesRes.data.length) {
        Importer.onDoneImporting($form, false, 'Unable to find anything to import')
        return
      }

      $('#import-results').show();
      $('#import-results .total').text(importFeatureImagesRes.data.length);
      
      Importer.onDoneImporting($form, true, 'Manage to get images from remote. Now saving in database')
      await mapSeries($form, importFeatureImagesRes.data,Importer.saveimportedFeatureImages)
      Importer.onDoneImporting($form, true, 'Feature Images Uploaded')
      $form.find('[type="submit"]').removeAttr('disabled');

    } catch (error) {
      console.log(error)
      Importer.onDoneImporting($form, false, 'Error Please Try Again')
    } 
  },
  importFeatureImagesSlugs: function(postType) {
    return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
        data: {
          'post_type': postType,
          'action': 'get_post_slugs'
        },
				type: 'POST',
				config: { headers: {'Content-Type': 'multipart/form-data' }},
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  importFeatureImages: function(auth_token, site_url, post_type, matching_type,import_content_images, slugs = null) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'site_url': site_url,
					'auth_token': auth_token,
					'post_type': post_type,
          'matching_type': matching_type,
          'slugs': slugs,
          'import_content_images': import_content_images,
          'action': 'source_migrate'
				},
				type: 'POST',
				config: { headers: {'Content-Type': 'multipart/form-data' }},
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  saveimportedFeatureImages: function(post_id, image_url) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
					'post_id': post_id,
          'image_url': image_url,
          'action' : 'save_imported_featued_images'
				},
				type: 'POST',
				config: { headers: {'Content-Type': 'multipart/form-data' }},
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  onMigrateProductReviewsSubmit: function($form) {
		$form.on('submit', function(e) {
			e.preventDefault();
      $form.find('[type="submit"]').prop('disabled', true);
			Importer.onMigrateProductReviews($form);
		});
  },
  onMigrateProductReviews: async function($form) {
    const site_url = $form.find('[name="site_url"]').val();
		const auth_token = $form.find('[name="auth_token"]').val();
    const post_type = $form.find('[name="post_type"]').val();

    const mapSeries = async ($form, iterable, action) => {
      let totalImported = 0
      for (const data of iterable) {
        totalImported++
        await action(data.sku, data.review)
        Importer.updateProgressBar($form,totalImported,iterable.length)
      }
    }

    try {
      let productsSkus = {'data':null};
      productsSkus = await Importer.importProductsSkus(post_type)
      productsSkus = JSON.parse(productsSkus)
      let importProductsReviews = await Importer.importProductReviews(auth_token, site_url, productsSkus.data)
      importProductsReviews = JSON.parse(importProductsReviews)
      if (!importProductsReviews || importProductsReviews.error){
        Importer.onDoneImporting($form, false, importProductsReviews.error)
        return
      }

      if(!importProductsReviews.data || !importProductsReviews.data.length) {
        Importer.onDoneImporting($form, false, 'Unable to find anything to import')
        return
      }

      $('#import-results').show();
      $('#import-results .total').text(importProductsReviews.data.length);
      
      Importer.onDoneImporting($form, true, 'Manage to get product reviews from remote. Now saving in database')
      console.log(importProductsReviews)
      await mapSeries($form, importProductsReviews.data,Importer.saveimportedProductReviews)
      Importer.onDoneImporting($form, true, 'Product Reviews Migrated')
      $form.find('[type="submit"]').removeAttr('disabled');

    } catch (error) {
      console.log(error)
      Importer.onDoneImporting($form, false, 'Error Please Try Again')
    } 
  },
  importProductsSkus: function(postType) {
    return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
        data: {
          'action': 'get_products_sku'
        },
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  importProductReviews: function(auth_token, site_url, skus) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'site_url': site_url,
					'auth_token': auth_token,
          'skus': skus,
          'action': 'source_migrate_reviews'
				},
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  saveimportedProductReviews: function(sku, reviews) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'action' : 'save_imported_product_reviews',
					'sku': sku,
          'reviews': reviews
				},
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  onMigrateUsersSubmit: function($form) {
    $form.on('submit', function(e) {
			e.preventDefault();
      $form.find('[type="submit"]').prop('disabled', true);
			Importer.onMigrateUsers($form);
		});
  },
  onMigrateUsers: async function($form) {
    const site_url = $form.find('[name="site_url"]').val();
		const auth_token = $form.find('[name="auth_token"]').val();

    const mapSeries = async ($form, iterable, action) => {
      let totalImported = 0
      for (const data of iterable) {
        totalImported++
        await action(data)
        Importer.updateProgressBar($form,totalImported,iterable.length)
      }
    }

    try {
      let users = {'data':null};
      users = await Importer.importUsers(auth_token,site_url)
      users = JSON.parse(users)

      if(!users.data || !users.data.length) {
        Importer.onDoneImporting($form, false, 'Unable to find anything to import')
        return
      }

      $('#import-results').show();
      $('#import-results .total').text(users.data.length);
      
      Importer.onDoneImporting($form, true, 'Manage to get users from remote. Now saving in database')
      await mapSeries($form, users.data,Importer.saveimportedUsers)
      Importer.onDoneImporting($form, true, 'Users Migrated')
      $form.find('[type="submit"]').removeAttr('disabled');

    } catch (error) {
      console.log(error)
      Importer.onDoneImporting($form, false, 'Error Please Try Again')
    } 
  },
  importUsers: function(auth_token, site_url) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'site_url': site_url,
					'auth_token': auth_token,
          'action': 'source_migrate_users'
				},
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  saveimportedUsers: function(user) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'action' : 'save_imported_users',
					user,
				},
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  onMigrateCouponsSubmit: function($form) {
    $form.on('submit', function(e) {
			e.preventDefault();
      $form.find('[type="submit"]').prop('disabled', true);
			Importer.onMigrateCoupons($form);
		});
  },
  onMigrateCoupons: async function($form) {
    const site_url = $form.find('[name="site_url"]').val();
		const auth_token = $form.find('[name="auth_token"]').val();

    const mapSeries = async ($form, iterable, site_url, action) => {
      let totalImported = 0
      for (const data of iterable) {
        totalImported++
        await action(data, site_url)
        Importer.updateProgressBar($form,totalImported,iterable.length)
      }
    }

    try {
      let coupons = {'data':null};
      coupons = await Importer.importCoupons(auth_token,site_url)
      coupons = JSON.parse(coupons)

      if(!coupons.data || !coupons.data.length) {
        Importer.onDoneImporting($form, false, 'Unable to find anything to import')
        return
      }

      $('#import-results').show();
      $('#import-results .total').text(coupons.data.length);
      
      Importer.onDoneImporting($form, true, 'Manage to get coupons from remote. Now saving in database')
      await mapSeries($form, coupons.data, site_url, Importer.saveimportedCoupons)
      Importer.onDoneImporting($form, true, 'Users Migrated')
      $form.find('[type="submit"]').removeAttr('disabled');

    } catch (error) {
      console.log(error)
      Importer.onDoneImporting($form, false, 'Error Please Try Again. Please make sure you have correct auth token')
    } 
  },
  importCoupons: function(auth_token, site_url) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'site_url': site_url,
					'auth_token': auth_token,
          'action': 'source_migrate_coupons'
				},
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
  saveimportedCoupons: function(data, site_url) {
		return new Promise( (resolve, reject) => {
			$.ajax({
				url: SourceMigrator.admin_ajax,
				data: {
          'action' : 'save_imported_coupons',
          'site_url': site_url,
					'coupon': data['coupon'],
          'meta': data['meta']
				},
				type: 'POST',
			}).done(function(res) {
				resolve(res);
			}).fail(function(err) {
				reject(err);
			});
		});
	},
	updateProgressBar: function($form, count, import_total) {
		const percentage = ((count/import_total) * 100).toFixed(2) + '%';
		// console.log(count, import_total, percentage);
		$('#progress .bar', $form).css('width', percentage);
		$('#progress .bar .percent', $form).text(percentage);
		$('#import-results .count', $form).text(count);
	},
	onDoneImporting: function($form, success, msg) {
		if (success) {
			$('#import-alert', $form).removeClass('alert-danger').addClass('alert-success').show().text(msg);
		} else {
			$('#import-alert', $form).removeClass('alert-success').addClass('alert-danger').show().text(msg);
		}
		$form.find('[type="submit"]').prop('disabled', false);
	},
};

$(document).ready(function() {
	ImporterForm.onLoad();
	Importer.onLoad();
});

})( jQuery );
