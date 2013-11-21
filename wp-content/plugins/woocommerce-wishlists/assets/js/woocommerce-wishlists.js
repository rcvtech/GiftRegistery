jQuery(document).ready(function($) {

	var bundles = $('.bundle_wrap');
	if (bundles.length) {
		$('.wl-button-wrap').appendTo( $('.bundle_wrap') ).removeClass('hide');
	} else {
		//Move the add to wishlist button inside the variation. 
		var variations = $('.variations_button');
		if (variations.length) {
			$('.wl-button-wrap').appendTo( $('.variations_button') ).removeClass('hide');
		}
	}

	//When page loads...
	$(".wl-panel").hide(); //Hide all content
	$("ul.wl-tabs li:first").addClass("active").show(); //Activate first tab
	$(".wl-panel:first").show(); //Show first tab content

	//On Click Event
	$("ul.wl-tabs li").click(function() {
		$("ul.wl-tabs li").removeClass("active"); //Remove any "active" class
		$(this).addClass("active"); //Add "active" class to selected tab
		$(".wl-panel").hide(); //Hide all tab content
		var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
		$(activeTab).fadeIn(); //Fade in the active ID content
		return false;
	});

	/////////////////////////////////
	// add to wishlist button effects
	/////////////////////////////////	

	// basic wishlist popup stuff	
	$('#wl-list-pop-wrap').hide(); // hide background click-off on load	
	$('.wl-list-pop').hide(); // hide modal on load	
	$('#wl-list-pop-wrap').click(function() {
		$('.wl-list-pop').hide(); // hide modal when click in background	
		$('#wl-list-pop-wrap').hide(); // hide background click-off
		$(window).unbind('scroll', adjust_scroll);
	});

	_productlink = null;
	// position popup at button click 
	$('.wl-add-to').click(function() {
		_productlink = $(this);

		$('#wl-list-pop-wrap').show(); // show background click-off on click
		$('.wl-list-pop').show(); // show modal on click

		var wlx = $(this).offset().left;
		var wly = $(this).offset().top;
		// need to add some code to adjust in case the user is logged in. WHen user is logged in with admin bar, it messes up the CSS since the body/html tags have the margin top on it 
		// need a way to check if admin bar is present, and if so, adjustt the coords below to subtract 28
		if ($('#wpadminbar ').length) { // if admin bar exists, adjust numbers to compensate for bar
			$(".wl-list-pop").css({
				top: wly - 28,
				left: wlx
			}).show();
		} else { // if not logged in, just display in regular position
			$(".wl-list-pop").css({
				top: wly,
				left: wlx
			}).show();
		}

		$(window).bind('scroll', adjust_scroll);

		return false;
	});

	function adjust_scroll() {
		var buttontop = _productlink.offset().top;
		if ($('#wpadminbar ').length) {
			buttontop = buttontop - 28;
		}

		$(".wl-list-pop").css({
			top: buttontop
		});
	}

	// close wishlist on esc key press
	$(document).keyup(function(e) {
		if (e.keyCode == 27) {
			$('.wl-list-pop-wrap').hide();
		}
	});

	$('.wl-add-to-single').click(function(event) {
		event.preventDefault();
		var wlid = $(this).data('listid');

		var $form = $('.wl-add-to').closest('form');

		$form.find("input#wlid").val(wlid);
		$form.attr('action', woocommerce_wishlist_add_to_wishlist_url);
		$form.submit();

		return false;
	});


	$('.wl-shop-add-to-single').click(function(event) {
		event.preventDefault();
		window.location.href = _productlink.attr('href') + "&wlid=" + $(this).data('listid');
		return false;
	});


	$('.wlconfirm').click(function() {
		var message = $(this).data('message');

		var answer = confirm(message ? message : 'Are you sure?');
		return answer;
	});

	$('input[type=checkbox]', '.wl-table thead tr th').click(function() {
		$(this).closest('table').find(':checkbox').attr('checked', this.checked);
	});


	$('.share-via-email-button').click(function(event) {
		var form_id = $(this).data('form');
		$('#' + form_id).trigger('submit', []);
		return true;
	});


	$('.move-list-sel').change(function(event) {

		$('.move-list-sel').val($(this).val());

	});

	$('.btn-apply').click(function(event) {
		event.preventDefault();

		$("#wlupdateaction").val('bulk');
		$('#wl-items-form').submit();

		return false;
	});

	$('#wleditaction1').change(function() {
		$('#wleditaction2').val($(this).val());
	});

	$('#wleditaction2').change(function() {
		$('#wleditaction1').val($(this).val());
	});

});