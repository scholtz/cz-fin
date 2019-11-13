import $ from 'jquery'
window.jQuery = $;
window.$ = $;

$(document).ready(function(){
    
    $(".personal").on("click",function(){
        document.location = '/Personal';
    });
    $(".premium").on("click",function(){
        document.location = '/Premium';
    });
    $(".enterprise").on("click",function(){
        document.location = '/Enterprise';
    });
    
    
   $.getScript( "/Scripts?"+Date.now() );

   window.dataLayer = window.dataLayer || [];
   function gtag(){dataLayer.push(arguments);}
   gtag('js', new Date());
   gtag('config', 'UA-3898474-31');
   
   $.getScript( "https://www.googletagmanager.com/gtag/js?id=UA-3898474-31" );
   $.getScript( "https://connect.facebook.net/cs_CZ/sdk.js#xfbml=1&version=v4.0&appId=133245520136227&autoLogAppEvents=1");
   
});