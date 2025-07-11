<?php
$target_page_slug = $args['target_page_slug'] ?? '';
$search_keyword   = $args['search_keyword'] ?? '';
?>
<form method="get" action="<?php echo esc_url( get_bloginfo( 'url' ) . '/' . $target_page_slug ); ?>" class="theme-search-form" role="search" aria-label="<?php esc_attr_e( 'Theme search form', 'aspireexplorer' ); ?>">
	<div class="theme-search-main">
		<label for="theme-search-keyword" class="screen-reader-text"><?php esc_html_e( 'Search WordPress Themes', 'aspireexplorer' ); ?></label>
		<input type="text" id="theme-search-keyword" name="keyword" placeholder="<?php esc_attr_e( 'Search WordPress Themes', 'aspireexplorer' ); ?>" value="<?php echo esc_attr( $search_keyword ); ?>" aria-label="<?php esc_attr_e( 'Search WordPress Themes', 'aspireexplorer' ); ?>" />
		<button type="submit" class="search-btn" aria-label="<?php esc_attr_e( 'Submit theme search', 'aspireexplorer' ); ?>">
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Search', 'aspireexplorer' ); ?></span>
		</button>
		<button type="button" class="filters-btn" aria-label="<?php esc_attr_e( 'Show Filters', 'aspireexplorer' ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'Filters', 'aspireexplorer' ); ?></span>
		</button>
	</div>
	<div class="theme-search-filters">
		<?php
		$theme_feature_list = get_theme_feature_list();

		if ( is_array( $theme_feature_list ) && ! empty( $theme_feature_list ) ) {
			foreach ( $theme_feature_list as $category_key => $features ) {
				$fieldset_id = 'theme-filter-group-' . sanitize_title( $category_key );
				echo '<fieldset id="' . esc_attr( $fieldset_id ) . '" aria-labelledby="' . esc_attr( $fieldset_id ) . '-legend">';
				echo '<legend id="' . esc_attr( $fieldset_id ) . '-legend">' . esc_html( $category_key ) . '</legend>';
				if ( is_array( $features ) && ! empty( $features ) ) {
					echo '<ul role="group" aria-labelledby="' . esc_attr( $fieldset_id ) . '-legend">';
					foreach ( $features as $feature_key => $feature_label ) {
						$checkbox_id = 'theme-filter-' . sanitize_title( $feature_key );
						$formatted_label = ucwords( str_replace( [ '_', '-' ], ' ', $feature_label ) );
						echo '<li>';
							echo '<input type="checkbox" id="' . esc_attr( $checkbox_id ) . '" name="tag[]" value="' . esc_attr( $feature_key ) . '" aria-describedby="' . esc_attr( $checkbox_id ) . '-desc" />';
							echo '<label for="' . esc_attr( $checkbox_id ) . '">' . esc_html( $formatted_label ) . '</label>';
							/* translators: %s: Feature name for accessibility description */
							echo '<span id="' . esc_attr( $checkbox_id ) . '-desc" class="screen-reader-text">' . sprintf( esc_html__( 'Filter themes by %s feature', 'aspireexplorer' ), esc_html( $formatted_label ) ) . '</span>';
						echo '</li>';
					}
					echo '</ul>';
				}
				echo '</fieldset>';
			}
		}
		?>
	</div>
</form>
