/**
 * JavaScript for SMW tooltip functions
 * @see http://www.semantic-mediawiki.org/wiki/Help:Tooltip
 *
 * @since 1.8
 * @release 0.3
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw, smw ) {

	"use strict";

	/*global mediaWiki:true semanticMediaWiki:true*/

	////////////////////////// PRIVATE METHODS ////////////////////////

	// Ensure global object is instantiate
	smw.util = smw.util || {};

	// Helper variable
	var h = mw.html;

	/**
	 * Default options
	 *
	 * viewport => $(window) keeps the tooltip on-screen at all times
	 * 'top center' + 'bottom center' => Position the tooltip above the link
	 * solo => true shows only one tooltip at a time
	 *
	 */
	var defaults = {
			qtip: {
				position: {
					viewport: $(window),
					at: 'top center',
					my: 'bottom center'
				},
				show: {
					solo: true
				},
				content: {
						title: {
							button: false
						}
				},
				style: {
					classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
				}
			},
			classes: {
				iconClass: 'smwtticon',
				contentClass: 'smwttcontent',
				entityClass: 'smwttpersist'
			}
	};

	/**
	 * Build a html element
	 *
	 * @var object
	 * @return string
	 */
	function _getHTMLElement( options ){
		return h.element( 'span', { 'class' : options.entityClass },
			new h.Raw(
				h.element( 'span', { 'class' : options.iconClass, 'data-type': options.type }, null ) +
				h.element( 'span', { 'class' : options.contentClass }, new h.Raw( options.content ) ) )
		);
	}

	////////////////////////// PUBLIC METHODS ////////////////////////

	/**
	 * Constructor
	 * @var Object
	 */
	smw.util.tooltip = function( settings ) {
		$.extend( this, defaults, settings );
	};

	smw.util.tooltip.prototype = {
		/**
		 * Init method initializes the qtip2 instance
		 *
		 * Example
		 * tooltip = new smw.util.tooltip();
		 * tooltip.show ( { title: ..., type: ..., content: ..., button: ..., event: ... } );
		 *
		 * @since 1.8
		 */
		show: function( options ) {
			return options.context.each( function() {
				$( this ).qtip( $.extend( {}, defaults.qtip, {
					hide: options.button ? 'unfocus' : undefined,
					show: { event: options.event, solo: true },
					content: {
						text: options.content,
						title: {
							text: options.title,
							button: options.button
						}
					}
				}	) );
			} );
		},

		/**
		 * The add method is a convenience method allowing to create a tooltip element
		 * with immediate instantiation
		 *
		 * @since 1.8
		 */
		add : function( options ) {
			// Defaults
			var option = $.extend( true, defaults.classes, options );

			// Check context
			if ( option.context === undefined ){
				return $.error( 'smw.util.tooltip add method is missing a context object' );
			}

			// Assign context
			var $this = option.context;

			// Append element
			$this.prepend( _getHTMLElement( options ) );

			// Ensure the rigth scope and use the icon as hoover/click element
			// The class [] selector is not the fastest but the safest otherwise if spaces are
			// used in the class definition it will break the selection
			this.show.call(
				$this.find( "[class='" + option.iconClass + "']" ),
				$.extend( true, options, { content: $this.find( "[class='" + option.contentClass + "']" ) } )
			);
		}
	};



	////////
	// @todo keep this one for now, as long as modules in SRF rely on it and only
	// after they have been updated below can vanish
	var methods = {
		init : function( options ) {
			return this.each( function() {
				var tooltip  = new smw.util.tooltip();

				// Tooltip instance
				tooltip.show( {
					context: $( this ),
					content: options.content,
					title: options.title,
					button: options.button
				} );
			} );
		},
		add : function( options ) {
			var tooltip  = new smw.util.tooltip();

			// Tooltip instance
			tooltip.add( $.extend( true, options, { context: this } ) );
		}
	};

	// Extends jquery
	$.fn.smwTooltip = function( method ) {

		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on smwTooltip' );
		}
	};
	////////

	/////////////////////////////// DOM //////////////////////////////

	$( document ).ready( function() {

		// Class reference
		var tooltip = new smw.util.tooltip();

		// Mostly used for special properties and quantity conversions
		$( '.smwttinline' ).each( function() {
			var $this = $( this );

			// Tooltip instance
			tooltip.show( {
				context: $this,
				content: $this.find( '.smwttcontent' ),
				title  : $this.data( 'type' ) === 'quantity' ? mw.msg( 'smw-ui-tooltip-title-quantity' ) : mw.msg( 'smw-ui-tooltip-title-property' ),
				button : false
			} );

		} );

		// Tooltip with extended interactions for service links, info, and error messages
		$( '.smwttpersist' ).each( function() {

			// Using a click event instead to trigger the tooltip
			var event = mw.user.options.get( 'smw-prefs-tooltip-option-click' ) ? 'click' : undefined;

			// Standard configuration
			var $this = $( this ),
				content = $this.find( '.smwttcontent' ),
				title = mw.msg( 'smw-ui-tooltip-title-info' ),
				button = true; // Display close button

			// Find icon reference where it exists
			$this.find( '.smwtticon' ).each( function() {

				// Change title in accordance with its type
				var type = $( this ).data( 'type' );
				if ( type === 'service' ){
					title = mw.msg( 'smw-ui-tooltip-title-service' );
				} else if ( type === 'warning' ) {
					title = mw.msg( 'smw-ui-tooltip-title-warning' );
					button = false; // No close button
				} else {
					title = mw.msg( 'smw-ui-tooltip-title-info' );
				}
			} );

			// Tooltip instance
			tooltip.show( {
				context: $this,
				content: content,
				title  : title,
				event  : event,
				button : button
			} );

		} );
	} );
} )( jQuery, mediaWiki, semanticMediaWiki );