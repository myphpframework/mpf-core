
$(document).ready(function() {
    var slideshows = [];
    $('ul[data-mpf-slideshow]').each(function (index, element) {
        var slideshow = $(element)
          , slideShowId = index
          , delay = parseInt(slideshow.attr('data-mpf-slideshow'))
          , length = parseInt($('li', slideshow).length);
        if (!slideshows.hasOwnProperty(slideShowId)) {
            slideshows[slideShowId] = {
                length: length,
                index: 0,
                interval: null
            };
        }

        var pages = '<ul class="mpf-slideshow-pages">';
        for (var i=0; i < slideshows[slideShowId].length; i++) {
            pages += '<li data-mpf-page-index="'+i+'">'+i+'</li>';
        }
        pages += '</ul>';
        slideshow.parent().append(pages);
        
        function gotoSlide(id, i) {
            $('li', slideshow).each(function (index, element) {
                var tv = $(element);
                tv.css('display', 'none');
                if (i == index) {
                    tv.css('display', 'block');
                    slideshows[id].index = i;
                }
            });
        }
        
        $('.mpf-slideshow-pages li', slideshow.parent()).each(function (index, element) {
            $(element).click(function () {
                $('.mpf-slideshow-pages li', slideshow.parent()).removeClass('current');
                $('.mpf-slideshow-pages li[data-mpf-page-index="'+index+'"]', slideshow.parent()).addClass('current');
                gotoSlide(slideShowId, index);
            });
        });
        
        if (delay != 0 && !isNaN(delay)) {
            function startSlideshow(i) {
                $('.mpf-slideshow-pages li[data-mpf-page-index="0"]', slideshow.parent()).addClass('current');
                slideshows[i].interval = setInterval(function () {
                    slideshows[i].index++;
                    if (slideshows[i].index >= slideshows[i].length) {
                        slideshows[i].index = 0;
                    }

                    $('li', slideshow).each(function (index, element) {
                        var tv = $(element);
                        tv.css('display', 'none');
                        if (slideshows[i].index == index) {
                            tv.css('display', 'block');
                            $('.mpf-slideshow-pages li', slideshow.parent()).removeClass('current');
                            $('.mpf-slideshow-pages li[data-mpf-page-index="'+index+'"]', slideshow.parent()).addClass('current');
                        }
                        
                        tv.mouseover(function () {
                            if (slideshows[i].interval != null) {
                                clearInterval(slideshows[i].interval);
                                slideshows[i].interval = null;
                            }
                        });
                        
                        tv.mouseout(function () {
                            if (slideshows[i].interval == null) {
                                startSlideshow(i);
                            }
                        });
                    });
                }, delay);
            }
            
            startSlideshow(slideShowId);
        }
    });
});
