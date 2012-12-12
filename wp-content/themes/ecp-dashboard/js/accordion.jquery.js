/*=== ACCORDION MENU ===*/
/* 
Simple JQuery menu.
HTML structure to use:

Notes: 

Each menu MUST have a class 'menu' set. If the menu doesn't have this, the JS won't make it dynamic
If you want a panel to be expanded at page load, give the containing LI element the classname 'expand'.
Use this to set the right state in your page (generation) code.

Optional extra classnames for the UL element that holds an accordion:

noaccordion : no accordion functionality
collapsible : menu works like an accordion but can be fully collapsed

<ul class="menu [optional class] [optional class]">
<li><a href="#">Sub menu heading</a>
<ul>
<li><a href="http://site.com/">Link</a></li>
<li><a href="http://site.com/">Link</a></li>
<li><a href="http://site.com/">Link</a></li>
...
...
</ul>
// This item is open at page load time
<li class="expand"><a href="#">Sub menu heading</a>
<ul>
<li><a href="http://site.com/">Link</a></li>
<li><a href="http://site.com/">Link</a></li>
<li><a href="http://site.com/">Link</a></li>
...
...
</ul>
...
...
</ul>

Copyright 2007-2010 by Marco van Hylckama Vlieg

web: http://www.i-marco.nl/weblog/
email: marco@i-marco.nl

Free to use any way you like.
*/


jQuery.fn.initMenu = function() {
    return this.each(function(){
        var theMenu = jQuery(this).get(0);
        jQuery('.acitem', this).hide();
        jQuery('li.expand > .acitem', this).show();
        jQuery('li.expand > .acitem', this).prev().addClass('active');
        jQuery('li a', this).click(
            function(e) {
                e.stopImmediatePropagation();
                var theElement = jQuery(this).next();
                var parent = this.parentNode.parentNode;
                if(jQuery(parent).hasClass('noaccordion')) {
                    if(theElement[0] === undefined) {
                        window.location.href = this.href;
                    }
                    jQuery(theElement).slideToggle('normal', function() {
                        if (jQuery(this).is(':visible')) {
                            jQuery(this).prev().addClass('active');
                        }
                        else {
                            jQuery(this).prev().removeClass('active');
                        }    
                    });
                    return false;
                }
                else {
                    if(theElement.hasClass('acitem') && theElement.is(':visible')) {
                        if(jQuery(parent).hasClass('collapsible')) {
                            jQuery('.acitem:visible', parent).first().slideUp('normal', 
                            function() {
                                jQuery(this).prev().removeClass('active');
                            }
                        );
                        return false;  
                    }
                    return false;
                }
                if(theElement.hasClass('acitem') && !theElement.is(':visible')) {         
                    jQuery('.acitem:visible', parent).first().slideUp('normal', function() {
                        jQuery(this).prev().removeClass('active');
                    });
                    theElement.slideDown('normal', function() {
                        jQuery(this).prev().addClass('active');
                    });
                    return false;
                }
            }
        }
    );
});
};