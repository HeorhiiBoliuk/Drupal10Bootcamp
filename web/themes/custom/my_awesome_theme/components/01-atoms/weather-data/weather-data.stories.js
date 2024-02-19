import weather from './weather-data.twig';

import weatherData from './weather-data.yml';

/**
 * Storybook Definition.
 */
export default { title: 'Atoms/Weather-data' };

export const JSTabs = () => weather(weatherData);
