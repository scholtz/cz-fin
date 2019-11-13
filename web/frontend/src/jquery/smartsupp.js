import $ from 'jquery'
window.jQuery = $;
window.$ = $;

$(document).ready(function(){
  $.getScript("https://www.smartsuppchat.com/loader.js",function(){
      smartsupp('name', $("#User").data("name"));
      smartsupp('email', $("#User").data("email"));
  });
});