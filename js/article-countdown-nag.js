var $leaky_paywall_article_countdown_nag = jQuery.noConflict();

$leaky_paywall_article_countdown_nag(document).ready(function($) {

    $( window ).load( function() {

        var bodyClasses = $('body').attr('class').split(' ');

        $.each(bodyClasses, function(i, value) {

            console.log(value);

            // for everything but pages
            if ( !value.search('postid' ) ) {
                
                var classArray = value.split('-');
                var post_id = parseInt( classArray[1] );

                if ( post_id > 0 ) {

                    var data = {
                        action: 'maybe_display_countdown',
                        post_id: post_id
                    };

                    $.get(lp_acn.ajaxurl, data, function(response) {
                        if ( response ) {
                            $( "body" ).append( response );
                            $( '#issuem-leaky-paywall-articles-remaining-nag' ).delay( 3000 ).animate({ left:'0px' });
                            if ( $('.acn-zero-remaining-overlay').length > 0 ) {
                                $('html').css('overflow', 'hidden');
                            }
                        }
                          
                    }, 'html' );

                }

            }

            // for pages
            if ( !value.search('page-id' ) ) {
                var classArray = value.split('-');
                var page_id = parseInt( classArray[2] );

                if ( page_id > 0 ) {

                    var data = {
                        action: 'maybe_display_countdown',
                        post_id: page_id
                    };

                    $.get(lp_acn.ajaxurl, data, function(response) {
                        if ( response ) {
                            $( "body" ).append( response );
                            $( '#issuem-leaky-paywall-articles-remaining-nag' ).delay( 3000 ).animate({ left:'0px' });

                            if ( $('.acn-zero-remaining-overlay').length > 0 ) {
                                $('html').css('overflow', 'hidden');
                            }
                        }
                          
                    }, 'html' );

                }

                
            }
        });

    });

    $( '#issuem-leaky-paywall-articles-remaining-nag #issuem-leaky-paywall-articles-remaining-close' ).live( 'click', function(e) {

        e.preventDefault();
        $( '#issuem-leaky-paywall-articles-remaining-nag' ).animate({ left:'-351px' });

    });

});
