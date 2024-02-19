import socialList from './social_list.twig';
import socialData from './social_list.yml';

/**
 * Storybook Definition.
 */
export default { title: 'Atoms/Social List' };

export const JSTabs = () => socialList(socialData);
