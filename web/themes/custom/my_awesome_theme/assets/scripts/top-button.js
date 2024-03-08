/**
 * Back to Top scrolling.
 */
(function ($, Drupal) {
  Drupal.behaviors.backToTop = {
    attach: function (context) {
      let scrollThreshold = 275;

      $(window).on('scroll', function () {
        if ($(this).scrollTop() > scrollThreshold) {
          if (!$('.scroll-top', context).length) {
            let scrollTopButton = $('<div class="scroll-top"><i class="sfdc-icon icon-angle-up"></i></div>');
            $('body', context).append(scrollTopButton);
            scrollTopButton.on('click', function (event) {
              event.preventDefault();
              $('html, body', context).animate({scrollTop: 0}, 'slow');
              return false;
            });
          }
        } else {
          $('.scroll-top', context).remove();
        }
      });
    }
  };
})(jQuery, Drupal);
