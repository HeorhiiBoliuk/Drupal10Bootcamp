import topHeader from './site-top-header.twig';

import topheaderButtons from '../../01-atoms/top-header-buttons/top-header-buttons.yml';
import topheaderSocial from '../../01-atoms/social_list/social_list.yml';
import topheaderWeatherData from '../../01-atoms/weather-data/weather-data.yml';

/**
 * Storybook Definition.
 */
export default {
  title: 'molecules/TopHeader',
  parameters: {
    layout: 'fullscreen',
  },
};

export const topHead = () =>
  topHeader({
    ...topheaderWeatherData,
    ...topheaderSocial,
    ...topheaderButtons,
  });
