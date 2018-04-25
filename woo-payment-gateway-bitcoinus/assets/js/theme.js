jQuery(document).ready(function($) {

  var tab = $('.nav-tab');
  var content = $('.tabContent');

  content.hide(0);
  $('#tab0').addClass('nav-tab-active');
  $('#content0').show(0);

  tab.on('click',function(e){
    tab.removeClass('nav-tab-active');
    content.hide(0);
    $('#'+e.target.attributes.getNamedItem('id').value).addClass('nav-tab-active');
    $('#'+e.target.attributes.getNamedItem('data-cont').value).show(0);
  });
  
});
