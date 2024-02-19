import image from './components/01-atoms/images/image/responsive-image.twig';
import figure from './components/01-atoms/images/image/figure.twig';
import iconTwig from './components/01-atoms/images/icons/icons.twig';

import imageData from './components/01-atoms/images/image/image.yml';
import figureData from './components/01-atoms/images/image/figure.yml';

const svgIcons = require.context('../../../images/icons/', true, /\.svg$/);
const icons = [];
svgIcons.keys().forEach((key) => {
  const icon = key.split('./')[1].split('.')[0];
  icons.push(icon);
});

/**
 * Storybook Definition.
 */
export default { title: 'Atoms/Images' };

export const images = () => image(imageData);

export const figures = () => figure(figureData);

export const Icons = () => iconTwig({ icons });
