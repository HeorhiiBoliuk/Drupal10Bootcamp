/**
 * Back to Top scrolling.
 */

(function (Drupal, once){
  Drupal.behaviors.backToTop = { attach:function (context) {
    once("backToTop", '.scroll-top', context).forEach( function (element){
      element.addEventListener('click', function (e){
        window.scrollTo({
          top: 0,
          behavior: 'smooth',
        });
      });

      window.addEventListener('scroll', function (e) {
        let high = window.innerHeight;
        let scrollProgress = window.scrollY;
        if (scrollProgress > high * 0.1){
          element.classList.add('open');
        }
        else{
          element.classList.remove('open');
        }
      });
    });
    }};
}(Drupal, once));
