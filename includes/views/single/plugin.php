<?php

/**
 * @var AspireExplorer\Model\PluginInfo plugin_info.
 */
$plugin_info = $args['plugin_info'] ?? [];

$banner_url = $plugin_info->get_banners( 'high' );
if ( empty( $banner_url ) ) {
	$banner_url = AE_DIR_URL . 'assets/images/default-banner.png';
}

$sections    = $plugin_info->get_sections();
$description = '';
if ( isset( $sections['description'] ) ) {
	$description = $sections['description'];
} elseif ( isset( $sections['changelog'] ) ) {
	$description = $sections['changelog'];
} elseif ( isset( $sections['installation'] ) ) {
	$description = $sections['installation'];
} elseif ( isset( $sections['faq'] ) ) {
	$description = $sections['faq'];
} else {
	$description = $plugin_info->get_description();
}
?>
<main class="single-plugin-card">
	<banner class="entry-banner">
		<img class="plugin-banner" src="<?php echo esc_url( $banner_url ); ?>" alt="Plugin Banner">
	</banner>
	<header class="entry-header">
		<div class="entry-title">
			<h3 class="plugin-title"><a href="<?php echo esc_attr( $plugin_info->get_slug() ); ?>"><?php echo esc_html( $plugin_info->get_name() ); ?></a></h3>
			<p class="plugin-author">by <?php echo esc_html( $plugin_info->get_author() ); ?></p>
			<p class="plugin-version">Version: <?php echo esc_html( $plugin_info->get_version() ); ?></p>
		</div>
		<div class="entry-download">
			<a href="<?php echo esc_url( $plugin_info->get_download_link() ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer">Download</a>
		</div>
	</header>
	<article>
		<?php
		if ( is_array( $sections ) && count( $sections ) > 0 ) {
			foreach ( $sections as $section => $content ) {
				echo '<div class="accordion-item">';
					echo '<input type="radio" name="accordions" id="section-' . esc_attr( $section ) . '" ' . checked( $section, array_key_first( $sections ), false ) . '>';
					echo '<label for="section-' . esc_attr( $section ) . '">' . esc_html( ucfirst( $section ) ) . '</label>';
					echo '<div class="accordion-content">' . wp_kses_post( $content ) . '</div>';
				echo '</div>';
			}
		}
		?>
	</article>
</main>
