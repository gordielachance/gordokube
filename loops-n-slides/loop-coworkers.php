<?php
/**
 * Loops 'n Slides Loop: Co-workers
 *
 * Display a post title and excerpt
 */
global $loopsns_loop;
?>

<?php if ( have_posts() ){ ?>
    <?php 
    while( have_posts() ){
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            
            <h3 class="loopsns-slide-title">
                <a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
            </h3>
            <div class="gordokube-trombinou"></div>
            <?php the_excerpt(); ?>
        </article>

   <?php 
    }

}
?>