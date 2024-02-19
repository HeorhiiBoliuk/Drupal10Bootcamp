import topheaderbuttonsList from './top-header-buttons.twig';
import topheaderbuttonsData from './top-header-buttons.yml';

/**
 * Storybook Definition.
 */
export default { title: 'Atoms/TopHeaderButtons' };

export const JSTabs = () => topheaderbuttonsList(topheaderbuttonsData);
