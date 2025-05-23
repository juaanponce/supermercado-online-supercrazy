<?php

// Exit if accessed directly
if ( ! defined( 'DGWT_WCAS_FILE' ) ) {
	exit;
}

/**
 * Solves conflict with the Divi builder and "Woo Products" module.
 * When using this module the wrong sorting was being determined on the search results page.
 * Due to this, the order of the autocomplete results was not preserved on the search results page.
 */
add_filter( 'woocommerce_get_catalog_ordering_args', function ( $args, $orderby, $order ) {
	if (
		! empty( get_query_var( 'dgwt_wcas' ) ) &&
		$orderby === 'post__in' &&
		$order === 'ASC' &&
		is_callable( 'DgoraWcas\Helpers::is_running_inside_class' ) &&
		\DgoraWcas\Helpers::is_running_inside_class( 'ET_Builder_Module_Shop', 20 )
	) {
		$args = [
			'orderby'  => 'relevance',
			'order'    => 'DESC',
			'meta_key' => '',
		];
	}

	return $args;
}, 10, 3 );

add_filter( 'et_builder_load_requests', function ( $requests ) {
	if ( ! isset( $requests['wc-ajax'] ) ) {
		$requests['wc-ajax'] = array();
	}
	$requests['wc-ajax'][] = 'dgwt_wcas_result_details';

	return $requests;
}, 15 );

add_action( 'wp_footer', function () {
	echo '<div id="wcas-divi-search" style="display: block;">' . do_shortcode( '[wcas-search-form layout="classic" mobile_overlay="1" mobile_breakpoint="980" ]' ) . '</div>';
} );

add_action( 'wp_footer', function () {
	?>
	<script>
		(function ($) {

			$(window).on('load', function () {
				var body = $('body');
				var diviSearch = $('.et-search-form');
				var diviSearchInMenu = $('.et_pb_menu__search-form');
				var search = $('#wcas-divi-search > div');
				if (diviSearch.length === 0 && diviSearchInMenu.length === 0) {
					return;
				}
				// Search in custom header
				if (body.hasClass('et-tb-has-header')) {
					if (diviSearchInMenu.length > 0) {
						diviSearchInMenu.replaceWith(search);
					}
				} else if (body.hasClass('et_header_style_slide') || body.hasClass('et_header_style_fullscreen')) {
					diviSearch = $('.et_slide_in_menu_container .et-search-form');
					if (diviSearch.eq(0)) {
						diviSearch.replaceWith(search);
					}
				} else if (
					body.hasClass('et_header_style_centered') ||
					body.hasClass('et_header_style_split') ||
					body.hasClass('et_header_style_left')
				) {
					diviSearch.replaceWith(search);
					$('.et_search_form_container .et_close_search_field').on('click', function () {
						$('.et_search_form_container .dgwt-wcas-close').trigger('click');
					});
					if (!body.hasClass('et_vertical_nav')) {
						$('#et_top_search').on('click', function () {
							// Header style: Default, Centered Inline Logo
							var mainHeader = $('.et_header_style_left #main-header, .et_header_style_split #main-header');
							if (mainHeader.eq(0)) {
								var mainHeaderHeight = mainHeader.outerHeight(false);
								if (mainHeaderHeight > 0) {
									$('.et_search_form_container .dgwt-wcas-search-wrapp').css('top', (mainHeaderHeight - 40) / 2);
								}
							}
						});
					}
				}

				// Prevent to focus input if it isn't empty (theme trigger focus() when user clicks search icon)
				var $search = $('#main-header .et_search_form_container .dgwt-wcas-search-wrapp');
				$('#et_top_search').on('mousedown', function () {
					if ($search.length > 0) {
						var $input = $search.find('.dgwt-wcas-search-input');
						if ($input.val().length > 0) {
							$input.attr('disabled', 'disabled');
						}
					}
				}).on('click', function () {
					if ($search.length > 0) {
						var $input = $search.find('.dgwt-wcas-search-input');
						if ($input.val().length > 0) {
							setTimeout(function () {
								$input.removeAttr('disabled');

								if (typeof $input.data('autocomplete') == 'object') {
									var instance = $input.data('autocomplete');
									instance.hide();
								}
							}, 100);
						}
					}
				});

				// Open overlay automatically
				$('#et_top_search #et_search_icon, #et_top_search_mob #et_search_icon').on('click', function () {
					if ($(window).width() <= 980) {
						var $handler = $('.js-dgwt-wcas-enable-mobile-form');
						if ($handler.length) {
							$handler[0].click();
						}

						setTimeout(function () {
							var $closeBtn = $('.et_close_search_field');
							if ($closeBtn.length) {
								$closeBtn.trigger('click');
							}
							var $closeBtn2 = $('.dm-search-box .close');
							if ($closeBtn2.length) {
								$closeBtn2.trigger('click');
							}
						}, 1100)
					}
				});

				// Open overlay automatically for search in custom menu
				$('.et_pb_menu .et_pb_menu__search-button').on('click', function () {
					if ($(window).width() <= 980) {
						var $handler = $('.et_pb_menu__search .js-dgwt-wcas-enable-mobile-form');
						if ($handler.length) {
							$handler[0].click();
						}

						setTimeout(function () {
							var $closeBtn = $('.et_pb_menu__close-search-button');
							if ($closeBtn.length) {
								$closeBtn.trigger('click');
							}
						}, 1100)
					} else {
						setTimeout(function () {
							var $input = $('.et_pb_menu__search .dgwt-wcas-search-input');
							if ($input.length > 0 && $input.val().length === 0) {
								$input.trigger('focus');
							}
						}, 500)
					}
				});

				<?php
				// Fix for FiboSearch and Divi Mobile.
				if (defined( 'DE_DM_VERSION' )) {
				?>
				$(document).ready(function () {
					setTimeout(function () {
						const diviMobileSearch = $('#dm-menu .dgwt-wcas-search-input');
						if (diviMobileSearch.length > 0) {
							diviMobileSearch.each(function (index, input) {
								dgwt_wcas.fixer.core.reinitSearchBar($(input));
							});
						}
					}, 1000);
				});
				<?php } ?>
			});
		}(jQuery));
	</script>
	<?php
}, 100 );

add_action( 'wp_head', function () {
	?>
	<style>
		#wcas-divi-search {
			display: none !important;
		}

		/* Custom header */
		.et_pb_menu__search .dgwt-wcas-search-wrapp {
			max-width: none;
		}

		/* Header style: Default, Centered Inline Logo */
		.et_header_style_split .et_search_form_container .dgwt-wcas-search-wrapp,
		.et_header_style_left .et_search_form_container .dgwt-wcas-search-wrapp {
			max-width: 400px;
			top: 0;
			bottom: 0;
			right: 45px;
			position: absolute;
			z-index: 1000;
			width: 100%;
		}

		.et_header_style_split .et_search_form_container .dgwt-wcas-close:not(.dgwt-wcas-inner-preloader),
		.et_header_style_left .et_search_form_container .dgwt-wcas-close:not(.dgwt-wcas-inner-preloader) {
			background-image: none;
		}

		.et_header_style_split .et_search_form_container span.et_close_search_field,
		.et_header_style_left .et_search_form_container span.et_close_search_field {
			right: 5px;
		}

		.et_header_style_split .et_search_form_container .js-dgwt-wcas-mobile-overlay-enabled .dgwt-wcas-search-form,
		.et_header_style_left .et_search_form_container .js-dgwt-wcas-mobile-overlay-enabled .dgwt-wcas-search-form {
			max-width: 100% !important;
		}

		.et_header_style_split .dgwt-wcas-overlay-mobile .dgwt-wcas-search-form,
		.et_header_style_left .dgwt-wcas-overlay-mobile .dgwt-wcas-search-form {
			max-width: 100% !important;
		}

		.dgwt-wcas-overlay-mobile .dgwt-wcas-search-wrapp-mobile {
			top: 0 !important;
		}

		/* Header style: Centered */
		.et_header_style_centered .et_search_form_container .dgwt-wcas-search-wrapp {
			bottom: 20px;
			position: absolute;
		}

		.et_header_style_centered .et_search_form_container .dgwt-wcas-preloader {
			right: 20px;
		}

		.et_header_style_centered .et_search_form_container .dgwt-wcas-close:not(.dgwt-wcas-inner-preloader) {
			background-image: none;
		}

		.et_header_style_centered .et_search_form_container span.et_close_search_field {
			right: 5px;
		}

		/* Header style: Slide in, Fullscreen */
		.et_header_style_fullscreen .et_slide_in_menu_container .dgwt-wcas-search-wrapp,
		.et_header_style_slide .et_slide_in_menu_container .dgwt-wcas-search-wrapp {
			margin-top: 15px;
		}

		.et_header_style_fullscreen .et_slide_in_menu_container .dgwt-wcas-search-wrapp {
			width: 400px;
		}

		.et_header_style_fullscreen .et_slide_in_menu_container .dgwt-wcas-search-input,
		.et_header_style_slide .et_slide_in_menu_container .dgwt-wcas-search-input {
			color: #444;
		}

		@media (max-width: 500px) {
			.et_header_style_fullscreen .et_slide_in_menu_container .dgwt-wcas-search-wrapp {
				width: 100%;
			}

			.et_header_style_slide .et_slide_in_menu_container .dgwt-wcas-search-wrapp {
				width: 100%;
				min-width: 150px;
			}
		}

		/* Full width nav */
		@media (min-width: 981px) {
			.et_fullwidth_nav .et_search_form_container .dgwt-wcas-search-wrapp {
				right: 40px;
			}

			.et_fullwidth_nav .et_search_form_container .dgwt-wcas-preloader {
				right: 0;
			}
		}

		/* Vertical nav */
		@media (min-width: 981px) {
			.et_vertical_nav #main-header .et_search_form_container {
				margin: 0 20px;
			}

			.et_vertical_nav .dgwt-wcas-search-wrapp {
				min-width: 100px;
				bottom: 0 !important;
				position: relative !important;
			}

			.et_vertical_nav .et_search_form_container span.et_close_search_field {
				right: 5px;
			}

			.et_vertical_nav .et_search_form_container .dgwt-wcas-close:not(.dgwt-wcas-inner-preloader) {
				background-image: none;
			}

			.et_vertical_nav .et_search_form_container .dgwt-wcas-preloader {
				right: 5px;
			}
		}
	</style>
	<?php
} );
