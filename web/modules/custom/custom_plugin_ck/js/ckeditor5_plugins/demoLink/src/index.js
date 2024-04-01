import { Plugin } from 'ckeditor5/src/core';
import DemoLinkEditing from './demolinkediting';
import DemoLinkUI from './demolinkui';
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
