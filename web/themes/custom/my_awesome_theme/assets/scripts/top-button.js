/**
 * Back to Top scrolling.
 */

(function (Drupal, once) {
  Drupal.behaviors.backToTop = {
    attach: function (context) {
      once("backToTop", '.footer_bottom', context).forEach(function (element) {
        let backToTopButton = document.createElement('div');
        backToTopButton.classList.add('scroll');
        let img = document.createElement('img');
        img.src = '/themes/custom/my_awesome_theme/assets/images/up-arrow-png-8.png';
        backToTopButton.appendChild(img);
        element.appendChild(backToTopButton);

        backToTopButton.addEventListener('click', function (e) {
          window.scrollTo({
            top: 0,
            behavior: 'smooth',
          });
        });

        window.addEventListener('scroll', function (e) {
          let high = window.innerHeight;
          let scrollProgress = window.scrollY;
          if (scrollProgress > high * 0.1) {
            backToTopButton.classList.add('open');
          } else {
            backToTopButton.classList.remove('open');
          }
        });
      });
    }
  };
}(Drupal, once));
