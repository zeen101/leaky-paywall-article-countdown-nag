( function( $ )  {

    $(document).ready( function() {
        
        $( '#issuem-leaky-paywall-articles-remaining-nag #issuem-leaky-paywall-articles-remaining-close' ).click( function(e) {
            e.preventDefault();
            $( '#issuem-leaky-paywall-articles-remaining-nag' ).animate({ left:'-351px' });
        });

    });

    $(window).load( function() {
        
        var bodyClasses = $('body').attr('class').split(' ');

        $.each(bodyClasses, function(i, value) {

            // for everything but pages
            if ( !value.search('postid' ) ) {
                
                var classArray = value.split('-');
                var post_id = parseInt( classArray[1] );

                if ( post_id > 0 ) {

                    var data = {
                        action: 'process_countdown_display',
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
                        action: 'process_countdown_display',
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


})( jQuery );