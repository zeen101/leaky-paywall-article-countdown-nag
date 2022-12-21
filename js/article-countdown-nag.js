var $lpacdn_jquery = jQuery.noConflict();

$lpacdn_jquery( document ).ready( function($) {
	
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
                        $( '#issuem-leaky-paywall-articles-remaining-nag' ).append( response );
                       
                        if ( $('.acn-zero-remaining-overlay').length > 0 ) {
                            $('html').css('overflow', 'hidden');
                        } else {
                            $( '#issuem-leaky-paywall-articles-remaining-nag' ).delay( 3000 ).animate({ left:'0px' });
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
                        $( '#issuem-leaky-paywall-articles-remaining-nag' ).append( response );
                        $( '#issuem-leaky-paywall-articles-remaining-nag' ).delay( 3000 ).animate({ left:'0px' });

                        if ( $('.acn-zero-remaining-overlay').length > 0 ) {
                            $('html').css('overflow', 'hidden');
                        }
                    }
                      
                }, 'html' );

            }
            
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