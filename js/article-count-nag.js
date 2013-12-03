var $issuem_leaky_paywall_article_count_nag = jQuery.noConflict();

$issuem_leaky_paywall_article_count_nag(document).ready(function($) {

    $( window ).load( function() {

        $( '#issuem-leaky-paywall-articles-remaining-nag' ).delay( 3000 ).animate({ left:'0px' });

    });

    $( '#issuem-leaky-paywall-articles-remaining-nag #issuem-leaky-paywall-articles-remaining-close' ).live( 'click', function(e) {

        e.preventDefault();
        $( '#issuem-leaky-paywall-articles-remaining-nag' ).animate({ left:'-300px' });

    });

    $( '#issuem-leaky-paywall-articles-zero-remaining-nag #issuem-leaky-paywall-articles-remaining-close' ).live( 'click', function(e) {

        e.preventDefault();
        $( '#issuem-leaky-paywall-articles-zero-remaining-nag' ).fadeOut( 'slow' );

    });

});