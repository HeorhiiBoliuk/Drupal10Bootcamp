import siteHeader from './site-header/site-header.twig';
import topheaderWeatherData from '../../01-atoms/weather-data/weather-data.yml';
import topheaderSocial from '../../01-atoms/social_list/social_list.yml';
import topheaderButtons from '../../01-atoms/top-header-buttons/top-header-buttons.yml';

/**
 * Storybook Definition.
 */
let mergedData = 0;
export default {
  title: 'Organisms/Site',
  parameters: {
    layout: 'fullscreen',
  },
};
export const header = () => {
  mergedData = {
    ...topheaderWeatherData,
    ...topheaderSocial,
    ...topheaderButtons,
  };
  return siteHeader(mergedData);
};
