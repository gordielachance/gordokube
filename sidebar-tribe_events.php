<div id="single-event-sidebar" class="sidebar" role="complementary">
        <?php
        //The Events Calendar fucks up a lot of things. Restore original query so we can get the real post ID.
        Tribe__Events__Templates::restoreQuery();
        ?>
        <div id="event_date" class="widget">
            <div class="widget-content">
                <h3 class="widget-title">Date</h3>
                <?php printf('<span class="tribe-event-duration">%s</span>',tribe_events_event_schedule_details()) ?>
            </div>
        </div>
        <div id="event_price" class="widget">
            <div class="widget-content">
                <h3 class="widget-title">Prix</h3>
                <?php echo tribe_get_formatted_cost();?>
            </div>
        </div>
        <?php
        /*
        Venue
        See src/views/modules/meta/venue.php
        */
        if ( $venue_id = tribe_get_venue_id() ) {
            ?>
            <div id="event_venue" class="widget tribe_event_venue" data-tribe-venue-id="<?php echo $venue_id;?>">
                <div class="widget-content">
                    <h3 class="widget-title">
                        <?php esc_html_e( tribe_get_venue_label_singular(), 'the-events-calendar' ) ?>
                        <?php if ( tribe_show_google_map_link() ){ ?>
                            <small class="tribe-venue-gmap"> <?php echo tribe_get_map_link_html(); ?></small>

                        <?php }; ?>
                    </h3>

                    <strong class="tribe-venue-name"> <?php echo tribe_get_venue() ?> </strong>

                    <?php if ( tribe_address_exists() ) : ?>
                        <address class="tribe-venue-address">
                            <?php echo tribe_get_full_address(); ?>
                        </address>
                    <?php endif; ?>

                    <?php if ( $phone = tribe_get_phone() ){ ?>
                        <p class="tribe-venue-tel"><?php echo $phone ?></p>
                    <?php } ?>

                    <?php if ( $website = tribe_get_venue_website_link() ){ ?>
                        <p class="tribe-venue-url"><?php echo $website ?></p>
                    <?php } ?>
                </div>
            </div>
            <?php
        }
        ?>
        <div id="event_links" class="widget">
            <div class="widget-content">
                <?php
                $cal_links = array();
                $cal_links[] = sprintf('<a class="tribe-events-gcal tribe-events-button" href="%s" title="%s">+ %s</a>',Tribe__Events__Main::instance()->esc_gcal_url( tribe_get_gcal_link() ),esc_attr__( 'Add to Google Calendar', 'the-events-calendar' ),esc_html__( 'Google Calendar', 'the-events-calendar' ));
                $cal_links[] = sprintf('<a class="tribe-events-ical tribe-events-button" href="%s" title="%s" >+ %s</a>',esc_url( tribe_get_single_ical_link() ),esc_attr__( 'Download .ics file', 'the-events-calendar' ),esc_html__( 'iCal Export', 'the-events-calendar' ));
                printf('<div class="tribe-events-cal-links">%s</div>',implode("\n",$cal_links));
                ?>
            </div>
            <?php

            if ( function_exists( 'sharing_display' ) ) {
                sharing_display( '', true );
            }

            if ( class_exists( 'Jetpack_Likes' ) ) {
                $custom_likes = new Jetpack_Likes;
                echo $custom_likes->post_likes( '' );
            }
            ?>
        </div>
</div>
