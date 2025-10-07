<?php
$target_page_slug = $args['target_page_slug'] ?? '';
$themes_result    = $args['themes_result'] ?? [];
$current_page     = $args['current_page'] ?? 1;
$total_results    = $args['total_results'] ?? 0;
$total_pages      = $args['total_pages'] ?? 1;
?>
<div class="archive-theme-card">
	<div class="theme-results-count">
		<p id="theme-results-count-text" aria-hidden="true">
			<?php
			printf(
				/* translators: %s: number of themes found */
				esc_html( _n( '%s Theme Found.', '%s Themes Found.', $total_results, 'aspireexplorer' ) ),
				esc_html( $total_results )
			);
			?>
		</p>
		<span class="screen-reader-text" aria-live="polite" aria-atomic="true" id="theme-results-count-sr">
			<?php
			printf(
				esc_html(
					/* translators: %s: number of themes in the results list */
					_n(
						'%s theme found in the results list below.',
						'%s themes found in the results list below.',
						$total_results,
						'aspireexplorer'
					)
				),
				esc_html( $total_results )
			);
			?>
		</span>
	</div>
	<ul class="theme-results" role="list">
		<?php
		foreach ( $themes_result as $theme_result ) {
			$theme_info = new \AspireExplorer\Model\ThemeInfo( $theme_result );
			\AspireExplorer\Controller\Utilities::include_file(
				'themes' . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR . 'theme.php',
				[
					'target_page_slug' => $target_page_slug,
					'theme_info'       => $theme_info,
				]
			);
		}
		?>
	</ul>
	<div class="pagination-wrapper">
		<?php
		if ( 1 < $total_pages ) {
			$big = 999999999;
			echo wp_kses_post(
				paginate_links(
					[
						'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $current_page,
						'total'     => $total_pages,
						'prev_next' => false,
						'type'      => 'list',
					]
				)
			);
		}
		?>
	</div>
</div>
