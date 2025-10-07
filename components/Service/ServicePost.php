<?php
namespace BuddyClients\Components\Service;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use BuddyClients\Components\Service\Service;

/**
 * Outputs html for a single service post.
 *
 * @since 1.0.21
 */
class ServicePost {

    /**
     * The ID of the service post.
     * 
     * @var int
     */
    public $post_id;

    /**
     * The service object. 
     * 
     * @var Service
     */
    private $service;

    /**
     * The label for the rate type.
     * 
     * @var string
     */
    private $rate_type_label;

    /**
     * The label preceding the rate amount.
     * 
     * @var string
     */
    private $rate_label;

    /**
     * Constructor method.
     * 
     * @since 1.0.21
     * 
     * @param   int $post_id    The ID of the service post.
     */
    public function __construct( $post_id ) {
        $this->post_id = $post_id;
        $this->service = buddyc_get_service_cache( 'service', $this->post_id );
        $this->get_var();
    }

    /**
     * Defines variables.
     * 
     * @since 1.0.21
     */
    private function get_var() {
        $this->rate_type_label = $this->get_rate_type_label();
        $this->rate_label = $this->get_rate_label();
    }

    /**
     * Builds the rate label.
     * 
     * @since 1.0.21
     */
    private function get_rate_type_label() {
        // Initialize
        $label = '';

        // Flat rate type
        if ( $this->service->rate_type === 'flat' ) {
            $label = esc_html__( 'flat', 'buddyclients-lite' );

        // Other rate type
        } else if ( $this->service->rate_type ) {
            $label = esc_html__( 'per', 'buddyclients-lite' ) . ' ' . strtolower( esc_html( get_post_meta( $this->service->rate_type, 'singular', true ) ) );
        }
        return $label;
    }

    /**
     * Builds the rate label.
     * 
     * @since 1.0.21
     */
    private function get_rate_label() {
        return $this->service->adjustments ? esc_html__( 'Starting At', 'buddyclients-lite' ) : esc_html__( 'Rate', 'buddyclients-lite' );
    }

    /**
     * Builds the booking form button. 
     * 
     * @since 1.0.21
     */
    private function booking_form_btn() {
        // Display button if not hidden from form
        if ( ! $this->service->hide ) {
            // Get booking page link
            $booking_page_link = buddyc_get_page_link( 'booking_page' );
            // Make sure the booking page exists
            if ( $booking_page_link && $booking_page_link !== '#' ) {
                $btn_args = [
                    'text'  => __( 'Book Now', 'buddyclients-lite' ),
                    'link'  => $booking_page_link,
                    'type'  => 'secondary',
                    'size'  => 'wide'
                ];
                return buddyc_btn( $btn_args );
            }
        }
    }

    /**
     * Renders the post content.
     * 
     * @since 1.0.21
     */
    public function render() {            
        
        // Open container
        $content = '<div class="buddyc-max-1000">';
        
        // Title
        $content .= '<h1>' . esc_html( $this->service->title ) . '</h1>';
        
        // Build single service content
        $content .= '<div>';
        $content .= get_the_content( $this->post_id );
        $content .= '</div>';
        
        $content .= $this->booking_form_btn();
        
        // Details
        $content .= '<div>';
        
        $content .= $this->build_rate_line();
        $content .= $this->build_dependency_line();
        
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }

    /**
     * Builds the rate line.
     * 
     * @since 1.0.21
     *
     * @return string The formatted rate line or an empty string if no rate value.
     */
    private function build_rate_line() {
        if ( empty( $this->service->rate_value ) ) {
            return '';
        }

        $rate_label = esc_html( $this->rate_label );
        $rate_value = esc_html( $this->service->rate_value );
        $rate_type_label = esc_html( $this->rate_type_label );

        /* translators: %1$s: the label preceding the rate (e.g. Starting At); %2$s: the value of the rate (e.g. $100); %3$s: the label for the rate (e.g. flat, per hour) */
        return sprintf( '<p><b>%1$s</b>: $%2$s %3$s</p>', $rate_label, $rate_value, $rate_type_label );
    }

    /**
     * Builds the dependencies line.
     * 
     * @since 1.0.21
     * 
     * @return string The dependencies line HTML or an empty string.
     */
    private function build_dependency_line() {
        // Get dependencies links
        $dependency_link = $this->build_dependencies_link();

        if ( $dependency_link ) {
            return sprintf(
                /* translators: %1$s: 'This service requires'; %2$s: the links to the services this service requires */
                '<p>%1$s %2$s</p>',
                esc_html__( 'This service requires', 'buddyclients-lite' ),
                $dependency_link
            );
        }
    }

    /**
     * Retrieves and formats service dependencies.
     * 
     * @since 1.0.21
     * 
     * @return string The formatted dependencies link or an empty string.
     */
    private function build_dependencies_link() {
        // Get dependencies
        $dependencies = $this->service->dependency;

        // Initialize
        $dependency_array = [];
        $dependency_link = '';

        if ( $dependencies ) {
            // Process each dependency
            foreach ( $dependencies as $dependency_id ) {
                // Ensure dependency ID is not empty
                if ( ! empty( $dependency_id ) ) {
                    $dependency_name = get_the_title( $dependency_id );
                    $dependency_url = get_permalink( $dependency_id );

                    $dependency_array[] = '<a href="' . esc_url( $dependency_url ) . '">' . esc_html( $dependency_name ) . '</a>';
                }
            }

            // Construct human-readable list
            if ( ! empty( $dependency_array ) ) {
                $count = count( $dependency_array );
                $dependency_list = '';

                for ( $i = 0; $i < $count; $i++ ) {
                    $dependency_list .= $dependency_array[ $i ];

                    if ( $count > 2 && $i == $count - 2 ) {
                        $dependency_list .= ', ' . esc_html__( 'or', 'buddyclients-lite' ) . ' ';
                    } elseif ( $i == $count - 2 ) {
                        $dependency_list .= ' ' . esc_html__( 'or', 'buddyclients-lite' ) . ' ';
                    } elseif ( $i < $count - 1 ) {
                        $dependency_list .= ', ';
                    }
                }

                $dependency_link = $dependency_list . '.';
            }
        }

        return $dependency_link;
    }


}