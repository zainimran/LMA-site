(function($){

	'use strict';

	// WordPress SEO by Yoast
	var Builder_WPSEO = {
		wpseo_meta_desc_length: 0,
		init: function(){
			var self = this,
				timeout;

			// check if wpseo activated
			if( TBuilderPluginCompat.wpseo_active ) {
				// Perform action
				this.updateDesc();
				$('#yoast_wpseo_metadesc').keyup(function () {
					clearTimeout(timeout);
					timeout = setTimeout(function(){
						self.updateDesc();
					}, 500);
				});
				$(document).on('change', '#yoast_wpseo_metadesc', this.updateDesc)
				.on('change', '#yoast_wpseo_focuskw', this.updateDesc);
			}
		
		},

		updateDesc: function(){
			var desc = $.trim(yst_clean($('#' + wpseoMetaboxL10n.field_prefix + 'metadesc').val())),
				snippet = $('#wpseosnippet');

			if (desc == '' && wpseoMetaboxL10n.wpseo_metadesc_template != '') {
				desc = wpseoMetaboxL10n.wpseo_metadesc_template;
			}

			if ( desc == '' ) {
				$.ajax({
					type: "POST",
					url: ajaxurl,
					data:
					{
						action : 'wpseo_get_html_builder',
						nonce : themifyBuilder.tfb_load_nonce,
						post_id : $('input#post_ID').val()
					},
					beforeSend: function(){
						$(".desc span.content").html('Updating meta desc ...');
					},
					success: function( data ){
						var result = JSON.parse(data),
							new_desc = result.text_str;

						// Clear the generated description
						snippet.find('.desc span.content').html('');
						yst_testFocusKw();

						desc = $("#content").val() + new_desc;
						desc = yst_clean(desc);

						var focuskw = yst_escapeFocusKw($.trim($('#' + wpseoMetaboxL10n.field_prefix + 'focuskw').val()));
						if (focuskw != '') {
							var descsearch = new RegExp(focuskw, 'gim');
							if (desc.search(descsearch) != -1 && desc.length > wpseoMetaboxL10n.wpseo_meta_desc_length) {
								desc = desc.substr(desc.search(descsearch), wpseoMetaboxL10n.wpseo_meta_desc_length);
							} else {
								desc = desc.substr(0, wpseoMetaboxL10n.wpseo_meta_desc_length);
							}

							Builder_WPSEO.updateFocusKw(focuskw, desc ); // update the focus kw

						} else {
							desc = desc.substr(0, wpseoMetaboxL10n.wpseo_meta_desc_length);
						}
						desc = yst_boldKeywords(desc, false);
						desc = yst_trimDesc(desc);
						snippet.find('.desc span.autogen').html(desc);
						
					}
				});
			}
		},

		updateFocusKw: function( focuskw, content ) {
			var p = new RegExp("(^|[ \s\n\r\t\.,'\(\"\+;!?:\-])" + focuskw + "($|[ \s\n\r\t.,'\)\"\+!?:;\-])", 'gim'),
				html = '<li>' + TBuilderPluginCompat.wpseo_builder_content_text + ptest(content, p) + '</li>';
			$(html).appendTo($('#focuskwresults').find('ul'));
		}
	};

	$(window).load(function(){
		Builder_WPSEO.init();
	});
})(jQuery);