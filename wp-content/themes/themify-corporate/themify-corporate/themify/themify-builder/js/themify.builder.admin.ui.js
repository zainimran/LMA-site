;var ThemifyPageBuilder;
(function($, window, document, undefined) {

	'use strict';

	// Serialize Object Function
	if ( 'undefined' === typeof $.fn.serializeObject ) {
		$.fn.serializeObject = function() {
			var o = {};
			var a = this.serializeArray();
			$.each(a, function() {
				if (o[this.name] !== undefined) {
					if (!o[this.name].push) {
						o[this.name] = [o[this.name]];
					}
					o[this.name].push(this.value || '');
				} else {
					o[this.name] = this.value || '';
				}
			});
			return o;
		};	
	}

	// Builder Function
	ThemifyPageBuilder = {
		
		clearClass: 'col6-1 col5-1 col4-1 col4-2 col4-3 col3-1 col3-2 col2-1 col-full',

		gridClass: ['col-full', 'col4-1', 'col4-2', 'col4-3', 'col3-1', 'col3-2', 'col6-1', 'col5-1'],

		init: function() {
			this.tfb_hidden_editor_object = tinyMCEPreInit.mceInit['tfb_lb_hidden_editor'];
			this.preLoader = $('<div/>', {id: 'themify_builder_loader'});
			this.alertLoader = $('<div/>', {id: 'themify_builder_alert', class: 'alert'});

			// auto save metabox when save post
			this.isPostSave = false;

			this.bindEvents();
			this.moduleEvents();
			this.setupLightbox();
			this.mediaUploader();
			this.openGallery();
			this.saveByKeyInput();
		},

		bindEvents: function() {
			var self = ThemifyPageBuilder,
				$body = $('body'),
				resizeId,
				eventToUse = 'true' == themifyBuilder.isTouch ? 'touchend' : 'mouseenter mouseleave';

			/* rows */
			$body.on('click', '.toggle_row', this.toggleRow)
			.on('click', '.themify_builder_option_row', this.optionRow)
			.on('click', '.themify_builder_delete_row', this.deleteRow)
			.on('click', '.themify_builder_duplicate_row', this.duplicateRow)
			.on( eventToUse , '.themify_builder_row .row_menu', this.MenuHover);
			$('.themify_builder_row_panel').on( eventToUse, '.module_menu', this.MenuHover);

			/* module */
			$body.on('click', '.themify_module_options', this.optionsModule)
			.on('dblclick', '.active_module', this.dblOptionModule)
			.on('click', '.themify_module_duplicate', this.duplicateModule)
			.on('click', '.themify_module_delete', this.deleteModule)
			.on('click', '.add_module', this.addModule)

			/* clear styling */
			.on('click', '.reset-module-styling', this.resetModuleStyling)

			/* lightbox */
			.on('click', '#themify_builder_lightbox_parent .close_lightbox', this.closeLightBox)
			.on('click', '.tf-option-checkbox-js', this.clickCheckBoxOption)
			.on('click', '#tfb_module_settings input[type="text"], #tfb_module_settings textarea, .themify_builder_row .gutter_select', function(){
				$(this).focus();
			})
			.on('dblclick', '#themify_builder_overlay', function( event ){
				var id = $(event.target).prop('id');
				if ( id !== '' && id == 'themify_builder_overlay' ) {
					$('#themify_builder_lightbox_parent').find('.close_lightbox').trigger('click');
				}
			})
			.on('click', '.builder_cancel_lightbox',  function(){
				$('#themify_builder_lightbox_parent').find('.close_lightbox').trigger('click');
			})

			/* save module option */
			.on('submit', '#tfb_module_settings', this.moduleSave)
			.on('click', '#themify_builder_main_save', this.mainSave)
			.on('click', '#tfb_module_settings .add_new a', this.moduleOptAddRow)
			.on('click', '#themify_builder_duplicate', this.duplicatePage)

			/* save row option */
			.on('submit', '#tfb_row_settings', this.rowSaving);

			/* hook save to publish button */
			$('input#publish').on('click', function(e){
				if(e.which){
					$('input[name*="builder_switch_frontend"]').val(0);
				}
				self.postSave(e);
			});

			// module events
			$(window).resize(function() {
				clearTimeout(resizeId);
				resizeId = setTimeout(function(){
					self.moduleEvents();
				}, 500);
			});

			// add loader to body
			self.alertLoader.appendTo('body');

			// equal height
			$('#page-buildert').on('click', function(){
				var $inputBuilderSwitch = $('input[name*="builder_switch_frontend"]');
				$inputBuilderSwitch.val(0);
				$inputBuilderSwitch.closest('.themify_field_row').hide(); // hide the switch to frontend field check
				self.newRowAvailable();
				self.moduleEvents();
			});
			$('input[name*="builder_switch_frontend"]').closest('.themify_field_row').hide(); // hide the switch to frontend field check

			// layout icon selected
			$body.on('click', '.tfl-icon', function(e){
				$(this).addClass('selected').siblings().removeClass('selected');
				e.preventDefault();
			});

			// equal height
			self.equalHeight();

			// switch frontend
			$('#themify_builder_switch_frontend').on('click', this.switchFrontEnd);

			// lightbox form fields
			$body.on('change', '#tfb_module_settings .query_category_single', function(){
				$(this).closest('.themify_builder_input').find('.query_category_multiple').val($(this).val());
			});

			// Styling
			$body.on('click', '.themify_builder_options_tab li', function(e){
				e.preventDefault();
				var activeTab = $(this).find('a').attr('href');
				$(this).addClass('current').siblings().removeClass('current');
				$(activeTab).show().siblings('.themify_builder_options_tab_content').hide();
			}).on('editing_module_option', function(){
				$('.themify_builder_options_tab_content').hide().first().show();
				$('ul.themify_builder_options_tab li:first').addClass('current');
			})

			// Grid Menu List
			.on('click', '.themify_builder_grid_list li a', this._gridMenuClicked)
			.on(eventToUse, '.themify_builder_row .grid_menu', this._gridHover)
			.on('change', '.themify_builder_row .gutter_select', this._gutterChange)
			.on('click', '.themify_builder_sub_row .sub_row_delete', this._subRowDelete)
			.on('click', '.themify_builder_sub_row .sub_row_duplicate', this._subRowDuplicate);

			// Module actions
			self.moduleActions();
			self.newRowAvailable();
			self._selectedGridMenu();
		},

		saveByKeyInput: function() {
			// key event save
			$(document).on('keydown', function(event){
				if (83 == event.which && (true == event.ctrlKey || true == event.metaKey)) {
					event.preventDefault();
					var $moduleSettings = $('#tfb_module_settings');
					if($moduleSettings.length > 0){
						$moduleSettings.trigger('submit');
					}
				}
			});
		},

		setColorPicker: function(context) {
			$('.builderColorSelect', context).each(function(){
				var $minicolors = $(this),
					// Hidden field used to save the value
					$input = $minicolors.parent().parent().find('.builderColorSelectInput'),
					// Visible field used to show the color only
					$colorDisplay = $minicolors.parent().parent().find('.colordisplay'),
					setColor = '',
					setOpacity = 1.0,
					sep = '_';

				if ( '' != $input.val() ) {
					// Get saved value from hidden field
					var colorOpacity = $input.val();
					if ( -1 != colorOpacity.indexOf(sep) ) {
						// If it's a color + opacity, split and assign the elements
						colorOpacity = colorOpacity.split(sep);
						setColor = colorOpacity[0];
						setOpacity = colorOpacity[1] ? colorOpacity[1] : 1;
					} else {
						// If it's a simple color, assign solid to opacity
						setColor = colorOpacity;
						setOpacity = 1.0;
					}
					// If there was a color set, show in the dummy visible field
					$colorDisplay.val( setColor );
				}

				$minicolors.minicolors({
					opacity: 1,
					textfield: false,
					change: function(hex, opacity) {
						if ( '' != hex ) {
							if ( opacity && '0.99' == opacity ) {
								opacity = '1';
							}
							var value = hex.replace('#', '') + sep + opacity;
							this.parent().parent().find('.builderColorSelectInput').val(value);
							$colorDisplay.val( hex.replace('#', '') );
						}
					}
				});
				// After initialization, set initial swatch, either defaults or saved ones
				$minicolors.minicolors('value', setColor);
				$minicolors.minicolors('opacity', setOpacity);
			});

			$('body').on('blur', '.colordisplay', function(){
				var $input = $(this),
					tempColor = '',
					$minicolors = $input.parent().find('.builderColorSelect'),
					$field = $input.parent().find('.builderColorSelectInput');
				if ( '' != $input.val() ) {
					tempColor = $input.val();
				}
				$input.val( tempColor.replace('#', '') );
				$field.val( $input.val().replace(/[abcdef0123456789]{3,6}/i, tempColor.replace('#', '')) );
				$minicolors.minicolors('value', tempColor);
			}).on('keyup', '.colordisplay', function(){
				var $input = $(this),
					tempColor = '',
					$minicolors = $input.parent().find('.builderColorSelect'),
					$field = $input.parent().find('.builderColorSelectInput');
				if ( '' != $input.val() ) {
					tempColor = $input.val();
				}
				$input.val( tempColor.replace('#', '') );
				$field.val( $input.val().replace(/[abcdef0123456789]{3,6}/i, tempColor.replace('#', '')) );
				$minicolors.minicolors('value', tempColor);
			});
		},

		moduleEvents: function() {
			var self = ThemifyPageBuilder,
					gridOpt;

			$('.row_menu .themify_builder_dropdown, .module_menu .themify_builder_dropdown').hide();
			$('.themify_module_holder').each(function(){
				if($(this).find('.themify_builder_module').length > 0) {
					$(this).find('.empty_holder_text').hide();
				}else{
					$(this).find('.empty_holder_text').show();
				}
			});

			$( ".themify_builder_module_panel .themify_builder_module" ).draggable({
				appendTo: "body",
				helper: "clone",
				revert: 'invalid',
				connectToSortable: ".themify_module_holder"
			});
			$( ".themify_module_holder" ).sortable({
				placeholder: 'themify_builder_ui_state_highlight',
				items: '.themify_builder_module, .themify_builder_sub_row',
				connectWith: '.themify_module_holder',
				cursor: 'move',
				revert: 100,
				sort: function( event, ui ){
					var placeholder_h = ui.item.outerHeight();
					$('.themify_module_holder .themify_builder_ui_state_highlight').height(placeholder_h);
				},
				receive: function( event, ui ){
					self.PlaceHoldDragger();
					$( this ).parent().find( '.empty_holder_text' ).hide();
				},
				stop: function(event, ui) {
					var parent = ui.item.parent();

					if(!ui.item.hasClass('active_module') && !ui.item.hasClass('themify_builder_sub_row')){
						var tmpl_params = {slug: ui.item.data('module-slug'), name: ui.item.data('module-name') },
							module_item_tmpl = wp.template('builder_module_item'),
							module_item = module_item_tmpl( tmpl_params ),
							$newElems = $(module_item);
						
						$( this ).parent().find( ".empty_holder_text" ).hide();
						ui.item.replaceWith($newElems);
						$newElems.find('.themify_module_options').trigger('click');
						self.moduleEvents();
					} else {
						// Make sub_row only can nested one level
						if ( ui.item.hasClass('themify_builder_sub_row') && ui.item.parents('.themify_builder_sub_row').length ) {
							var $clone_for_move = ui.item.find('.active_module').clone();
							$clone_for_move.insertAfter(ui.item);
							ui.item.remove();
						}

						self.newRowAvailable();
						self.moduleEvents();
					}
					self.equalHeight();
				}
			}).disableSelection();

			$( "#themify_builder_row_wrapper" ).sortable({
				items: '.themify_builder_row',
				handle: '.themify_builder_row_top',
				axis: 'y',
				placeholder: 'themify_builder_ui_state_highlight',
				sort: function( event, ui ){
					var placeholder_h = ui.item.height();
					$('.themify_builder_row_panel .themify_builder_ui_state_highlight').height(placeholder_h);
				}
			}).disableSelection();

			var grid_menu_tmpl = wp.template( 'builder_grid_menu' ),
				grid_menu_render = grid_menu_tmpl({});
			$('.themify_builder_row_content').each(function(){
				$(this).children().each(function(){
					var $holder = $(this).find('.themify_module_holder').first();
					$holder.children('.themify_builder_module').each(function(){
						if ( $(this).find('.grid_menu').length == 0 ) {
							$(this).append($(grid_menu_render));
						}
					});
				});
			});
		},

		getDocHeight: function(){
			var D = document;
			return Math.max(
				Math.max(D.body.scrollHeight, D.documentElement.scrollHeight),
				Math.max(D.body.offsetHeight, D.documentElement.offsetHeight),
				Math.max(D.body.clientHeight, D.documentElement.clientHeight)
			);
		},

		setupLightbox: function() {
			var isThemifyTheme = 'true' == themifyBuilder.isThemifyTheme? 'is-themify-theme' : 'is-not-themify-theme',
				lightbox_tmpl = wp.template( 'builder_lightbox' ),
				markup = lightbox_tmpl( { is_themify_theme: isThemifyTheme } );
			$(markup).hide().find('#themify_builder_lightbox_parent').hide().end().appendTo('body');
		},

		toggleRow: function(e) {
			e.preventDefault();
			$(this).parents('.themify_builder_row').toggleClass('collapsed').find('.themify_builder_row_content').slideToggle();
		},

		deleteRow: function(e) {
			e.preventDefault();
			var row_length = $(this).closest('.themify_builder_row_js_wrapper').find('.themify_builder_row:visible').length;
			if(row_length > 1) {
				$(this).closest('.themify_builder_row').remove();
			}
			else {
				$(this).closest('.themify_builder_row').hide();
			}
		},

		duplicateRow: function(e) {
			e.preventDefault();
			var self = ThemifyPageBuilder,
					wrapper = $(this).parents('.themify_builder_row_js_wrapper'),
					oriElems = $(this).closest('.themify_builder_row'),
					newElems = $(this).closest('.themify_builder_row').clone(true),
					row_count = $('#tfb_module_settings .themify_builder_row_js_wrapper').find('.themify_builder_row:visible').length + 1,
					number = row_count + Math.floor(Math.random() * 9);

			// fix wpeditor empty textarea
			newElems.find('.tfb_lb_wp_editor.tfb_lb_option_child').each(function(){
				var this_option_id = $(this).attr('id'),
					element_val;

				if( typeof tinyMCE !== 'undefined') {
					element_val = $(this).is(':hidden') ? tinyMCE.get( this_option_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( this_option_id ).value );
				} else {
					element_val = $('#' + this_option_id).val();
				}
				$(this).val(element_val);
				$(this).addClass('clone');
			});

			// fix textarea field clone
			newElems.find('textarea:not(.tfb_lb_wp_editor)').each(function(i){
				var insertTo = oriElems.find('textarea').eq(i).val();
				if( insertTo != '') {
					$(this).val(insertTo);
				}
			});

			// fix radio button clone
			newElems.find('.themify-builder-radio-dnd').each(function(i){
				var oriname = $(this).attr('name');
				$(this).attr('name', oriname + '_' + row_count);
				$(this).attr('id', oriname + '_' + row_count + '_' + i);
				$(this).next('label').attr('for', oriname + '_' + row_count + '_' + i);
			});

			newElems.find('.themify-builder-plupload-upload-uic').each(function(i){
				$(this).attr('id', 'pluploader_' + row_count + number + i + 'themify-builder-plupload-upload-ui');
				$(this).find('input[type=button]').attr('id', 'pluploader_' + row_count + number + i + 'themify-builder-plupload-browse-button');
				$(this).addClass('plupload-clone');
			});

			newElems.insertAfter(oriElems).find('.themify_builder_dropdown').hide();

			$('#tfb_module_settings').find('.tfb_lb_wp_editor.tfb_lb_option_child.clone').each(function(i){
				var element = $(this),
						element_val = element.val(),
						parent_child = element.closest('.themify_builder_input');

				$(this).closest('.wp-editor-wrap').remove();
				
				var oriname = element.attr('name');
				element.attr('id', oriname + '_' + row_count + number + '_' + i);
				element.attr('class').replace('wp-editor-area', '');

				element.appendTo(parent_child).wrap('<div class="wp-editor-wrap"/>');

			});

			self.addNewWPEditor();
			self.builderPlupload('new_elemn');
			self.moduleEvents();
		},

		menuTouched: [],

		MenuHover: function(e) {
			if ( 'touchend' == e.type ) {
				var $row = $(this).closest('.themify_builder_row'),
					$col = $(this).closest('.themify_builder_col'),
					$mod = $(this).closest('.themify_builder_module'),
					index = 'row_' + $row.index();
				if ( $col.length > 0 ) {
					index += '_col_' + $col.index();
				}
				if ( $mod.length > 0 ) {
					index += '_mod_' + $mod.index();
				}
				if ( ThemifyPageBuilder.menuTouched[index] ) {
					$(this).find('.themify_builder_dropdown').stop(false,true).hide();
					$row.css('z-index', '');
					ThemifyPageBuilder.menuTouched = [];
				} else {
					var $builderCont = $('#themify_builder_row_wrapper');
					$builderCont.find('.themify_builder_dropdown').stop(false,true).hide();
					$builderCont.find('.themify_builder_row').css('z-index', '');
					$(this).find('.themify_builder_dropdown').stop(false,true).show();
					$row.css('z-index', '998');
					ThemifyPageBuilder.menuTouched = [];
					ThemifyPageBuilder.menuTouched[index] = true;
				}
			} else if(e.type=='mouseenter') {
				$(this).find('.themify_builder_dropdown').stop(false,true).show();
			} else if(e.type=='mouseleave') {
				$(this).find('.themify_builder_dropdown').stop(false,true).hide();
			}
		},

		optionsModule: function(e) {
			e.preventDefault();
			var self = ThemifyPageBuilder,
					module_name = $(this).data('module-name'),
					set_elems = $(this).closest('.themify_builder_module').find('.themify_module_settings').find('script[type="text/json"]'),
					is_settings_exist = (set_elems.text().trim().length > 2 && set_elems.text().trim() != 'null') ? true : false,
					el_settings = (is_settings_exist) ? JSON.parse(set_elems.text().trim()) : '';

			$('.module_menu .themify_builder_dropdown').hide();
			$('#themify_builder_lightbox_container').empty();
			$('#themify_builder_overlay').addClass( 'tfb-lightbox-open' ).show();
			self.preLoader.appendTo('body');

			// assigned selected module class
			$('#themify_builder_row_wrapper .themify_builder_module').removeClass('current_selected_module');
			var $tfb_active_module = $(this).parents('.themify_builder_module');
			$tfb_active_module.addClass('current_selected_module');
			$('body').addClass('noScroll');

			$.ajax({
				type: "POST",
				url: themifyBuilder.ajaxurl,
				data:
				{
					action : 'tfb_lightbox_options',
					nonce : themifyBuilder.tfb_load_nonce,
					tfb_module_name : module_name
				},
				success: function( data ){
					$( document ).on( 'keyup', ThemifyPageBuilder.lightboxCloseKeyListener );
					var top = $(document).scrollTop() + 80,
						$newElems = $(data);

					$("#themify_builder_lightbox_parent")
					.show()
					.css('top', self.getDocHeight())
					.animate({
						top: 100
					}, 800 );

					$('#themify_builder_loader').remove();
					
					$('#themify_builder_lightbox_container').append($newElems);

					$('#tfb_module_settings .tfb_lb_option').each( function(){
						var $this_option = $(this),
							this_option_id = $this_option.attr('id'),
							$found_element = el_settings[this_option_id];
						
						if ( $found_element ){
							if ( $this_option.hasClass('select_menu_field') ){
								if ( !isNaN( $found_element ) ) {
									$this_option.find("option[data-termid='" + $found_element + "']").attr('selected','selected');
								} else {
									$this_option.find("option[value='" + $found_element + "']").attr('selected','selected');
								}
							} else if ( $this_option.is('select') ){
								$this_option.val( $found_element );
							} else if( $this_option.hasClass('themify-builder-uploader-input') ) {
								var img_field = $found_element,
										img_thumb = $('<img/>', {src: img_field, width: 50, height: 50});

								if( img_field != '' ){
									$this_option.val(img_field);
									$this_option.parent().find('.img-placeholder').empty().html(img_thumb);
								}
								else{
									$this_option.parent().find('.thumb_preview').hide();
								}

							} else if($this_option.hasClass('themify-option-query-cat')){
								var parent = $this_option.parent(),
										single_cat = parent.find('.query_category_single'),
										multiple_cat  = parent.find('.query_category_multiple'),
										elems = $found_element,
										value = elems.split('|'),
										cat_type = value[1],
										cat_val = value[0];

								parent.find("option[value='" + cat_val + "']").attr('selected','selected');
								multiple_cat.val( cat_val );

							} else if( $this_option.hasClass('themify_builder_row_js_wrapper') ) {
								var row_append = 0;
								if($found_element.length > 0){
									row_append = $found_element.length - 1;
								}

								// add new row
								for (var i = 0; i < row_append; i++) {
									$this_option.parent().find('.add_new a').first().trigger('click');
								}

								$this_option.find('.themify_builder_row').each(function(r){
									$(this).find('.tfb_lb_option_child').each(function(i){
										var $this_option_child = $(this),
										this_option_id_real = $this_option_child.attr('id'),
										this_option_id_child = $this_option_child.hasClass('tfb_lb_wp_editor') ? $this_option_child.attr('name') : $this_option_child.data('input-id'),
										$found_element_child = $found_element[r][''+ this_option_id_child +''];
										
										if( $this_option_child.hasClass('themify-builder-uploader-input') ) {
											var img_field = $found_element_child,
													img_thumb = $('<img/>', {src: img_field, width: 50, height: 50});

											if( img_field != '' && img_field != undefined ){
												$this_option_child.val(img_field);
												$this_option_child.parent().find('.img-placeholder').empty().html(img_thumb).parent().show();
											}
											else{
												$this_option_child.parent().find('.thumb_preview').hide();
											}

										}
										else if( $this_option_child.hasClass('tf-radio-choice') ){
											$this_option_child.find("input[value='" + $found_element_child + "']").attr('checked','checked');
										} else if( $this_option_child.hasClass( 'themify-layout-icon' ) ) {
											$this_option_child.find( '#' + $found_element_child ).addClass( 'selected' );
										}
										else if( $this_option_child.is('input, textarea, select') ){
											$this_option_child.val($found_element_child);
										}

										if ( $this_option_child.hasClass('tfb_lb_wp_editor') && !$this_option_child.hasClass('clone') ) {
											self.initQuickTags(this_option_id_real);
											if ( typeof tinyMCE !== 'undefined' ) {
												self.initNewEditor( this_option_id_real );
											}
										}

									});
								});

							} else if ( $this_option.hasClass('tf-radio-input-container') ){
								$this_option.find("input[value='" + $found_element + "']").attr('checked', 'checked');  
								var selected_group = $this_option.find('input[name="'+this_option_id+'"]:checked').val();
								
								// has group element enable
								if($this_option.hasClass('tf-option-checkbox-enable')){
									$('.tf-group-element').hide();
									$('.tf-group-element-' + selected_group ).show();
								}

							} else if ( $this_option.is('input, textarea') ){
								$this_option.val( $found_element );
							} else if ( $this_option.hasClass('themify-checkbox') ){
								var cselected = $found_element;
								cselected = cselected.split('|');

								$this_option.find('.tf-checkbox').each(function(){
									if($.inArray($(this).val(), cselected) > -1){
										$(this).prop('checked', true);
									}
									else{
										$(this).prop('checked', false);
									}
								});

							} else if ( $this_option.hasClass('themify-layout-icon') ) {
									$this_option.find('#' + $found_element).addClass('selected');
							} else { 
								$this_option.html( $found_element );
							}
						}
						else{
							if ( $this_option.hasClass('themify-layout-icon') ){
								$this_option.children().first().addClass('selected');
							}
							else if ( $this_option.hasClass('themify-builder-uploader-input') ) {
								$this_option.parent().find('.thumb_preview').hide();
							}
							else if ( $this_option.hasClass('tf-radio-input-container') ) {
								$this_option.find('input[type=radio]').first().prop('checked');
								var selected_group = $this_option.find('input[name="'+this_option_id+'"]:checked').val();
								
								// has group element enable
								if($this_option.hasClass('tf-option-checkbox-enable')){
									$('.tf-group-element').hide();
									$('.tf-group-element-' + selected_group ).show();
								}
							}
							else if( $this_option.hasClass('themify_builder_row_js_wrapper') ){
								$this_option.find('.themify_builder_row').each(function(r){
									$(this).find('.tfb_lb_option_child').each(function(i){
										var $this_option_child = $(this),
										this_option_id_real = $this_option_child.attr('id');

										if ( $this_option_child.hasClass('tfb_lb_wp_editor') ) {
											
											var this_option_id_child = $this_option_child.data('input-id');

											self.initQuickTags(this_option_id_real);
											if ( typeof tinyMCE !== 'undefined' ) {
												self.initNewEditor( this_option_id_real );
											}
										}

									});
								});
							}
							else if( $this_option.hasClass('themify-checkbox') && is_settings_exist ) {
								$this_option.find('.tf-checkbox').each(function(){
									$(this).prop('checked', false);
								});
							}
							else if( $this_option.is('input, textarea') && is_settings_exist ) {
								$this_option.val('');
							}
						}

						if ( $this_option.hasClass('tfb_lb_wp_editor') ) {
							self.initQuickTags(this_option_id);
							if ( typeof tinyMCE !== 'undefined' ) {
								self.initNewEditor( this_option_id );
							}
						}

					});

					// Trigger event
					$('body').trigger( 'editing_module_option', [ el_settings ] );
	
					// add new wp editor
					self.addNewWPEditor();

					// colorpicker
					self.setColorPicker();

					// plupload init
					self.builderPlupload('normal');

					// option binding setup
					self.moduleOptionsBinding();

					// builder drag n drop init
					self.moduleOptionBuilder();

					// tabular options
					$('.themify_builder_tabs').tabs();

					$('#themify_builder_lightbox_parent').show();

					$('#themify_builder_lightbox_parent').find('select').wrap('<div class="selectwrapper"></div>');
					$('.selectwrapper').click(function(){
						$(this).toggleClass('clicked');
					});

				}
			});
		},

		moduleOptionsBinding: function(){
			var form = $( '#tfb_module_settings' );
			$( 'input[data-binding], textarea[data-binding], select[data-binding]', form ).change(function(){
				var logic = false,
					binding = $( this ).data( 'binding' ),
					val = $( this ).val();
				if( val == '' && binding['empty'] != undefined ) {
					logic = binding['empty'];
				} else if( val != '' && binding[val] != undefined ) {
					logic = binding[val];
				} else if( val != '' && binding['not_empty'] != undefined ) {
					logic = binding['not_empty'];
				}

				if( logic ) {
					if( logic['show'] != undefined ) {
						$.each( logic['show'], function( i, v ){
							$( '.' + v ).show();
						} );
					}
					if( logic['hide'] != undefined ) {
						$.each( logic['hide'], function( i, v ){
							$( '.' + v ).hide();
						} );
					}
				}
			}).change();
		},

		dblOptionModule: function(e) {
			e.preventDefault();
			$(this).find('.themify_module_options').trigger('click');
		},

		duplicateModule: function(e) {
			e.preventDefault();
			var holder = $(this).closest('.themify_builder_module'),
					self = ThemifyPageBuilder;
			$(this).closest('.themify_builder_dropdown').hide();
			$(this).closest('.themify_builder_module').clone().insertAfter(holder).find('.themify_builder_dropdown').hide();
			self.equalHeight();
		},

		deleteModule: function(e) {
			e.preventDefault();
			var self = ThemifyPageBuilder;
			self.switchPlaceholdModule($(this));
			$(this).parents('.themify_builder_module').remove();
			self.newRowAvailable();
			self.equalHeight();
			self.moduleEvents();
		},

		addModule: function(e) {
			e.preventDefault();
			var self = ThemifyPageBuilder,
				tmpl_params = {slug: $(this).closest('.themify_builder_module').data('module-slug'), name: $(this).closest('.themify_builder_module').data('module-name') },
				module_item_tmpl = wp.template( 'builder_module_item' ),
				module_item = module_item_tmpl( tmpl_params ),
				dest = $('.themify_builder_row_panel').find('.themify_builder_row:visible').first().find('.themify_module_holder').first(),
				$newElems = $(module_item),
				position = $newElems.appendTo(dest);
					
			$('html,body').animate({ scrollTop: position.offset().top - 300 }, 500);
			self.moduleEvents();
			self.equalHeight();
			$newElems.find('.themify_module_options').trigger('click');
		},

		lightboxCloseKeyListener : function(e){
			if( e.keyCode == 27 ) {
				e.preventDefault();
				ThemifyPageBuilder.closeLightBox(e);
			}
		},

		closeLightBox: function(e) {
			e.preventDefault();
			$( document ).off( 'keyup', ThemifyPageBuilder.lightboxCloseKeyListener );
			var self = ThemifyPageBuilder;

			var $tfb_dialog_form = $('form#tfb_module_settings');
			
			if ( typeof tinyMCE !== 'undefined' ) {
				$tfb_dialog_form.find('.tfb_lb_wp_editor').each( function(){
					var $id = $(this).prop('id');
					switchEditors.go($id, 'tmce');
				});
			}
			
			$('#themify_builder_lightbox_parent').animate({
				top: self.getDocHeight()
			}, 800, function() {
				// Animation complete.
				$('#themify_builder_lightbox_container').empty();
				$('.themify_builder_lightbox_title').text('');
				$('#themify_builder_overlay, #themify_builder_lightbox_parent').hide();
				$('#themify_builder_overlay').removeClass( 'tfb-lightbox-open' );
				self.deleteEmptyModule();
				$('body').removeClass('noScroll');
			});
		},

		initNewEditor: function(editor_id) {
			var self = ThemifyPageBuilder;
			if ( typeof tinyMCEPreInit.mceInit[editor_id] !== "undefined" ) {
				self.initMCEv4( editor_id, tinyMCEPreInit.mceInit[editor_id] );
				return;
			}
			var tfb_new_editor_object = self.tfb_hidden_editor_object;
			
			tfb_new_editor_object['elements'] = editor_id;
			tfb_new_editor_object['selector'] = '#' + editor_id;
			tinyMCEPreInit.mceInit[editor_id] = tfb_new_editor_object;

			// v4 compatibility
			self.initMCEv4( editor_id, tinyMCEPreInit.mceInit[editor_id] );
		},

		initMCEv4: function( editor_id, $settings ){
			// v4 compatibility
			if( parseInt( tinyMCE.majorVersion) > 3 ) {
				// Creates a new editor instance
				var ed = new tinyMCE.Editor(editor_id, $settings, tinyMCE.EditorManager);	
				ed.render();
			}
		},

		initQuickTags: function(editor_id) {
			// add quicktags
			if ( typeof(QTags) == 'function' ) {
				quicktags( {id: editor_id} );
				QTags._buttonsInit();
			}
		},

		switchPlaceholdModule: function(obj) {
			var check = obj.parents('.themify_module_holder');
			if(check.find('.themify_builder_module').length == 1) {
				check.find('.empty_holder_text').show();
			}
		},

		PlaceHoldDragger: function(){
			$('.themify_module_holder').each(function(){
				if($(this).find('.themify_builder_module').length == 0){
					$(this).find('.empty_holder_text').show();
				}
			});
		},

		makeEqual: function( $obj, target ) {
			$obj.each(function(){
				var t = 0;
				$(this).find(target).children().each(function(){
					var $holder = $(this).find('.themify_module_holder').first();
					$holder.css('min-height', '');
					if ( $holder.height() > t ) {
						t=$holder.height();
					}
				});
				$(this).find(target).children().each(function(){
					$(this).find('.themify_module_holder').first().css('min-height', t + 'px');
				});
			});
		},

		equalHeight: function(){
			ThemifyPageBuilder.makeEqual( $('.themify_builder_row:visible'), '.themify_builder_row_content:visible');
			ThemifyPageBuilder.makeEqual( $('.themify_builder_sub_row:visible'), '.themify_builder_sub_row_content');
		},

		saveData: function(loader, callback, saveto){
			saveto = saveto || 'main';
			var self = ThemifyPageBuilder,
				dataSend = self.retrieveData(),
				ids = [{ id: $('input#post_ID').val(), data: dataSend }];
			
			$.ajax({
				type: "POST",
				url: themifyBuilder.ajaxurl,
				data:
				{
					action : 'tfb_save_data',
					tfb_load_nonce : themifyBuilder.tfb_load_nonce,
					ids : JSON.stringify( ids ),
					tfb_saveto : saveto
				},
				cache: false,
				beforeSend: function ( xhr ){
					if(loader){
						$('#themify_builder_alert').addClass('busy').show();
					}
				},
				success: function( data ){
					if(loader){
						$('#themify_builder_alert').removeClass('busy').addClass('done');

						setTimeout(function(){
							$('#themify_builder_alert').fadeOut('slow').removeClass('done');
						},1000);
					}

					// load callback
					if( $.isFunction(callback) ){
						callback.call(this, data);
					}

				}
			});
		},

		moduleSave: function(e){
			var self = ThemifyPageBuilder,
					$active_module_settings = $('.current_selected_module .themify_module_settings'),
					parent_active_mod = $active_module_settings.parent(),
					temp_appended_data = {};
			
			$('#tfb_module_settings .tfb_lb_option').each(function(){
				var option_value, option_class,
					this_option_id = $(this).attr('id');

					option_class = this_option_id + ' tfb_module_setting';

				if ( $(this).hasClass('tfb_lb_wp_editor') && !$(this).hasClass('builder-field') ){
					if ( typeof tinyMCE !== 'undefined' ) {
						option_value = $(this).is(':hidden') ? tinyMCE.get( this_option_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( this_option_id ).value );
					} else {
						option_value = $(this).val();
					}

					if( parent_active_mod.hasClass('text') ){
						var excerpt = self.limitString(option_value, 100);
						parent_active_mod.find('.module_excerpt').empty().html(excerpt);
					}
				}
				else if ( $(this).hasClass('themify-checkbox') ) {
					var cselected = [];
					$(this).find('.tf-checkbox:checked').each(function(i){
						cselected.push($(this).val());
					});
					if ( cselected.length > 0 ) {
						option_value = cselected.join('|');
					} else {
						option_value = '|';
					}
				}
				else if ( $(this).hasClass('themify-layout-icon') ) {
					if( $(this).find('.selected').length > 0 ){
						option_value = $(this).find('.selected').attr('id');
					}
					else{
						option_value = $(this).children().first().attr('id');
					}
				}
				else if ( $(this).hasClass('themify-option-query-cat') ) {
					var parent = $(this).parent(),
							single_cat = parent.find('.query_category_single'),
							multiple_cat  = parent.find('.query_category_multiple');

					if( multiple_cat.val() != '' ) {
						option_value = multiple_cat.val() + '|multiple';
					} else {
						option_value = single_cat.val() + '|single';
					}
				}
				else if( $(this).hasClass('themify_builder_row_js_wrapper') ){
					var row_items = [];
					$(this).find('.themify_builder_row').each(function(){
						var temp_rows = {};
						$(this).find('.tfb_lb_option_child').each(function(){
							var option_value_child,
							this_option_id_child = $(this).data('input-id');

							if( $(this).hasClass('tf-radio-choice') ){
								option_value_child = ($(this).find(':checked').length > 0) ? $(this).find(':checked').val() : '';
							} else if( $( this ).hasClass( 'themify-layout-icon' ) ){
								if( $(this).find('.selected').length > 0 ){
									option_value_child = $(this).find('.selected').attr('id');
								}
								else{
									option_value_child = $(this).children().first().attr('id');
								}
							}
							else if ($(this).hasClass('tfb_lb_wp_editor')){
								var text_id = $(this).attr('id');
								this_option_id_child = $(this).attr('name');
								if ( typeof tinyMCE !== 'undefined' ) {
									option_value_child = $(this).is(':hidden') ? tinyMCE.get( text_id ).getContent() : switchEditors.wpautop( tinymce.DOM.get( text_id ).value );
								} else {
									option_value_child = $(this).val();
								}
							}
							else{
								option_value_child = $(this).val();
							}

							if( option_value_child ) {
								temp_rows[this_option_id_child] = option_value_child;
							}
						});
						row_items.push(temp_rows);
					});
					option_value = row_items;
				}
				else if ( $(this).hasClass('tf-radio-input-container') ) {
					option_value = $(this).find('input[name="'+this_option_id+'"]:checked').val();
				}
				else if ( $(this).hasClass('module-widget-form-container') ) {
					option_value = $(this).find(':input').serializeObject();
				}
				else if ( $(this).is('select, input, textarea') ) {
					option_value = $(this).val();
				}

				if( option_value ) {
					temp_appended_data[this_option_id] = option_value;
				}
			});

			$active_module_settings.find('script[type="text/json"]').text( JSON.stringify( temp_appended_data ) );
			
			$('#themify_builder_lightbox_parent').hide();
			$('.close_lightbox').trigger('click');

			// clear empty module
			self.deleteEmptyModule();
			self.newRowAvailable();
			self.moduleEvents();

			e.preventDefault();
		},

		postSave: function(e){
			if( $('#themify_builder_row_wrapper').is(':visible') ){
				var self = ThemifyPageBuilder,
					_this = $(this);

				if( !self.isPostSave ){
					self.saveData(false, function(){
						self.isPostSave = true;
						$('input#publish').trigger('click');
					});
					e.preventDefault();
				}
				else{
					self.isPostSave = false;
					return true;  
				}
			}
		},

		switchFrontEnd: function(e){
			if( $('#themify_builder_row_wrapper').is(':visible') ){
				var self = ThemifyPageBuilder,
					_this = $(this),
					targetLink = themifyBuilder.permalink;

				$('#themify_builder_alert').addClass('busy').show();
				self.saveData(false, function(){
					var new_url = targetLink.replace( /\&amp;/g, '&' ) + '#builder_active';
					window.location.href = new_url;
				});
				e.preventDefault();
			}
		},

		mainSave: function(e){
			var self = ThemifyPageBuilder;

			self.saveData(true);
			e.preventDefault();
		},

		retrieveData: function(){
			var self = ThemifyPageBuilder,
				option_data = {},
				cols = {},
				modules = {};

			// rows
			$('#themify_builder_row_wrapper .themify_builder_row:visible').each(function(r){
				var row_order = r,
					cols = {};

				if($(this).find('.themify_builder_module').length > 0){
					// cols
					$(this).find('.themify_builder_row_content').children('.themify_builder_col').each(function(c){
						var grid_class = self.filterClass($(this).attr('class')),
								modules = {};
						// mods
						$(this).find('.themify_module_holder').first().children().each(function(m){
							if ( $(this).hasClass('themify_builder_module') ) {
								var mod_name = $(this).data('mod-name'),
								mod_elems = $(this).find('.themify_module_settings'),
								mod_settings = JSON.parse( mod_elems.find('script[type="text/json"]').text() );
								modules[m] = {'mod_name': mod_name, 'mod_settings': mod_settings};
							}

							// Sub Rows
							if ( $(this).hasClass('themify_builder_sub_row') ) {
								var sub_cols = {};
								$(this).find('.themify_builder_col').each(function(sub_col){
									var sub_grid_class = self.filterClass($(this).attr('class')),
										sub_modules = {};

									$(this).find('.active_module').each(function(sub_m){
										var sub_mod_name = $(this).data('mod-name'),
										sub_mod_elems = $(this).find('.themify_module_settings'),
										sub_mod_settings = JSON.parse( sub_mod_elems.find('script[type="text/json"]').text() );
										sub_modules[sub_m] = {'mod_name': sub_mod_name, 'mod_settings': sub_mod_settings};
									});
									sub_cols[ sub_col ] = { grid_class: sub_grid_class, modules: sub_modules };
								});

								modules[m] = { row_order: m, gutter: $(this).data('gutter'), cols: sub_cols };
							}
						});

						cols[c] = {'grid_class': grid_class, 'modules': modules};

					});

					option_data[r] = {row_order: r, gutter: $(this).data('gutter'), cols: cols };
				} else {
					option_data[r] = {};
				}

				// get row styling
				if ( $(this).find('.row-data-styling').length > 0 ){
					var $data_styling = $(this).find('.row-data-styling').data('styling');
					if( 'object' === typeof $data_styling ) 
						option_data[r].styling = $data_styling;
				}

			});

			return option_data;
		},

		filterClass: function(str){
			var grid = ThemifyPageBuilder.gridClass.concat(['first', 'last']),
				n = str.split(' '),
				new_arr = [];

				for (var i = 0; i < n.length; i++) {
					if($.inArray(n[i], grid) > -1){
						new_arr.push(n[i]);
					}
				}

			return new_arr.join(' ');
		},

		limitString: function(str, limit){
			var new_str;

			if($(str).text().length > limit ){
				new_str = $(str).text().substr(0, limit);
			}
			else{
				new_str = $(str).text();
			}

			return new_str;
		},

		mediaUploader: function() {
			
			// Uploading files
			var $body = $('body');
			
			// Field Uploader
			$body.on('click', '.themify-builder-media-uploader', function( event ){
				var $el = $(this);

				// Create the media frame.
				var file_frame = wp.media.frames.file_frame = wp.media({
					title: $(this).data('uploader-title'),
					library: {
						type: 'image'
					},
					button: {
						text: $(this).data('uploader-button-text')
					},
					multiple: false  // Set to true to allow multiple files to be selected
				});
		 
				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					var attachment = file_frame.state().get('selection').first().toJSON();
		 
					// Do something with attachment.id and/or attachment.url here
					$el.closest('.themify_builder_input').find('.themify-builder-uploader-input').val(attachment.url).trigger( 'change' )
					.parent().find('.img-placeholder').empty()
					.html($('<img/>', {src: attachment.url, width: 50, height:50}))
					.parent().show();
				});
		 
				// Finally, open the modal
				file_frame.open();
				event.preventDefault();
			});

			// delete button
			$body.on('click', '.themify-builder-delete-thumb', function(e){
				$(this).prev().empty().parent().hide();
				$(this).parents('.themify_builder_input').find('.themify-builder-uploader-input').val('');
				e.preventDefault();
			});

			// Media Buttons
			$body.on('click', '.insert-media', function(e) {
				window.wpActiveEditor = $(this).data('editor');
			});
		},

		builderPlupload: function(action_text) {
			var class_new = action_text == 'new_elemn' ? '.plupload-clone' : '',
				$builderPlupoadUpload = $(".themify-builder-plupload-upload-uic" + class_new);

			if($builderPlupoadUpload.length > 0) {
				var pconfig=false;
				$builderPlupoadUpload.each(function() {
					var $this=$(this);
					var id1=$this.attr("id");
					var imgId=id1.replace("themify-builder-plupload-upload-ui", "");

					pconfig=JSON.parse(JSON.stringify(themify_builder_plupload_init));

					pconfig["browse_button"] = imgId + pconfig["browse_button"];
					pconfig["container"] = imgId + pconfig["container"];
					pconfig["drop_element"] = imgId + pconfig["drop_element"];
					pconfig["file_data_name"] = imgId + pconfig["file_data_name"];
					pconfig["multipart_params"]["imgid"] = imgId;
					//pconfig["multipart_params"]["_ajax_nonce"] = $this.find(".ajaxnonceplu").attr("id").replace("ajaxnonceplu", "");
					pconfig["multipart_params"]["_ajax_nonce"] = themifyBuilder.tfb_load_nonce;
					pconfig["multipart_params"]['topost'] = $('input#post_ID').val();

					var uploader = new plupload.Uploader(pconfig);

					uploader.bind('Init', function(up){});
					uploader.init();

					// a file was added in the queue
					uploader.bind('FilesAdded', function(up, files){
						up.refresh();
						up.start();
						$('#themify_builder_alert').addClass('busy').show();
					});

					uploader.bind('Error', function(up, error){
						var $promptError = $('.prompt-box .show-error');
						$('.prompt-box .show-login').hide();
						$promptError.show();
						
						if($promptError.length > 0){
							$promptError.html('<p class="prompt-error">' + error.message + '</p>');
						}
						$(".overlay, .prompt-box").fadeIn(500);
					});

					// a file was uploaded
					uploader.bind('FileUploaded', function(up, file, response) {
						var json = JSON.parse(response['response']), status;
						
						if('200' == response['status'] && !json.error) {
							status = 'done';
						} else {
							status = 'error';
						}
						
						$("#themify_builder_alert").removeClass("busy").addClass(status).delay(800).fadeOut(800, function() {
							$(this).removeClass(status);
						});
						
						if(json.error){
							alert(json.error);
							return;
						}

						var response_file = json.file,
							response_url = json.large_url,
							thumb_url = json.thumb;

						$this.parents('.themify_builder_input').find('.themify-builder-uploader-input').val(response_url).trigger( 'change' )
						.parent().find('.img-placeholder').empty()
						.html($('<img/>', {src: thumb_url, width: 50, height:50}))
						.parent().show();

					});
					
					$this.removeClass('plupload-clone');
				});
			}
		},

		moduleOptionBuilder: function() {

			// sortable accordion builder
			$( ".themify_builder_module_opt_builder_wrap" ).sortable({
				items: '.themify_builder_row',
				handle: '.themify_builder_row_top',
				axis: 'y',
				placeholder: 'themify_builder_ui_state_highlight',
				start: function( event, ui ) {
					if ( typeof tinyMCE !== 'undefined' ) {
						$('#tfb_module_settings').find('.tfb_lb_wp_editor.tfb_lb_option_child').each(function(){
							var id = $(this).attr('id'),
								content = tinymce.get(id).getContent();
							$(this).data('content', content);
							tinyMCE.execCommand('mceRemoveEditor', false, id);
						});
					}
				},
				stop: function( event, ui ) {
					if ( typeof tinyMCE !== 'undefined' ) {
						$('#tfb_module_settings').find('.tfb_lb_wp_editor.tfb_lb_option_child').each(function(){
							var id = $(this).attr('id');
							tinyMCE.execCommand('mceAddEditor', false, id);
							tinymce.get(id).setContent($(this).data('content'));
						});
					}
				},
				sort: function( event, ui ){
					var placeholder_h = ui.item.height();
					$('.themify_builder_module_opt_builder_wrap .themify_builder_ui_state_highlight').height(placeholder_h);
				}
			});
		},

		moduleOptAddRow: function(e) {
			var self = ThemifyPageBuilder,
					parent = $(this).parent().prev(), 
					template = parent.find('.themify_builder_row').first().clone(),
					row_count = $('.themify_builder_row_js_wrapper').find('.themify_builder_row:visible').length + 1,
					number = row_count + Math.floor(Math.random() * 9);

			// clear form data
			template.removeClass('collapsed').find('.themify_builder_row_content').show();
			template.find('.themify-builder-radio-dnd').each(function(i){
				var oriname = $(this).attr('name');
				$(this).attr('name', oriname + '_' + row_count).prop('checked', false);
				$(this).attr('id', oriname + '_' + row_count + '_' + i);
				$(this).next('label').attr('for', oriname + '_' + row_count + '_' + i);
			});

			template.find( '.themify-layout-icon a' ).removeClass( 'selected' );

			template.find('.thumb_preview').each(function(){
				$(this).find('.img-placeholder').html('').parent().hide();
			});
			template.find('input[type=text], textarea').each(function(){
				$(this).val('');
			});
			template.find('.tfb_lb_wp_editor.tfb_lb_option_child').each(function(){
				$(this).addClass('clone');
			});
			template.find('.themify-builder-plupload-upload-uic').each(function(i){
				$(this).attr('id', 'pluploader_' + row_count + number + i + 'themify-builder-plupload-upload-ui');
				$(this).find('input[type=button]').attr('id', 'pluploader_' + row_count + number + i + 'themify-builder-plupload-browse-button');
				$(this).addClass('plupload-clone');
			});

			// Fix color picker input
			template.find('.builderColorSelectInput').each(function(){
				var thiz = $(this),
					input = thiz.clone().val(''),
					parent = thiz.closest('.themify_builder_field');
				thiz.prev().minicolors('destroy').removeAttr('maxlength');
				parent.find( '.colordisplay' ).wrap( '<div class="themify_builder_input" />' ).before( '<span class="builderColorSelect"><span></span></span>' ).after( input );
				self.setColorPicker(parent);
			});

			$(template).appendTo(parent).show();

			$('#tfb_module_settings').find('.tfb_lb_wp_editor.tfb_lb_option_child.clone').each(function(i){
				var element = $(this),
						element_val = element.val(),
						parent_child = element.closest('.themify_builder_input');

				$(this).closest('.wp-editor-wrap').remove();
				
				var oriname = element.attr('name');
				element.attr('id', oriname + '_' + row_count + number + '_' + i);
				element.attr('class').replace('wp-editor-area', '');

				element.appendTo(parent_child).wrap('<div class="wp-editor-wrap"/>');

			});

			if( e.which ) {
				self.addNewWPEditor();
				self.builderPlupload('new_elemn');
			}

			e.preventDefault();
		},

		clickCheckBoxOption: function(e) {
			var selected_group = $(this).data('selected');
			$('.tf-group-element').hide();
			$('.'+selected_group).show();
			$('.thumb_preview').each(function(){
				if($(this).find('img').length == 0) {
					$(this).hide();
				}
			});
		},

		deleteEmptyModule: function() {
			$('#themify_builder_row_wrapper').find('.themify_builder_module').each(function(){
				if($.trim($(this).find('.themify_module_settings').find('script[type="text/json"]').text()).length <= 2){
					$(this).remove();
				}
			});
		},

		openGallery: function() {
			
			var clone = wp.media.gallery.shortcode,
					file_frame;
			$('body').on('click', '.tf-gallery-btn', function( event ){
				var $el = $(this),
						shortcode_val = $(this).closest('.themify_builder_input').find('.tf-shortcode-input');
				
				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					frame:     'post',
					state:     'gallery-edit',
					title:     wp.media.view.l10n.editGalleryTitle,
					editing:   true,
					multiple:  true,
					selection: false
				});

				wp.media.gallery.shortcode = function(attachments) {
					var props = attachments.props.toJSON(),
					attrs = _.pick( props, 'orderby', 'order' );

					if ( attachments.gallery )
						_.extend( attrs, attachments.gallery.toJSON() );

					attrs.ids = attachments.pluck('id');

					// Copy the `uploadedTo` post ID.
					if ( props.uploadedTo )
						attrs.id = props.uploadedTo;

					// Check if the gallery is randomly ordered.
					if ( attrs._orderbyRandom )
						attrs.orderby = 'rand';
					delete attrs._orderbyRandom;

					// If the `ids` attribute is set and `orderby` attribute
					// is the default value, clear it for cleaner output.
					if ( attrs.ids && 'post__in' === attrs.orderby )
						delete attrs.orderby;

					// Remove default attributes from the shortcode.
					_.each( wp.media.gallery.defaults, function( value, key ) {
						if ( value === attrs[ key ] )
							delete attrs[ key ];
					});

					var shortcode = new wp.shortcode({
						tag:    'gallery',
						attrs:  attrs,
						type:   'single'
					});

					shortcode_val.val(shortcode.string());

					wp.media.gallery.shortcode = clone;
					return shortcode;
				}

				file_frame.on( 'update', function( selection ) {
					var shortcode = wp.media.gallery.shortcode( selection ).string().slice( 1, -1 );
					shortcode_val.val('[' + shortcode + ']');
				});
			
				if($.trim(shortcode_val.val()).length > 0) {
					file_frame = wp.media.gallery.edit($.trim(shortcode_val.val()));
					file_frame.state('gallery-edit').on( 'update', function( selection ) {
						var shortcode = wp.media.gallery.shortcode( selection ).string().slice( 1, -1 );
						shortcode_val.val('[' + shortcode + ']');
					});
				}
				else
				{
					file_frame.open();
					$('.media-menu').find('.media-menu-item').last().trigger('click');
				}
				event.preventDefault();
			});
			
		},

		addNewWPEditor: function() {
			var self = ThemifyPageBuilder;

			$('#tfb_module_settings').find('.tfb_lb_wp_editor.clone').each(function(i){
				var element = $(this),
						element_val = element.val(),
						parent = element.closest('.themify_builder_input');
				
				$(this).closest('.wp-editor-wrap').remove();
				
				var oriname = element.attr('name'),
						this_option_id_temp = element.attr('id'),
						this_class = element.attr('class').replace('wp-editor-area', '').replace('clone', '');

				$.ajax({
					type: "POST",
					url: themifyBuilder.ajaxurl,
					dataType: 'html',
					data:
					{
						action : 'tfb_add_wp_editor',
						tfb_load_nonce : themifyBuilder.tfb_load_nonce,
						txt_id : this_option_id_temp,
						txt_class : this_class,
						txt_name : oriname,
						txt_val : element_val
					},
					success: function( data ){
						var $newElems = $(data),
								this_option_id_clone = $newElems.find('.tfb_lb_wp_editor').attr('id');

						$newElems.appendTo(parent);

						self.initQuickTags(this_option_id_clone);
						if ( typeof tinyMCE !== 'undefined' ) {
							self.initNewEditor( this_option_id_clone );
						}
					}
				});

			});
		},

		moduleActions: function(){
			var $body = $('body'),
				$self = ThemifyPageBuilder;
			$body.on('change', '.module-widget-select-field', function(){
				var $seclass = $(this).val(),
					id_base = $(this).find(':selected').data('idbase');
				
				$.ajax({
					type: "POST",
					url: themifyBuilder.ajaxurl,
					dataType: 'html',
					data:
					{
						action : 'module_widget_get_form',
						tfb_load_nonce : themifyBuilder.tfb_load_nonce,
						load_class : $seclass,
						id_base : id_base
					},
					success: function( data ){
						var $newElems = $(data);

						$('.module-widget-form-placeholder').html($newElems);
						$('#themify_builder_lightbox_container').each(function() {
							var $this = $(this).find('#instance_widget');
							$this.find('select').wrap('<div class="selectwrapper"></div>');
						});
						$('.selectwrapper').click(function(){
							$(this).toggleClass('clicked');
						});
					}
				});
			});

			$body.on('editing_module_option', function(e, settings){
				var $field = $('#tfb_module_settings .tfb_lb_option.module-widget-select-field');
				if ( $field.length == 0 ) return;

				var $seclass = $field.val(),
					id_base = $field.find(':selected').data('idbase'),
					$instance = settings.instance_widget;

				$.ajax({
					type: "POST",
					url: themifyBuilder.ajaxurl,
					dataType: 'html',
					data:
					{
						action : 'module_widget_get_form',
						tfb_load_nonce : themifyBuilder.tfb_load_nonce,
						load_class : $seclass,
						id_base : id_base,
						widget_instance: $instance
					},
					success: function( data ){
						var $newElems = $(data);
						$('.module-widget-form-placeholder').html($newElems);
					}
				});
			});
		},

		newRowAvailable: function() {
			var self = ThemifyPageBuilder;

			$('.themify_builder_row_js_wrapper').each(function(){
				var $container = $(this),
					$parent = $container.find('.themify_builder_row:visible'),
					tmpl = wp.template( 'builder_row' ),
					$template = $( tmpl({}) );
				
				$parent.each(function(){
					var data_styling = $(this).find('.row-data-styling').data('styling');
					
					if( $(this).find('.themify_builder_module').length == 0 && ( typeof data_styling === 'string' || $.isEmptyObject( data_styling ) ) ){
						$(this).remove();
					}
				});

				if( $parent.find('.themify_builder_module').length > 0 || $container.find('.themify_builder_row:visible').length == 0){
					$template.appendTo($container);
				}
			});
		},

		showLoader: function(stats) {
			if(stats == 'show'){
				$('#themify_builder_alert').addClass('busy').show();
			}
			else if(stats == 'spinhide'){
				$("#themify_builder_alert").delay(800).fadeOut(800, function() {
					$(this).removeClass('busy');
				});
			}
			else{
				$("#themify_builder_alert").removeClass("busy").addClass('done').delay(800).fadeOut(800, function() {
					$(this).removeClass('done');
				});
			}
		},

		duplicatePage: function(e) {
			var self = ThemifyPageBuilder,
				reply = confirm(themifyBuilder.confirm_on_duplicate_page);
			if(reply) {
				self.saveData(true, function(){
					self.duplicatePageAjax();
				});
			} else {
				self.duplicatePageAjax();
			}
			
			e.preventDefault();
		},

		duplicatePageAjax: function(){
			var self = ThemifyPageBuilder;
			$.ajax({
				type: "POST",
				url: themifyBuilder.ajaxurl,
				dataType: 'json',
				data:
				{
					action : 'tfb_duplicate_page',
					tfb_load_nonce : themifyBuilder.tfb_load_nonce,
					tfb_post_id : $('input#post_ID').val(),
					tfb_is_admin: 1
				},
				beforeSend: function( xhr ){
					self.showLoader('show');
				},
				success: function( data ){
					self.showLoader('hide');
					var new_url = data.new_url.replace( /\&amp;/g, '&' );
					window.location.href = new_url;
				}
			});
		},

		optionRow: function(e) {
			e.preventDefault();
			var self = ThemifyPageBuilder,
				$this = $(this),
				$options = $this.closest('.themify_builder_row').find('.row-data-styling').data('styling');

			$('#themify_builder_lightbox_container').empty();
			$('#themify_builder_overlay').addClass( 'tfb-lightbox-open' ).show();
			self.showLoader('show');

			// highlight current selected row
			$('.themify_builder_row').removeClass('current_selected_row');
			$this.closest('.themify_builder_row').addClass('current_selected_row');
			$('body').addClass('noScroll');

			$.ajax({
				type: "POST",
				url: themifyBuilder.ajaxurl,
				data:
				{
					action : 'row_lightbox_options',
					nonce : themifyBuilder.tfb_load_nonce
				},
				success: function( data ){
					$("#themify_builder_lightbox_parent")
					.show()
					.css('top', self.getDocHeight())
					.animate({
						top: 100
					}, 800 );

					self.showLoader('spinhide');
					
					$('.themify_builder_lightbox_title').text(themifyBuilder.textRowStyling);
					$('#themify_builder_lightbox_container').html(data);

					if ( 'object' === typeof $options ) {
						$.each($options, function(id, val){
							$('#tfb_row_settings').find('#' + id).val(val);
						 });
						
						$('#tfb_row_settings').find('.tfb_lb_option[type=radio]').each(function(){
							var id = $(this).prop('name');
							if ('undefined' !== typeof $options[id]) {
								if ( $(this).val() === $options[id] ) {
									$(this).prop('checked', true);
								}
							}
						});
					}

					// image field
					$('#tfb_row_settings').find('.themify-builder-uploader-input').each(function(){
						var img_field = $(this).val(),
							img_thumb = $('<img/>', {src: img_field, width: 50, height: 50});

						if( img_field != '' ){
							$(this).parent().find('.img-placeholder').empty().html(img_thumb);
						}
						else{
							$(this).parent().find('.thumb_preview').hide();
						}
					});

					// colorpicker
					self.setColorPicker();

					// @backward-compatibility
					if( jQuery('#background_video').val() !== '' && $( '#background_type input:checked' ).length == 0 ) {
						$('#background_type_video').trigger( 'click' );
					} else if( $( '#background_type input:checked' ).length == 0 ) {
						$('#background_type_image').trigger( 'click' );
					}

					$( '.tf-option-checkbox-enable input:checked' ).trigger( 'click' );

					// plupload init
					self.builderPlupload('normal');

					$('#themify_builder_lightbox_parent').show();

					$('#themify_builder_lightbox_parent').find('select').wrap('<div class="selectwrapper"></div>');
					$('.selectwrapper').click(function(){
						$(this).toggleClass('clicked');
					});

					/* checkbox field type */
					$( '.themify-checkbox' ).each(function(){
						var id = $( this ).attr( 'id' );
						if( $options[id] ) {
							$options[id] = typeof $options[id] == 'string' ? [$options[id]] : $options[id]; // cast the option value as array
							$.each( $options[id], function( i, v ){
								$( '.tf-checkbox[value="'+ v +'"]' ).prop( 'checked', true );
							} );
						}
					});
				}
			});
		},

		rowSaving: function(e) {
			e.preventDefault();
			var self = ThemifyPageBuilder,
				$active_row_settings = $('.current_selected_row .row-data-styling'),
				temp_appended_data = $('#tfb_row_settings .tfb_lb_option').serializeObject();
			
			$active_row_settings.data('styling', temp_appended_data );

			// Save data
			self.saveData(true, function(){
				$('#themify_builder_lightbox_parent').hide();
				$('.close_lightbox').trigger('click');	
			}, 'cache');

			self.editing = true;
		},

		resetModuleStyling: function(e){
			e.preventDefault();
			var dataReset = $(this).data('reset'),
				$context = dataReset == 'module' ? $('#themify_builder_options_styling') : $('#tfb_row_settings');
			
			$('.tfb_lb_option:not(.exclude-from-reset-field)', $context).each(function(){
				var $this = $(this);
				$this.val('').prop('checked', false).prop('selected', false);
				if( $this.hasClass('themify-builder-uploader-input') ) {
					$this.parent().find('.img-placeholder').html('').parent().hide();
				} else if ( $this.hasClass('font-family-select') ) {
					$this.val('default');
				} else if( $this.hasClass('builderColorSelectInput') ) {
					$this.parent().find('.colordisplay').val('').trigger('blur');
				}
			});
		},

		_gridMenuClicked: function( event ) {
			event.preventDefault();
			var set = $(this).data('grid'),
				handle = $(this).data('handle'), $base, is_sub_row = false;

			$(this).closest('.themify_builder_grid_list').find('.selected').removeClass('selected');
			$(this).closest('li').addClass('selected');

			switch( handle ) {
				case 'module':
					var sub_row_tmpl = wp.template( 'builder_sub_row' ),
						tmpl_sub_row = sub_row_tmpl( {placeholder: themifyBuilder.dropPlaceHolder, newclass: 'col-full' } ),
						$mod_clone = $(this).closest('.active_module').clone();
					$mod_clone.find('.grid_menu').remove();
					
					$base = $(tmpl_sub_row).find('.themify_module_holder').append($mod_clone).end()
					.insertAfter( $(this).closest('.active_module')).find('.themify_builder_sub_row_content');

					$(this).closest('.active_module').remove();
				break;

				case 'sub_row':
					is_sub_row = true;
					$base = $(this).closest('.themify_builder_sub_row').find('.themify_builder_sub_row_content');
				break;

				default:
					$base = $(this).closest('.themify_builder_row').find('.themify_builder_row_content');
			}

			// Hide the dropdown
			$(this).closest('.themify_builder_grid_list_wrapper').hide();

			$.each(set, function(i, v){
				if ( $base.children('.themify_builder_col').eq(i).length > 0 ) {
					$base.children('.themify_builder_col').eq(i).removeClass(ThemifyPageBuilder.clearClass).addClass( 'col' + v );
				} else {
					// Add column
					ThemifyPageBuilder._addNewColumn( { placeholder: themifyBuilder.dropPlaceHolder, newclass: 'col' + v }, $base);
				}
			});

			// remove unused column
			if ( set.length < $base.children().length ) {
				$base.children('.themify_builder_col').eq( set.length - 1 ).nextAll().each( function(){
					// relocate active_module
					var modules = $(this).find('.themify_module_holder').first().clone();
					modules.find('.empty_holder_text').remove();
					modules.children().appendTo($(this).prev().find('.themify_module_holder').first());
					$(this).remove(); // finally remove it
				});
			}

			$base.children().removeClass('first last');
			$base.children().first().addClass('first');
			$base.children().last().addClass('last');

			// remove sub_row when fullwidth column
			if ( is_sub_row && set[0] == '-full' ) {
				var $move_modules = $base.find('.active_module').clone();
				$move_modules.insertAfter( $(this).closest('.themify_builder_sub_row') );
				$(this).closest('.themify_builder_sub_row').remove();
			}

			ThemifyPageBuilder.equalHeight();
			ThemifyPageBuilder.moduleEvents();
		},

		_addNewColumn: function( params, $context ) {
			var template_func = wp.template( 'builder_column'),
				template = template_func( params );
			$context.append($(template));
		},

		_gridHover: function(event) {
			event.stopPropagation();
			if ( event.type == 'touchend' ) {
				$column_menu = $(this).find('.themify_builder_grid_list_wrapper');
				if ( $column_menu.is(':hidden') ) {
					$column_menu.show();
				} else {
					$column_menu.hide();
				}
			} else if(event.type=='mouseenter') {
				$(this).find('.themify_builder_grid_list_wrapper').stop(true,true).show();
			} else if(event.type=='mouseleave' && ( event.toElement || event.relatedTarget ) ) {
				$(this).find('.themify_builder_grid_list_wrapper').stop(true,true).hide();
			}
		},

		_gutterChange: function( event ) {
			var handle = $(this).data('handle');
			
			switch( handle ) {
				case 'sub_row':
					$(this).closest('.themify_builder_sub_row').data('gutter', this.value);
				break;

				case 'row':
					$(this).closest('.themify_builder_row').data('gutter', this.value);
				break;
			}

			// Hide the dropdown
			$(this).closest('.themify_builder_grid_list_wrapper').hide();
		},

		_selectedGridMenu: function() {
			$('.grid_menu').each(function(){
				var handle = $(this).data('handle'),
					grid_base = [], $base;
				if ( handle == 'module' ) return;
				switch( handle ) {
					case 'sub_row':
						$base = $(this).closest('.themify_builder_sub_row').find('.themify_builder_sub_row_content');
					break;

					default:
						$base = $(this).closest('.themify_builder_row').find('.themify_builder_row_content');
				}

				$base.children().each(function(){
					grid_base.push( ThemifyPageBuilder._getColClass( $(this).prop('class').split(' ') ) );
				});

				$(this).find('.grid-layout-' + grid_base.join('-')).closest('li').addClass('selected');

			});
		},

		_getColClass: function(classes) {
			var matches = ThemifyPageBuilder.clearClass.split(' '),
				spanClass = null;
			
			for(var i = 0; i < classes.length; i++) {
				if($.inArray(classes[i], matches) > -1){
					spanClass = classes[i].replace('col', '');
				}
			}
			return spanClass;
		},

		_subRowDelete: function( event ) {
			event.preventDefault();
			if (confirm(themifyBuilder.subRowDeleteConfirm)) {
				$(this).closest('.themify_builder_sub_row').remove();
				ThemifyPageBuilder.newRowAvailable();
				ThemifyPageBuilder.equalHeight();
				ThemifyPageBuilder.moduleEvents();
				ThemifyPageBuilder.editing = true;
			}
		},

		_subRowDuplicate: function( event ) {
			event.preventDefault();
			$(this).closest('.themify_builder_sub_row').clone().insertAfter($(this).closest('.themify_builder_sub_row'));
			ThemifyPageBuilder.equalHeight();
			ThemifyPageBuilder.moduleEvents();
			ThemifyPageBuilder.editing = true;
		}
	};

	// Initialize Builder
	$(function(){
		ThemifyPageBuilder.init();
	});
}(jQuery, window, document));