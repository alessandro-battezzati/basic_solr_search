<?php

/**
 * The template for displaying search results pages
 *
 * @link 
 *
 * @package WordPress
  
 */

get_header();
?>

<div class="wrap">
    <?php get_sidebar('home'); ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php if (have_posts()) : ?>
                <header class="page-header">
                    <h1 class="page-title">
                        <?php _e('Search results for: '); ?>
                        <span class="page-description"><?php echo get_search_query(); ?></span>
                    </h1>
                    <p> Trovati: <?php
                                    $search_solr_total = get_query_var('search_solr_total');
                                    if ($search_solr_total) {                                        
                                        echo $search_solr_total;                                        
                                    } else {
                                        echo 0;                                        
                                    }
                                    ?> 
                    </p>

                </header><!-- .page-header -->
                <?php
                $search_solr_result = get_query_var('search_solr_result');                
                $page_links = get_query_var('page_links');
                ?>
                <?php 
                if ($search_solr_total) { 
                    foreach ($search_solr_result->docs as $item) : 
                    ?>
                        <div id="post-1" class="post-1 post type-post status-publish format-standard hentry category-senza-categoria entry">
                            <header class="entry-header">
                                <p class="entry-title">
                                    <img width="40" src="<?php echo plugin_dir_url(__FILE__) . '../assets/img/' . $item->type[2] . '.png'; ?>" />
                                    <b>
                                        <?php
                                        if ($item->title) {
                                            $title = $item->title;
                                        } else {
                                            $title = $item->content;
                                        }
                                        ?>
                                        <a href="<?php echo $item->url; ?>"><?php echo solr_search_template_truncate($title, '100'); ?></a>
                                    </b>
                                </p>
                                <p><?php echo solr_search_template_truncate($item->content, '200'); ?></p>
                            </header><!-- .entry-header -->
                        </div>

                    <?php endforeach; 
                }
                ?>
                <p>
                    <?php if ($page_links) {
                        echo '<div>' . $page_links . '</div>';
                    } ?>
                </p>
            <?php
                // Start the Loop.
                while (have_posts()) :
                    the_post();

                    get_template_part('template-parts/content/content', 'excerpt');

                // End the loop.
                endwhile;

            // If no content, include the "No posts found" template.
            else :
                get_template_part('template-parts/content/content', 'none');

            endif;
            ?>
        </main><!-- #main -->
    </div><!-- #primary -->
</div>
<?php
get_footer();
