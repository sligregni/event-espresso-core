(function ( $ ) {

	/**
	*	add jQuery functions
	*/
	$.fn.extend({

		/**
		*	center elements on screen
		 * @param {string}  position // relative, absolute or fixed (default)
		*/
		eeCenter : function( position ) {
			position = typeof position !== 'undefined' && position !== '' ? position : 'fixed';
			var element_top = (( $( window ).height() / 2 ) - this.outerHeight() ) / 2;
			element_top = position === 'fixed' ? element_top + $( window ).height() / 8 : element_top + $( window ).scrollTop;
			element_top = Math.max( 0, element_top );
			var element_left = ( $( window ).width() - this.outerWidth() ) / 2;
			element_left = position === 'fixed' ? element_left : element_left + $( window ).scrollLeft;
			element_left = Math.max( 0, element_left );
			this.css({ 'position' : position, 'top' : element_top + 'px', 'left' : element_left + 'px' , 'margin' : 0 });
			return this;
		},


		/**
		 * Shortcut for adding a window overlay quickly if none exists in the dom
		 *
		 * @param {int} opacity allows the setting of the opacity value for the overlay via client. opacity[0] = webkit opacity, opacity[1] = value for alpha(opacity=).
		 * @return {jQuery}
		 */
		eeAddOverlay : function( opacity ) {
			opacity = typeof opacity === 'undefined' || opacity > 1 ? 0.5 : opacity;
			var overlay = '<div id="ee-overlay"></div>';
			$(overlay).appendTo('body').css({
				'position' : 'fixed',
				'top' : 0,
				'left' : 0,
				'width' : '100%',
				'height' : '100%',
				'background' : '#000',
				'opacity' : opacity,
				'filter' : 'alpha(opacity=' + ( opacity * 100 ) + ')',
				'z-index' : 10000
			});
			return this;
		},




		/**
		 * Shortcut for removing a window overlay quickly if none exists in the dom (will destroy)
		 * @return {jQuery}
		 */
		eeRemoveOverlay : function() {
			$('#ee-overlay').remove();
			return this;
		},


		/**
		 * adds a scrollTo action for jQuery
		 * @return {jQuery}
		 */
		eeScrollTo : function( speed ) {
			var selector = this;
			speed = typeof(speed) === 'undefined' ? 2000 : speed;
			$("html,body").animate({
				scrollTop: selector.offset().top - 80
			}, speed);
			return this;
		},


		/**
		*	return the correct value for a form input regardless of it's type
		*/
		eeInputValue : function () {
			var inputType = this.prop('type');
			if ( inputType ===  'checkbox' || inputType === 'radio' ) {
				return this.prop('checked');
			} else {
				return this.val();
			}
		},


		/**
		*	return an object of URL params
		*/
		eeGetParams : function () {
			var urlParams = {};
			var url = this.attr('href');
			url = typeof url !== 'undefined' && url !== '' ? url : location.href;
			url = url.substring( url.indexOf( '?' ) + 1 ).split( '#' );
			urlParams.hash = typeof url[1] !== 'undefined' && url[1] !== '' ? url[1] : '';
			var qs = url[0].split( '&' );
			for( var i = 0; i < qs.length; i++ ) {
				qs[ i ] = qs[ i ].split( '=' );
				urlParams[ qs[ i ][0] ] = decodeURIComponent( qs[ i ][1] );
			}
			return urlParams;
		},


		/**
		 * Set element visibility to hidden
		 *
		 */
		eeInvisible: function() {
			return this.each( function() {
				$(this).css("visibility", "hidden");
			});
		},


		/**
		 * Set element visibility to visible
		 *
		 */
		eeVisible: function() {
			return this.each( function() {
				$(this).css("visibility", "visible");
			});
		},



		/**
		 * This helper method simply removes any matching items from a js array.
		 * @param  {array} arr js array to remove items from
		 * @param  {string}   ind value of what element is being removed
		 * @return {array}    new array with removed items
		 */
		removeFromArray: function( arr, ind ) {
			return arr.filter( function(i) {
				return i !== ind;
			});
		}


	});

}( jQuery ));


jQuery(document).ready(function($) {

	var existing_message = $('#message');
	$('.show-if-js').css({ 'display' : 'inline-block' });
	$('.hide-if-no-js').removeClass( 'hide-if-no-js' );


	window.do_before_admin_page_ajax = function do_before_admin_page_ajax() {
		// stop any message alerts that are in progress
		$(existing_message).stop().hide();
		// spinny things pacify the masses
		$('#espresso-ajax-loading').eeCenter().show();
	};



	window.show_admin_page_ajax_msg = function show_admin_page_ajax_msg( response, beforeWhat, removeExisting ) {
		removeExisting = typeof removeExisting !== false;
		var messages = $( '#ajax-notices-container' );
		// if there is no existing message...
		if ( removeExisting === true ) {
			messages.html('');
		}
		// make sure there is at least ONE notification to display
		if (
			( typeof response !== 'object' ) ||
			! ( // or NOT the following
				( typeof response.success !== 'undefined' && response.success !== '' && response.success !== false ) ||
				( typeof response.attention !== 'undefined' && response.attention !== '' && response.attention !== false ) ||
				( typeof response.errors !== 'undefined' && response.errors !== '' && response.errors !== false )
			)
		) {
			console.log( JSON.stringify( 'show_admin_page_ajax_msg response: ', null, 4 ) );
			console.log( response );
			return;
		}

		$( '#espresso-ajax-loading' ).fadeOut( 'fast' );
		var msg = '';
		// no existing errors?
		if ( typeof(response.errors) !== 'undefined' && response.errors !== '' && response.errors !== false ) {
			msg = msg + '<div class="ee-admin-notification error hidden"><p>' + response.errors + '</p></div>';
		}
		// attention notice?
		if ( typeof(response.attention) !== 'undefined' && response.attention !== '' && response.attention !== false ) {
			msg = msg + '<div class="ee-admin-notification ee-attention hidden"><p>' + response.attention + '</p></div>';
		}
		// success ?
		if ( typeof(response.success) !== 'undefined' && response.success !== '' && response.success !== false ) {
			msg = msg + '<div class="ee-admin-notification updated hidden ee-fade-away"><p>' + response.success + '</p></div>';
		}
		messages.html( msg );
		var new_messages = messages;
		messages.remove();
		beforeWhat = typeof beforeWhat !== 'undefined' && beforeWhat !== '' ? beforeWhat : '.nav-tab-wrapper';
		// set message content
		var messages_displayed = false;
		$( 'body, html' ).animate( { scrollTop : 0 }, 'normal', function() {
			if ( ! messages_displayed ) {
				$( beforeWhat ).before( new_messages );
			}
		} );
		// and display it
		new_messages.find('.ee-admin-notification').each( function() {
			$( this ).removeAttr( 'style' ).removeClass( 'hidden' ).show();
			//  but sometimes not too long
			if ( $( this ).hasClass( 'ee-fade-away' ) ) {
				$( this ).delay( 8000 ).fadeOut();
			}
		} );

	};


	function display_espresso_notices() {
		$('#espresso-notices').eeCenter();
		$('.espresso-notices').slideDown();
		$('.espresso-notices.fade-away').delay(10000).slideUp();
	}
	display_espresso_notices();



	function display_espresso_ajax_notices( message, type ) {
		type = typeof type !== 'undefined' && type !== '' ? type : 'error';
		var notice_id = '#espresso-ajax-notices-' + type;
		$( notice_id + ' .espresso-notices-msg' ).text( message );
		$( '#espresso-ajax-notices' ).eeCenter();
		$( notice_id ).slideDown('fast');
		$('.espresso-ajax-notices.fade-away').delay(10000).slideUp('fast');
	}


	//close btn for notifications
	$('.close-espresso-notice').on( 'click', function(e){
		$(this).parent().hide();
		e.preventDefault();
		e.stopPropagation();
	});



	// submit form
	$('.submit-this-form').click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).closest('form').submit();
		return false;
	});



	// generic click event for displaying and giving focus to an element and hiding control
	$('.display-the-hidden').on( 'click', function(e) {
		// get target element from "this" (the control element's) "rel" attribute
		var item_to_display = $(this).attr("rel");
		//alert( 'item_to_display = ' + item_to_display );
		// hide the control element
		$(this).fadeOut(50).hide();
		// display the target's div container - use slideToggle or removeClass
		$('#'+item_to_display+'-dv').slideToggle(250, function() {
			// display the target div's hide link
			$('#hide-'+item_to_display).show().fadeIn(50);
			// if hiding/showing a form input, then id of the form input must = item_to_display
			$('#'+item_to_display).focus(); // add focus to the target
		});
		e.preventDefault();
		e.stopPropagation();
		return false;
	});



	// generic click event for re-hiding an element and displaying it's display control
	$('.hide-the-displayed').on( 'click', function(e) {
		// get target element from "this" (the control element's) "rel" attribute
		var item_to_hide = $(this).attr("rel");
		// hide the control element
		$(this).fadeOut(50).hide();
		// hide the target's div container - use slideToggle or addClass
		$('#'+item_to_hide+'-dv').slideToggle(250, function() {
			// display the control element that toggles display of this element
			$('#display-'+item_to_hide).show().fadeIn(50);
		});
		e.preventDefault();
		e.stopPropagation();
		return false;
	});



	// generic click event for resetting a form input - can be coupled with the "hide_the_displayed" function above
	$('.cancel').click(function() {
		// get target element from "this" (the control element's) "rel" attribute
		var item_to_cancel = $(this).attr("rel");
		// set target element's value to an empty string
		$('#'+item_to_cancel).val('');
		e.preventDefault();
		e.stopPropagation();
	});



	/**
	 * when a Country select dropdown changes, find its corresponding state select dropdown
	 * and hide all option groups that do not correspond to the selected country
	 */
	$( '.ee-country-select-js' ).change(
		function () {
			var country_select_id = $( this ).attr( 'id' ),
				selected_country  = $( this ).find( "option:selected" ).text(),
				state_select_id   = '',
				$state_select      = null,
				selected_state    = null,
				valid_option      = false;

			// console_log( 'country_select_id', country_select_id, true );
			// console_log( 'selected_country', selected_country, false );
			// console_log( 'state_select_id', state_select_id, false );
			// console_log( 'country_select_id.indexOf( country )', ~country_select_id.indexOf( 'country' ), false );

			// is this country question a system question ?
			if ( ~country_select_id.indexOf( 'country' ) ) {
				// good, then just swap 'country' for 'state' to get the corresponding state select id
				state_select_id = country_select_id.replace( 'country', 'state' );
			} else {
				// no ??? dang... now we have to try and find the corresponding state question.
				var $state_div = $(this).parent().next('.ee-state-select-js-input-dv');
				if ( ! $state_div.length ) {
					// console.log( 'State Select div not found after Country Select div' );
					$state_div = $( this ).parent().prev( '.ee-state-select-js-input-dv' );
				}
				if ( ! $state_div.length ) {
					console.log(
						'Can not find corresponding State select for Country select with id: '
						+ country_select_id + '. Ideally the State question should be immediately after the Country question.'
					);
				}
				$state_select = $state_div.find('.ee-state-select-js');
				if ( $state_select === null || ! $state_select.length ) {
					// going to keep the following commented out code in case we need to support
					// country <=> state question pairs that are not immediately next to each other
					// var search_id_parts = country_select_id.split( '-' );
					// console_log( 'search_id_parts', search_id_parts, false );
					// var search_id = '',
					// 	select_id = '';
					// // event id should be next
					// if ( typeof search_id_parts[ 1 ] === 'undefined' || typeof search_id_parts[ 2 ] === 'undefined' ) {
						console.log(
							'Invalid "country_select_id"! Can not find corresponding State select for Country select with id: '
							+ country_select_id
						);
						return;
					// }
					// something like: 'ee_reg_qstn' + '-' + event_id + '-'
					// search_id = search_id_parts[ 0 ] + '-' + search_id_parts[ 1 ] + '-';
					// // search_id += search_id_parts[ 2 ];
					//
					// console_log( 'search_id', search_id, false );
					// $( '.ee-state-select-js' ).each(
					// 	function () {
					// 		select_id = $( this ).attr( 'id' );
					// 		console_log( 'select_id', select_id, true );
					// 		console_log( "~select_id.indexOf( 'state' )", ~select_id.indexOf( 'state' ), false );
					// 		console_log(
					// 			"~select_id.indexOf( 'nsmf_new_state' )",
					// 			~select_id.indexOf( 'nsmf_new_state' ),
					// 			false
					// 		);
					// 		console_log( "~select_id.indexOf( 'search_id' )", ~select_id.indexOf( search_id ), false );
					// 		// skip any state system questions
					// 		if ( ~select_id.indexOf( 'state' )
					// 			 || ~select_id.indexOf( 'nsmf_new_state' )
					// 			 || !~select_id.indexOf( search_id ) ) {
					// 			console.log( 'NOT A MATCH' );
					// 			return true;
					// 		}
					// 		console_log( 'MATCH select_id', select_id, false );
					// 		var select_id_parts = select_id.split( '-' );
					// 		if ( typeof select_id_parts[ 1 ]
					// 			 === 'undefined'
					// 			 || typeof select_id_parts[ 2 ]
					// 				=== 'undefined' ) {
					// 			return true;
					// 		}
					// 		select_id              = select_id_parts[ 0 ] + '-' + select_id_parts[ 1 ] + '-';
					// 		var select_question_id = select_id_parts[ 2 ];
					// 	}
					// );
				}
				state_select_id = $state_select.attr( 'id' );
			}
			if ( ( $state_select === null || ! $state_select.length ) && state_select_id !== '' ) {
				// console_log( 'state_select_id', state_select_id, false );
				$state_select = $( '#' + state_select_id );
			}

			if ( $state_select.length ) {
				// grab the currently selected state (if there is one)
				selected_state = $state_select.find( ":selected" ).val();
				// console_log( 'selected_state', selected_state, false );
				// remove span tags from all optgroups
				$( 'span > optgroup', $state_select ).unwrap();
				// if a valid country is selected
				if ( selected_country !== '' ) {
					// wrap all unselected optgroup with span tags which effectively hides them in the dropdown
					$( 'optgroup:not([label="' + selected_country + '"])', $state_select ).wrap( '<span></span>' );
					// if a valid corresponding state select exists
					if ( selected_state.length ) {
						// loop through all of its optgroups
						$state_select.find( 'optgroup' ).each(
							function () {
								// if this optgroup is not hidden (wrapped in  a span)
								if ( $(this).parent().prop( "tagName" ) == 'SELECT' ) {
									// then loop through each of its options
									$( this ).find( 'option' ).each(
										function () {
											// was this option match the previously selected state ?
											if ( $( this ).val() == selected_state ) {
												valid_option = true;
												// make sure it's set as the selected option
												$state_select.val( selected_state ).change();
											}
										}
									);
								}
							}
						);
					}
					// console_log( 'valid_option', valid_option, false );
					// if the previously selected state is not valid
					if ( ! valid_option ) {
						// makes sure no option is selected
						$( "option:selected", $state_select ).prop( "selected", false );
						// then find the empty placeholder and select it
						$state_select
							.find( 'optgroup[label=""]' )
							.unwrap()
							.find( 'option[value=""]' )
							.attr( 'selected', 'selected' );
						// select it again to be sure
						$state_select.val('')
					} else {
						// previously selected state IS valid
						// so make sure the empty placeholder is unselected
						$state_select
							.find( 'optgroup[label=""]' )
							.find( 'option[value=""]' )
							.removeAttr( 'selected' );
					}
				} else {
					// console.log( JSON.stringify( 'NO COUNTRY SELECTED', null, 4 ) );
					// unwrap any wrapped elements
					$state_select.find( 'optgroup' ).each(
						function () {
							// console_log( 'optgroup', $( this ).val(), false );
							if ( $( this ).parent().prop( "tagName" ) == 'SPAN' ) {
								$( this ).unwrap();
							}
						}
					);
				}
			}
		}
	);

});



//functions available to window



/**
 * Function : dump()
 * Arguments: The data - array,hash(associative array),object
 *    The level - OPTIONAL
 * Returns  : The textual representation of the array.
 * This function was inspired by the print_r function of PHP.
 * This will accept some data as the argument and return a
 * text that will be a more readable version of the
 * array/hash/object that is given.
 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
 */
function dump(arr,level) {
	var dumped_text = "";
	if ( ! level ) {
		level = 0;
	}

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) {
		level_padding += "    ";
	}
	//Array/Hashes/Objects
	if( typeof(arr) === 'object' ) {
		for(var item in arr) {
			if ( typeof item !== 'undefined' && arr.hasOwnProperty( item ) ) {
				var value = arr[item];
				//If it is an array
				if( typeof(value) === 'object' ) {
					dumped_text += level_padding + "'" + item + "' ...\n";
					dumped_text += dump(value,level+1);
				} else {
					dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
				}
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

/**
 *  @function console_log
 *  print to the browser console
 * @param  {string} item_name
 * @param  {*} value
 * @param  {boolean} spacer
 */
function console_log( item_name, value, spacer ) {
	if ( typeof value === 'object' ) {
		console_log_object( item_name, value, 0 );
	} else {
		if ( spacer === true ) {
			console.log( ' ' );
		}
		if ( typeof item_name !== 'undefined' && typeof value !== 'undefined' && value !== '' ) {
			console.log( item_name + ' = ' + value );
		} else if ( typeof item_name !== 'undefined' ) {
			console.log( item_name );
		}
	}
}

/**
 * @function console_log_object
 * print object to the browser console
 * @param  {string} obj_name
 * @param  {object} obj
 * @param  {number} depth
 */
function console_log_object( obj_name, obj, depth ) {
	depth      = typeof depth !== 'undefined' ? depth : 0;
	var spacer = '';
	for ( var i = 0; i < depth; i++ ) {
		spacer = spacer + '    ';
	}
	if ( typeof obj === 'object' ) {
		if ( !depth ) {
			console.log( ' ' );
		}
		if ( typeof obj_name !== 'undefined' ) {
			console.log( spacer + 'OBJ: ' + obj_name + ' : ' );
		} else {
			console.log( spacer + 'OBJ : ' );
		}
		jQuery.each(
			obj, function ( index, value ) {
				if ( typeof value === 'object' && depth < 6 ) {
					depth++;
					console_log_object( index, value, depth );
				} else {
					console.log( spacer + index + ' = ' + value );
				}
				depth = 0;
			}
		);
	} else {
		console_log( spacer + obj_name, obj, true );
	}
}