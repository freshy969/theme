<?php
/**
 * Custom template tags for AffiliateWP
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since AffiliateWP 1.0
 */

/**
 * Limit excerpt length to 20 characters
 */
function affwp_excerpt_length( $length ) {
	return 20;
}
add_filter( 'excerpt_length', 'affwp_excerpt_length' );

/**
 * Remove purchase links from add-ons pages
 */
remove_action( 'edd_after_download_content', 'edd_append_purchase_link' );

/**
 * Filter comment form
 */
function affwp_comment_form_defaults( $defaults ) {
	$defaults['title_reply'] 	= '';
	$defaults['label_submit']	= 'Go for it';

	return $defaults;
}
add_filter( 'comment_form_defaults', 'affwp_comment_form_defaults', 1 );

/**
 * Share purchase shortcode
 */
function affwp_show_sharing_buttons_after_purchase( $atts, $content = null ) {
	
	$success_page = edd_get_option( 'success_page' ) ? is_page( edd_get_option( 'success_page' ) ) : false;

	// return if no affiliate was rewarded via the select menu
	if ( ! $success_page )
		return;

	$content = affwp_share_box( '', 'I just purchased AffiliateWP, the best affiliate marketing plugin for WordPress!' );

	return $content;
}
add_shortcode( 'affwp_share_purchase', 'affwp_show_sharing_buttons_after_purchase' );

/**
 * Add Twitter Card meta tags
 */ 
function affwp_twitter_card_meta() {
	?>
	<meta name="twitter:card" content="product">
	<meta name="twitter:site" content="@affwp">
	<meta name="twitter:creator" content="">
	<meta name="twitter:title" content="AffiliateWP - The best affiliate marketing plugin for WordPress">
	<meta name="twitter:description" content="An efficient and reliable affiliate marketing plugin for WordPress. Integrates directly with all major WordPress e-Commerce and Membership plugins.">
	<meta name="twitter:image:src" content="http://affiliatewp.com/wp-content/uploads/2014/04/twitter-card.png">
	<meta name="twitter:data1" content="$49 USD">
	<meta name="twitter:label1" content="PRICE">
	<meta name="twitter:data2" content="Personal (1 site)">
	<meta name="twitter:label2" content="LICENSE">
	<meta name="twitter:domain" content="affiliatewp.com">
	<meta name="twitter:app:name:iphone" content="">
	<meta name="twitter:app:name:ipad" content="">
	<meta name="twitter:app:name:googleplay" content="">
	<meta name="twitter:app:url:iphone" content="">
	<meta name="twitter:app:url:ipad" content="">
	<meta name="twitter:app:url:googleplay" content="">
	<meta name="twitter:app:id:iphone" content="">
	<meta name="twitter:app:id:ipad" content="">
	<meta name="twitter:app:id:googleplay" content="">
<?php }
add_action( 'wp_head', 'affwp_twitter_card_meta' );

/**
 * Redirect if addon is accessed while coming soon
 * Admins can see addon just fine
 * @since 1.1.9
 */
function affwp_redirect_coming_soon() {
	global $post;

	if ( ! is_singular( 'download' ) || current_user_can( 'manage_options' ) )
		return;

	$do_redirect = affwp_addon_is_coming_soon( get_the_ID() );

	if ( $do_redirect ) {
		$redirect_url = site_url( 'addons' );

		if ( isset( $_REQUEST['HTTP_REFERER'] ) ) {
			$referer = esc_url( $_REQUEST['HTTP_REFERER '] );

			if ( strpos( $referer, $redirect_url ) !== false ) {
				$redirect_url = $referer;
			}
		}

		wp_redirect( $redirect_url, 301 ); exit;
	}
	
}
add_action( 'template_redirect', 'affwp_redirect_coming_soon' );




/**
 * Prevent adons from being added to cart (free or priced) with ?edd_action=add_to_cart&download_id=XXX
 *
 * @param int	$download_id Download Post ID
 *
 * @since 1.0.3
 */
function affwp_pre_add_to_cart( $download_id ) {
	// let customers buy affiliateWP
	if ( $download_id === affwp_get_affiliatewp_id() )
		return; 

	wp_die( __( 'This add-on cannot be purchased', 'affwp' ), '', array( 'back_link' => true ) );
}
add_action( 'edd_pre_add_to_cart', 'affwp_pre_add_to_cart' );

/**
 * Output custom icons - favicon & apple touch icon
 * @link https://github.com/audreyr/favicon-cheat-sheet
 */
function affwp_favicons() {
?>
	<link rel="apple-touch-icon-precomposed" href="<?php echo get_stylesheet_directory_uri() . '/images/favicon-152.png'; ?>">
	<?php 
}
add_action( 'wp_head', 'affwp_favicons' );

/**
 * Make doc category pags show all posts
 */
function affwp_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() )
		return;
 	
 	// Make doc category pags show all posts
	if ( $query->is_tax( 'doc_category' ) ) {

		$query->set( 'posts_per_page', -1 );
			return;
	}

	// add-ons page
	if ( $query->is_post_type_archive( 'download' ) ) {

		// show all add-ons
		$query->set( 'posts_per_page', -1 );
		
		// remove affiliateWP from showing
		$query->set( 'post__not_in', array( affwp_get_affiliatewp_id() ) );

		// remove free addons assigned to the "free" category
		$term = get_term_by( 'slug', 'free', 'download_category' );
		
		$args = array(
	       array(
	           'taxonomy' => 'download_category',
	           'field' => 'id',
	           'terms' => array( $term->term_id ),
	           'operator'=> 'NOT IN'
	       )
	    );

		$query->set( 'tax_query', $args );

		return;
	}
}
add_action( 'pre_get_posts', 'affwp_pre_get_posts' );

/**
 * Get started now button
 * @param  string $text [description]
 * @return [type]       [description]
 */
function affwp_button( $text = '', $url = '', $size = 'large' ) {
	if ( ! ( $text && $url ) )
		return;
?>
	<a class="button <?php echo $size; ?>" href="<?php echo $url; ?>"><?php echo $text; ?></a>
<?php }

/**		
 * Render the_title
 * @since 1.0
 * Add filter similar to subheader to make modify title easier
*/
function affwp_the_title( $header = '' ) {
	$header = $header ? $header : get_the_title();
	echo apply_filters( 'affwp_the_title', '<h1>' . $header . '</h1>'  );
}	

/**
 * Filter the title for specific pages
 */
function affwp_the_title_filters( $title ) {
	global $wp_query;

	// search query
	if ( get_search_query() ) {
		if ( in_the_loop() ) {
			$title = the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		}
		else {
			$title = __( '<h1>Search Results</h1>', 'affwp' );
		}
	}

	// download category pages
	if ( is_tax( 'download_category' ) ) {
		if ( in_the_loop() ) {
			$title = the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		}
		else {
			$title = sprintf( __( '<h1>%s</h1>', 'affwp' ), single_cat_title( '', false ) );
		}

	}

	// normal category pages
	if ( is_category() ) {
		if ( in_the_loop() ) {
			$title = the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		}
		else {
			$title = sprintf( __( '<h1>%s</h1>', 'affwp' ), single_cat_title( '', false ) );
		}
	}

	return $title;
	
}
add_filter( 'affwp_the_title', 'affwp_the_title_filters' );



/**
 * Show shortcode without shortcode activating
 */
function affwp_show_shortcode( $atts, $content = '' ) {
	return $content;
}
add_shortcode( 'show_shortcode', 'affwp_show_shortcode' );

/**
 * Page header
 */
function affwp_page_header( $header = '', $sub_header = '' ) {
	global $post;
	?>
	<header class="entry-header">
		<?php affwp_the_title( $header ); ?>

		<?php
			
			if ( ! $sub_header ) {
			//	$excerpt = isset( $post->post_excerpt ) ? $post->post_excerpt : '';
				$sub_header = function_exists( 'get_the_subheading' ) && get_the_subheading() ? '<h2>' . get_the_subheading() . '</h2>' : '';
			}
			
			echo apply_filters( 'affwp_excerpt', $sub_header );
		?>

		<?php do_action( 'affwp_page_header_end' ); ?>

	</header>
<?php }

/**
 * Filter the subheadings
 */
function affwp_modify_excerpts( $sub_header ) {
	global $wp_query;

	// search query
	if ( get_search_query() ) {
		$sub_header = sprintf( __( '<h2>Your searched for <strong>%s</strong></h2>', 'affwp' ), get_search_query() );
	}

	// normal category pages
	if ( is_category() ) {
		$term = $wp_query->queried_object;
		$sub_header = $term->description ? sprintf( '<h2>%s</h2>', $term->description ) : '';
	}

	if ( is_tax( 'download_category' ) ) {
		$term = $wp_query->queried_object;

		if ( $term->description ) {
			$sub_header = sprintf( '<h2>%s</h2>', $term->description );
		}
	}

	return $sub_header;
}
add_filter( 'affwp_excerpt', 'affwp_modify_excerpts' );







/**
 * Add twitter custom timeline to testimonials page
 */
function affwp_testimonials_twitter_feed() {
	if ( ! is_page_template( 'page-templates/testimonials.php' ) )
		return;
	?>

	<a class="twitter-timeline" href="https://twitter.com/affwp/timelines/458773013576417280" data-widget-id="458774486909595648" data-border-color="#F7F7F7">Word on the street</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

	<?php
}
add_action( 'affwp_page_header_end', 'affwp_testimonials_twitter_feed' );



if ( ! function_exists( 'affwp_paging_nav' ) ) :
/**
 * Display navigation to next/previous set of posts when applicable.
 *
 * @since AffiliateWP 1.0
 *
 * @return void
 */
function affwp_paging_nav() {
	// Don't print empty markup if there's only one page.
	if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
		return;
	}

	$paged        = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;
	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$query_args   = array();
	$url_parts    = explode( '?', $pagenum_link );

	if ( isset( $url_parts[1] ) ) {
		wp_parse_str( $url_parts[1], $query_args );
	}

	$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
	$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

	$format  = $GLOBALS['wp_rewrite']->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format .= $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit( 'page/%#%', 'paged' ) : '?paged=%#%';

	// Set up paginated links.
	$links = paginate_links( array(
		'base'     => $pagenum_link,
		'format'   => $format,
		'total'    => $GLOBALS['wp_query']->max_num_pages,
		'current'  => $paged,
		'mid_size' => 1,
		'add_args' => array_map( 'urlencode', $query_args ),
		'prev_text' => __( '&larr; Previous', 'affwp' ),
		'next_text' => __( 'Next &rarr;', 'affwp' ),
	) );

	if ( $links ) :

	?>
	<nav class="navigation paging-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Posts navigation', 'affwp' ); ?></h1>
		<div class="pagination loop-pagination">
			<?php echo $links; ?>
		</div><!-- .pagination -->
	</nav><!-- .navigation -->
	<?php
	endif;
}
endif;

if ( ! function_exists( 'affwp_post_nav' ) ) :
/**
 * Display navigation to next/previous post when applicable.
 *
 * @since AffiliateWP 1.0
 *
 * @return void
 */
function affwp_post_nav() {
	// Don't print empty markup if there's nowhere to navigate.
	$previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
	$next     = get_adjacent_post( false, '', false );

	if ( ! $next && ! $previous ) {
		return;
	}

	?>
	<nav class="navigation post-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Post navigation', 'affwp' ); ?></h1>
		<div class="nav-links columns columns-2">
			<?php
			if ( is_attachment() ) :
				previous_post_link( '%link', __( '<span class="meta-nav item">Published In</span>%title', 'affwp' ) );
			else :
				previous_post_link('<div class="item">%link</div>');
				next_post_link('<div class="item">%link</div>');
				
				// previous_post_link( '%link', __( '<span class="meta-nav item">%title</span>', 'affwp' ) );
				// next_post_link( '%link', __( '<span class="meta-nav item">%title</span>', 'affwp' ) );
			endif;
			?>
		</div><!-- .nav-links -->
	</nav><!-- .navigation -->
	<?php
}
endif;

/**
 * Display navigation to next/previous post when applicable.
 *
 * @since 1.1.1
 *
 * @return void
 */
function affwp_single_post_nav() {
	?>

	<nav class="nav-links columns columns-2">

	<?php
		$prev_post = get_adjacent_post( false, '', true );

		if ( ! empty( $prev_post ) ) {
			echo '<div class="nav-previous item"><a href="' . get_permalink( $prev_post->ID ) . '" title="' . $prev_post->post_title . '"><i class="icon-arrow-left"></i><span>' . $prev_post->post_title . '</span></a></div>';
		}

		$next_post = get_adjacent_post( false, '', false );

		if ( ! empty( $next_post ) ) {
			echo ' <div class="nav-next item"><a href="' . get_permalink( $next_post->ID ) . '" title="' . $next_post->post_title . '"><span>' . $next_post->post_title . '</span><i class="icon-arrow-right"></i></a></div>';
		}
	?>
	</nav>
	<?php
}


if ( ! function_exists( 'affwp_posted_on' ) ) :
/**
 * Print HTML with meta information for the current post-date/time and author.
 *
 * @since AffiliateWP 1.0
 *
 * @return void
 */
function affwp_posted_on() {
	if ( is_sticky() && is_home() && ! is_paged() ) {
		echo '<span class="featured-post">' . __( 'Sticky', 'affwp' ) . '</span>';
	}

	// Set up and print post meta information.
	// printf( '<span class="entry-date"><a href="%1$s" rel="bookmark"><time class="entry-date" datetime="%2$s">%3$s</time></a></span> <span class="byline"><span class="author vcard"><a class="url fn n" href="%4$s" rel="author">%5$s</a></span></span>',
	// 	esc_url( get_permalink() ),
	// 	esc_attr( get_the_date( 'c' ) ),
	// 	esc_html( get_the_date() ),
	// 	esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
	// 	get_the_author()
	// );

	// printf( '<span class="entry-date"><time class="entry-date" datetime="%1$s">%2$s</time></span> <span class="byline"><span class="author vcard"><a class="url fn n" href="%3$s" rel="author">%4$s</a></span></span>',
	// 	esc_attr( get_the_date( 'c' ) ),
	// 	esc_html( get_the_date() ),
	// 	esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
	// 	get_the_author()
	// );

	// printf( '<div class="entry-date"><time class="entry-date" datetime="%1$s">%2$s</time></div>',
	// 	esc_attr( get_the_date( 'c' ) ),
	// 	esc_html( get_the_date() )
	// );

	printf( '<div class="entry-date"><time class="entry-date" datetime="%1$s">%2$s</time></div>',
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);
}
endif;

/**
 * Find out if blog has more than one category.
 *
 * @since AffiliateWP 1.0
 *
 * @return boolean true if blog has more than 1 category
 */
function affwp_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'affwp_category_count' ) ) ) {
		// Create an array of all the categories that are attached to posts
		$all_the_cool_cats = get_categories( array(
			'hide_empty' => 1,
		) );

		// Count the number of categories that are attached to the posts
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'affwp_category_count', $all_the_cool_cats );
	}

	if ( 1 !== (int) $all_the_cool_cats ) {
		// This blog has more than 1 category so affwp_categorized_blog should return true
		return true;
	} else {
		// This blog has only 1 category so affwp_categorized_blog should return false
		return false;
	}
}

/**
 * Flush out the transients used in affwp_categorized_blog.
 *
 * @since AffiliateWP 1.0
 *
 * @return void
 */
function affwp_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'affwp_category_count' );
}
add_action( 'edit_category', 'affwp_category_transient_flusher' );
add_action( 'save_post',     'affwp_category_transient_flusher' );

/**
 * Display an optional post thumbnail.
 *
 * Wraps the post thumbnail in an anchor element on index
 * views, or a div element when on single views.
 *
 * @since AffiliateWP 1.0
 *
 * @return void
*/
function affwp_post_thumbnail() {
	if ( post_password_required() || ! has_post_thumbnail() ) {
		return;
	}

	if ( is_singular() ) :
	?>

	<div class="post-thumbnail">
	<?php
		if ( ( ! is_active_sidebar( 'sidebar-2' ) || is_page_template( 'page-templates/full-width.php' ) ) ) {
			the_post_thumbnail( 'affwp-full-width' );
		} else {
			the_post_thumbnail();
		}
	?>
	</div>


	<?php else : ?>

	<a title="<?php the_title_attribute(); ?>" class="post-thumbnail" href="<?php the_permalink(); ?>">
	<?php
		if ( ( ! is_active_sidebar( 'sidebar-2' ) || is_page_template( 'page-templates/full-width.php' ) ) ) {
			the_post_thumbnail();
		} else {
			the_post_thumbnail();
		}
	?>
	</a>

	<?php endif; // End is_singular()
}


/**
 * Returns the URL to upgrade a license from personal -> business or dev, or from business -> dev
 *
 * @since AffiliateWP 1.x
 *
 * @return string
*/
function affwp_get_license_upgrade_url( $type = '' ) {

	if ( ! function_exists( 'edd_get_checkout_uri' ) || ! $type ) {
		return home_url( '/pricing' );
	}

	$args = array(
		'edd_action' => 'upgrade_affwp_license',
		'type'		 => $type
	);

	return add_query_arg( $args, edd_get_checkout_uri() );
}

/**
 * Returns the URL to download an add on
 *
 * @since AffiliateWP 1.x
 *
 * @return string
*/
function affwp_get_add_on_download_url( $add_on_id = 0 ) {

	$args = array(
		'edd_action' => 'add_on_download',
		'add_on'     => $add_on_id,
	);

	return add_query_arg( $args, home_url() );
}
