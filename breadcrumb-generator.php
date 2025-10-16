<?php
// use [iiq_breadcrumbs] shortcode to add breadcrumbs to custom post type templates

if ( ! function_exists( 'iiq_get_primary_meta_cat_id' ) ) {
	function iiq_get_primary_meta_cat_id( $post_id = 0 ){
		$post_id = $post_id ?: get_the_ID();
		if ( ! $post_id ) return 0;

		// Yoast
		if ( class_exists('WPSEO_Primary_Term') ) {
			$yoast = new WPSEO_Primary_Term('category', $post_id);
			$tid = (int) $yoast->get_primary_term();
			if ( $tid && ! is_wp_error($tid) ) return $tid;
		}
		// RankMath
		$rm = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
		if ( $rm ) return $rm;

		return 0;
	}
}

if ( ! function_exists( 'iiq_get_primary_or_single_category' ) ) {
	function iiq_get_primary_or_single_category( $post_id = 0 ){
		$post_id = $post_id ?: get_the_ID();
		if ( ! $post_id ) return null;

		$primary_id = iiq_get_primary_meta_cat_id( $post_id );
		if ( $primary_id ) {
			$term = get_term( $primary_id, 'category' );
			if ( $term && ! is_wp_error( $term ) ) return $term;
		}

		$terms = get_the_terms( $post_id, 'category' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) return null;

		if ( count( $terms ) === 1 ) return $terms[0];
		return $terms[0];
	}
}

add_shortcode( 'iiq_breadcrumbs', function( $atts = [] ) {
	if ( ! is_singular() ) return '';

	$post_id = get_the_ID();
	$post_type = get_post_type( $post_id );
	if ( ! $post_type ) return '';

	$pt_obj = get_post_type_object( $post_type );
	$pt_label = $pt_obj && ! empty( $pt_obj->labels->name ) ? $pt_obj->labels->name : $post_type;

	$cpt_url = '';
	if ( $pt_obj && ! empty( $pt_obj->has_archive ) ) {
		$cpt_url = get_post_type_archive_link( $post_type );
	}

	$cat_term = iiq_get_primary_or_single_category( $post_id );
	$cat_url = $cat_term ? get_term_link( $cat_term ) : '';
	$crumbs = [];
	$crumbs[] = [ 'ResMan', home_url( '/' ) ];
	$crumbs[] = [ $pt_label, ! is_wp_error( $cpt_url ) ? (string) $cpt_url : '' ];
	if ( $cat_term ) {
		$crumbs[] = [ $cat_term->name, ! is_wp_error( $cat_url ) ? (string) $cat_url : '' ];
	}

	ob_start(); ?>
	<nav class="iiq-breadcrumbs" aria-label="Breadcrumb">
		<?php
		$sep = ' <span class="breadcrumb-separator">›</span> ';
		$out = [];
		foreach ( $crumbs as [$label, $url] ) {
			$label_esc = esc_html( $label );
			if ( ! empty( $url ) ) {
				$out[] = '<a class="breadcrumb-link" href="' . esc_url( $url ) . '">' . $label_esc . '</a>';
			} else {
				$out[] = '<span class="breadcrumb-text">' . $label_esc . '</span>';
			}
		}
		echo implode( $sep, $out );
		?>
	</nav>
	<?php
	return trim( ob_get_clean() );
} );
?>