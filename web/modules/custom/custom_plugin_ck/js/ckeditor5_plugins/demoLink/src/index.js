/**
 * @file
 * Defines the demoLink plugin.
 */

import { Plugin } from 'ckeditor5/src/core';
import DemoLinkEditing from './demolinkediting';
import DemoLinkUI from './demolinkui';

/**
 * The DemoLink plugin.
*/
class DemoLink extends Plugin {

  /**
   * @inheritdoc
   */
  static get requires() {
    return [DemoLinkEditing, DemoLinkUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'demoLink';
  }

}

export default {
  DemoLink,
};
