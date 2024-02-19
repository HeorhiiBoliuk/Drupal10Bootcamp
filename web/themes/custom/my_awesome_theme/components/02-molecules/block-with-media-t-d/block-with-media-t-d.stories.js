import textWithMediaTwig from './block-with-media-t-d.twig';

import textWithMediaData from './block-with-media-t-d.yml';

/**
 * Storybook Definition.
 */
export default {
  title: 'Molecules/Block With Media Title Data',
  argTypes: {
    blockWithMediaContent: {
      name: 'Text With Media Content (optional)',
      type: 'string',
      defaultValue: textWithMediaData.block_text_title,
    },
  },
};

export const blockWithMedia = ({ blockWithMediaContent }) =>
  textWithMediaTwig({
    ...textWithMediaData,
    block_text_title: blockWithMediaContent,
  });
