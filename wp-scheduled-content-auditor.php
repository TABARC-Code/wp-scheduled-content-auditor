<?php
/**
 * Plugin Name: WP Scheduled Content Auditor
 * Plugin URI: https://github.com/TABARC-Code/wp-scheduled-content-auditor
 * Description: Checks what is scheduled, what is late, and what WordPress quietly forgot to publish.
 * Version: 1.0.0
 * Author: TABARC-Code
 * Author URI: https://github.com/TABARC-Code
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Copyright (c) 2025 TABARC-Code
 * Original work by TABARC-Code.
 * You may modify and redistribute this under the terms of the GPL version 3 or later.
 * Keep this notice. If you break something, you own the mess.
 *
 * Reason this exists:
 * WordPress decided that "Missed schedule" is something the admin can discover accidentally
 * while wondering why the front page is still showing yesterday.
 * I would like a calm screen that lists:
 * - Posts that are scheduled
 * - Posts that are probably stuck
 * - A simple button to publish or bump them
 *
 * TODO: add basic cron health diagnostics instead of pretending WP Cron is fine.
 * TODO: add filters and hooks so I can log missed schedule events elsewhere.
 * FIXME: massive sites will need pagination. I am not pretending this first version is infinite scale.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Scheduled_Content_Auditor {

    private $screen_slug = 'wp-scheduled-content-auditor';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_tools_page' ) );
        add_action( 'admin_post_wp_sca_publish_now', array( $this, 'handle_publish_now' ) );
        add_action( 'admin_post_wp_sca_bump_schedule', array( $this, 'handle_bump_schedule' ) );
        add_action( 'admin_head-plugins.php', array( $this, 'inject_plugin_list_icon_css' ) );
    }

    /**
     * Where my SVG lives. All my plugins follow this pattern.
     */
    private function get_brand_icon_url() {
        return plugin_dir_url( __FILE__ ) . '.branding/tabarc-icon.svg';
    }

    public function add_tools_page() {
        add_management_page(
            __( 'Scheduled Content Auditor', 'wp-scheduled-content-auditor' ),
            __( 'Scheduled Auditor', 'wp-scheduled-content-auditor' ),
            'edit_posts',
            $this->screen_slug,
            array( $this, 'render_tools_page' )
        );
    }

    public function render_tools_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-scheduled-content-auditor' ) );
        }

        $now_gmt  = current_time( 'mysql', true );
        $now_ts   = current_time( 'timestamp', true );
        $buffer   = 5 * MINUTE_IN_SECONDS; // a tiny grace window before I start calling it "late"

        $scheduled_posts = $this->get_scheduled_posts();
        $late_posts      = array();
        $future_posts    = array();

        foreach ( $scheduled_posts as $post ) {
            $post_ts = strtotime( get_gmt_from_date( $post->post_date ) );
            if ( $post_ts + $buffer < $now_ts ) {
                $late_posts[] = $post;
            } else {
                $future_posts[] = $post;
            }
        }

        $cron_info = $this->get_cron_info();

        $notice = '';
        if ( isset( $_GET['wp_sca_result'] ) ) {
            $notice = $this->build_result_notice( sanitize_text_field( wp_unslash( $_GET['wp_sca_result'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Scheduled Content Auditor', 'wp-scheduled-content-auditor' ); ?></h1>
            <p>
                This is the part of WordPress that should have existed from day one.
                Here I can see what is scheduled, what is late, and fix it without digging through the post list manually.
            </p>

            <?php if ( $notice ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html( $notice ); ?></p>
                </div>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Cron summary', 'wp-scheduled-content-auditor' ); ?></h2>
            <?php $this->render_cron_summary( $cron_info ); ?>

            <h2><?php esc_html_e( 'Late or likely missed schedule', 'wp-scheduled-content-auditor' ); ?></h2>
            <p>
                These posts are still marked as scheduled, but their publish time is already in the past.
                Classic "missed schedule" behaviour. I can force publish or bump them.
            </p>
            <?php $this->render_post_table( $late_posts, true ); ?>

            <h2><?php esc_html_e( 'Upcoming scheduled content', 'wp-scheduled-content-auditor' ); ?></h2>
            <p>
                These are future posts that have not fired yet. Mostly here so I can sanity check that
                tomorrow will not be empty because cron decided to take the day off.
            </p>
            <?php $this->render_post_table( $future_posts, false ); ?>
        </div>
        <?php
    }

    /**
     * Small helper to display a human friendly notice after actions.
     */
    private function build_result_notice( $code ) {
        switch ( $code ) {
            case 'published':
                return __( 'Selected post has been published now.', 'wp-scheduled-content-auditor' );
            case 'bumped':
                return __( 'Selected post has had its schedule bumped.', 'wp-scheduled-content-auditor' );
            case 'none':
                return __( 'No posts were changed. Possibly nothing was selected.', 'wp-scheduled-content-auditor' );
            case 'error':
            default:
                return __( 'Something went wrong. Check permissions or logs if this keeps happening.', 'wp-scheduled-content-auditor' );
        }
    }

    /**
     * Fetch all scheduled posts across public post types, capped at a reasonable number.
     *
     * I am not pretending this is perfect for giant installs. This is the first pass.
     */
    private function get_scheduled_posts() {
        $args = array(
            'post_type'      => get_post_types(
                array(
                    'public' => true,
                ),
                'names'
            ),
            'post_status'    => 'future',
            'posts_per_page' => 200,
            'orderby'        => 'date',
            'order'          => 'ASC',
        );

        return get_posts( $args );
    }

    /**
     * Basic cron diagnostics. I am not here to fix WP Cron today,
     * just to see if publish_future_post is even scheduled.
     */
    private function get_cron_info() {
        $cron = _get_cron_array();
        if ( ! is_array( $cron ) ) {
            return array(
                'events'     => array(),
                'next_run'   => null,
                'event_count'=> 0,
            );
        }

        $events     = array();
        $next_run   = null;
        $event_count = 0;

        foreach ( $cron as $timestamp => $hooks ) {
            foreach ( $hooks as $hook => $details ) {
                if ( $hook === 'publish_future_post' ) {
                    $event_count += count( $details );
                    if ( $next_run === null || $timestamp < $next_run ) {
                        $next_run = $timestamp;
                    }
                    $events[] = array(
                        'timestamp' => $timestamp,
                        'details'   => $details,
                    );
                }
            }
        }

        return array(
            'events'      => $events,
            'next_run'    => $next_run,
            'event_count' => $event_count,
        );
    }

    private function render_cron_summary( $info ) {
        if ( empty( $info['event_count'] ) ) {
            echo '<p>' . esc_html__( 'No publish_future_post events found. Either nothing is scheduled or cron is asleep.', 'wp-scheduled-content-auditor' ) . '</p>';
            return;
        }

        echo '<p>';
        printf(
            /* translators: %d is the number of scheduled events. */
            esc_html__( 'There are %d scheduled publish events in the cron queue.', 'wp-scheduled-content-auditor' ),
            (int) $info['event_count']
        );
        echo '</p>';

        if ( ! empty( $info['next_run'] ) ) {
            echo '<p>';
            echo esc_html__( 'Next publish_future_post is due at:', 'wp-scheduled-content-auditor' ) . ' ';
            echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $info['next_run'] ) );
            echo '</p>';
        }
    }

    /**
     * Render a table of posts, with optional action buttons.
     */
    private function render_post_table( $posts, $include_actions ) {
        if ( empty( $posts ) ) {
            echo '<p>' . esc_html__( 'Nothing here. Which is either good news or suspiciously quiet.', 'wp-scheduled-content-auditor' ) . '</p>';
            return;
        }

        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'wp-scheduled-content-auditor' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'wp-scheduled-content-auditor' ); ?></th>
                    <th><?php esc_html_e( 'Author', 'wp-scheduled-content-auditor' ); ?></th>
                    <th><?php esc_html_e( 'Scheduled for', 'wp-scheduled-content-auditor' ); ?></th>
                    <th><?php esc_html_e( 'Age', 'wp-scheduled-content-auditor' ); ?></th>
                    <?php if ( $include_actions ) : ?>
                        <th><?php esc_html_e( 'Actions', 'wp-scheduled-content-auditor' ); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $now_ts = current_time( 'timestamp' );
            foreach ( $posts as $post ) :
                $edit_link = get_edit_post_link( $post->ID );
                $author    = get_userdata( $post->post_author );
                $scheduled_ts = strtotime( $post->post_date );
                $diff      = $now_ts - $scheduled_ts;
                ?>
                <tr>
                    <td>
                        <?php if ( $edit_link ) : ?>
                            <a href="<?php echo esc_url( $edit_link ); ?>">
                                <?php echo esc_html( get_the_title( $post ) ); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html( get_the_title( $post ) ); ?>
                        <?php endif; ?>
                    </td>
                    <td><code><?php echo esc_html( get_post_type( $post ) ); ?></code></td>
                    <td><?php echo $author ? esc_html( $author->display_name ) : esc_html__( 'Unknown', 'wp-scheduled-content-auditor' ); ?></td>
                    <td><?php echo esc_html( get_date_from_gmt( get_gmt_from_date( $post->post_date ), get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
                    <td><?php echo esc_html( $this->format_age( $diff ) ); ?></td>
                    <?php if ( $include_actions ) : ?>
                        <td>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                <?php wp_nonce_field( 'wp_sca_publish_now_' . $post->ID, 'wp_sca_nonce' ); ?>
                                <input type="hidden" name="action" value="wp_sca_publish_now">
                                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e( 'Publish now', 'wp-scheduled-content-auditor' ); ?>
                                </button>
                            </form>

                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:4px;">
                                <?php wp_nonce_field( 'wp_sca_bump_schedule_' . $post->ID, 'wp_sca_nonce' ); ?>
                                <input type="hidden" name="action" value="wp_sca_bump_schedule">
                                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>">
                                <input type="hidden" name="bump_minutes" value="60">
                                <button type="submit" class="button">
                                    <?php esc_html_e( 'Bump +1 hour', 'wp-scheduled-content-auditor' ); ?>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Rough duration formatter.
     */
    private function format_age( $seconds ) {
        if ( $seconds <= 0 ) {
            return __( 'in the future', 'wp-scheduled-content-auditor' );
        }

        $minutes = floor( $seconds / 60 );
        $hours   = floor( $minutes / 60 );
        $days    = floor( $hours / 24 );

        if ( $days > 0 ) {
            return sprintf(
                _n( '%s day late', '%s days late', $days, 'wp-scheduled-content-auditor' ),
                number_format_i18n( $days )
            );
        }

        if ( $hours > 0 ) {
            return sprintf(
                _n( '%s hour late', '%s hours late', $hours, 'wp-scheduled-content-auditor' ),
                number_format_i18n( $hours )
            );
        }

        if ( $minutes > 0 ) {
            return sprintf(
                _n( '%s minute late', '%s minutes late', $minutes, 'wp-scheduled-content-auditor' ),
                number_format_i18n( $minutes )
            );
        }

        return __( 'seconds late', 'wp-scheduled-content-auditor' );
    }

    /**
     * Handler for Publish now action.
     */
    public function handle_publish_now() {
        if ( ! current_user_can( 'publish_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to publish content.', 'wp-scheduled-content-auditor' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $nonce   = isset( $_POST['wp_sca_nonce'] ) ? $_POST['wp_sca_nonce'] : '';

        if ( ! $post_id || ! wp_verify_nonce( $nonce, 'wp_sca_publish_now_' . $post_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'wp-scheduled-content-auditor' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'future' ) {
            $this->redirect_with_result( 'none' );
        }

        // I am intentionally using wp_update_post here instead of some hacky direct SQL.
        $result = wp_update_post(
            array(
                'ID'          => $post_id,
                'post_status' => 'publish',
                'post_date'   => current_time( 'mysql' ),
                'post_date_gmt' => current_time( 'mysql', true ),
            ),
            true
        );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_result( 'error' );
        }

        // In theory this should also unschedule the cron hook for this post, but WP usually handles that.
        $this->redirect_with_result( 'published' );
    }

    /**
     * Handler for bumping schedule forward.
     */
    public function handle_bump_schedule() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to reschedule content.', 'wp-scheduled-content-auditor' ) );
        }

        $post_id      = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $nonce        = isset( $_POST['wp_sca_nonce'] ) ? $_POST['wp_sca_nonce'] : '';
        $bump_minutes = isset( $_POST['bump_minutes'] ) ? (int) $_POST['bump_minutes'] : 60;

        if ( $bump_minutes < 1 ) {
            $bump_minutes = 60;
        }

        if ( ! $post_id || ! wp_verify_nonce( $nonce, 'wp_sca_bump_schedule_' . $post_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'wp-scheduled-content-auditor' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'future' ) {
            $this->redirect_with_result( 'none' );
        }

        $current_ts  = strtotime( $post->post_date_gmt . ' GMT' );
        $new_ts      = $current_ts + ( $bump_minutes * MINUTE_IN_SECONDS );
        $new_date_gmt = gmdate( 'Y-m-d H:i:s', $new_ts );
        $new_date    = get_date_from_gmt( $new_date_gmt, 'Y-m-d H:i:s' );

        $result = wp_update_post(
            array(
                'ID'            => $post_id,
                'post_date'     => $new_date,
                'post_date_gmt' => $new_date_gmt,
            ),
            true
        );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_result( 'error' );
        }

        $this->redirect_with_result( 'bumped' );
    }

    private function redirect_with_result( $code ) {
        $url = add_query_arg(
            array(
                'page'          => $this->screen_slug,
                'wp_sca_result' => $code,
            ),
            admin_url( 'tools.php' )
        );

        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Tiny branded touch in the plugin list.
     */
    public function inject_plugin_list_icon_css() {
        $icon_url = esc_url( $this->get_brand_icon_url() );
        ?>
        <style>
            .wp-list-table.plugins tr[data-slug="wp-scheduled-content-auditor"] .plugin-title strong::before {
                content: '';
                display: inline-block;
                vertical-align: middle;
                width: 18px;
                height: 18px;
                margin-right: 6px;
                background-image: url('<?php echo $icon_url; ?>');
                background-repeat: no-repeat;
                background-size: contain;
            }
        </style>
        <?php
    }
}

new WP_Scheduled_Content_Auditor();
