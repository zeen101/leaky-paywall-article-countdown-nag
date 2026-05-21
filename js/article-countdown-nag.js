var $lpacdn_jquery = jQuery.noConflict();

$lpacdn_jquery( document ).ready( function($) {

    // Resolve the current post/page ID from the body classes.
    var postId = 0;
    var bodyClasses = ( $('body').attr('class') || '' ).split(' ');

    $.each(bodyClasses, function(i, value) {
        if ( value.indexOf('postid-') === 0 ) {
            postId = parseInt( value.split('-')[1], 10 );
        } else if ( value.indexOf('page-id-') === 0 ) {
            postId = parseInt( value.split('-')[2], 10 );
        }
    });

    if ( ! postId ) {
        return;
    }

    // Read the visitor's viewed-content history from localStorage — the same
    // store LP core's restriction check uses under 5.x. This is what the legacy
    // cookie-based path could no longer see reliably.
    var viewedContent = {};
    try {
        viewedContent = JSON.parse( window.localStorage.getItem('lp_viewed_content') ) || {};
    } catch ( e ) {
        viewedContent = {};
    }

    $.ajax({
        url: lp_acn.restUrl,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ post_id: postId, viewed_content: viewedContent })
    }).done(function(resp) {

        if ( ! resp || 'none' === resp.state || ! resp.html ) {
            return;
        }

        var $nag = $( '#issuem-leaky-paywall-articles-remaining-nag' );
        $nag.append( resp.html );

        if ( 'zero' === resp.state || $('.acn-zero-remaining-overlay').length > 0 ) {
            $('html').css('overflow', 'hidden');
        } else {
            $nag.delay( 3000 ).animate({ left: '0px' });
        }
    });

    $( '#issuem-leaky-paywall-articles-remaining-nag' ).on('click', 'a#issuem-leaky-paywall-articles-remaining-close', function(e) {
        e.preventDefault();
        $( 'div#issuem-leaky-paywall-articles-remaining-nag' ).animate({ left:'-351px' });
    });

    $(document).on('click', '#issuem-leaky-paywall-articles-zero-remaining-nag a', function(e) {
        e.preventDefault();

        var url = $(this).attr('href');
        var post_id = '';
        var nag_loc = '';
        var bodyClasses = $('body').attr('class').split(' ');

        $.each(bodyClasses, function(i, value) {

            if ( !value.search('postid' ) ) {

                var classArray = value.split('-');

                var post_id = parseInt( classArray[1] );

                if ( post_id > 0 ) {

                    nag_loc = post_id;

                }

            }

            // for pages
            if ( !value.search('page-id' ) ) {

                var classArray = value.split('-');
                var post_id = parseInt( classArray[2] );

                if ( post_id > 0 ) {

                    nag_loc = post_id;

                }

            }

        });

        var data = {
            action: 'leaky_paywall_store_nag_location',
            post_id: nag_loc
        };

        $.get(leaky_paywall_script_ajax.ajaxurl, data, function(resp) {
            window.location.href = url;
        });

    });

});