<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
	<div class="themify_builder_content-<?php echo esc_attr( $builder_id ); ?> themify_builder<?php if ( $this->in_the_loop ) echo ' in_the_loop'; ?>">
	<?php foreach ( $builder_output as $rows => $row ): ?>
	<!-- module_row -->
	<?php
	$row['row_order'] = isset( $row['row_order'] ) ? $row['row_order'] : '1';
	$row_classes = array( 'module_row', 'module_row_' . $row['row_order'], 'clearfix' );
	$class_fields = array( 'custom_css_row', 'background_repeat', 'animation_effect', 'row_width', 'row_height' );
	foreach( $class_fields as $field ) {
		if ( isset( $row['styling'][ $field ] ) && ! empty( $row['styling'][ $field ] ) ) array_push( $row_classes, $row['styling'][ $field ] );
	}
	?>
		<div class="<?php echo implode(' ', $row_classes ); ?>">
			<div class="row_inner_wrapper">

				<div class="row_inner">

					<?php do_action('themify_builder_row_start', $builder_id, $row ); ?>

					<?php if ( isset( $row['cols'] ) && count( $row['cols'] ) > 0 ):
						
						$count = count( $row['cols'] );

						switch ( $count ) {
							
							case 4:
								$order_classes = array( 'first', 'second', 'third', 'last' );
							break;

							case 3:
								$order_classes = array( 'first', 'middle', 'last' );
							break;

							case 2:
								$order_classes = array( 'first', 'last' );
							break;

							default:
								$order_classes = array( 'first' );
							break;
						}

						foreach ( $row['cols'] as $cols => $col ):
							$columns_class = array();
							$grid_class = explode(' ', $col['grid_class'] );
							$dynamic_class[0] = $order_classes[ $cols ];
							$columns_class = array_merge( $columns_class, $grid_class );
							foreach( $dynamic_class as $class ) {
								array_push( $columns_class, $class );
							}
							$columns_class = array_unique( $columns_class );
							// remove class "last" if the column is fullwidth
							if ( 1 == $count ) {
								if ( ( $key = array_search( 'last', $columns_class ) ) !== false) {
									unset( $columns_class[ $key ] );
								}
							}
							$print_column_classes = implode( ' ', $columns_class );
							?>

					<div class="<?php echo esc_attr( $print_column_classes ); ?>">
						<?php
							if ( isset( $col['modules'] ) && count( $col['modules'] ) > 0 ) { 
								foreach ( $col['modules'] as $modules => $mod ) { 
									
									// First child modules
									if ( isset( $mod['mod_name'] ) ) { 
										$identifier = array( $rows, $cols, $modules ); // define module id
										$this->get_template_module( $mod, $builder_id, true, false, '', $identifier );
									}

									// Print any Sub Rows
									if ( isset( $mod['cols'] ) && count( $mod['cols'] ) > 0 ) {
										$sub_row_gutter = isset( $mod['gutter'] ) && ! empty( $mod['gutter'] ) ? $mod['gutter'] : 'gutter-default';
										$sub_row_class = 'sub_row_' . $rows . '-' . $cols . '-' . $modules; 
										$sub_row_attr = '';
										echo sprintf('<div class="themify_builder_sub_row clearfix %s %s"%s>', $sub_row_gutter, $sub_row_class, $sub_row_attr );
										?>

										<?php
										foreach( $mod['cols'] as $col_key => $sub_col ) {
											$sub_col_class = $sub_col['grid_class'];
											echo sprintf( '<div class="%s">', $sub_col_class );

											if ( isset( $sub_col['modules'] ) && count( $sub_col['modules'] ) > 0 ) {
												foreach( $sub_col['modules'] as $sub_module_k => $sub_module ) {
													$sw_wrap = false;
													$sw_class = '';
													$sub_identifier = array( $sub_row_class, $col_key, $sub_module_k ); // define module id
													$this->get_template_module( $sub_module, $builder_id, true, $sw_wrap, $sw_class, $sub_identifier );
												}
											}
											echo '</div>';
										}

										echo '</div>';
									}
									
								}
							}
						?>
					</div>
					<!-- /col -->
					<?php endforeach; endif; // end col loop ?>
					
					<?php do_action('themify_builder_row_end', $builder_id, $row ); ?>

				</div>
				<!-- /row_inner -->
			</div>
		</div>
		<!-- /module_row -->

	<?php endforeach; // end row loop ?>
	</div>