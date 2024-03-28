/**
 * @file
 * Defines the DemoLinkEditing plugin.
 */

import {Plugin} from 'ckeditor5/src/core';
import DemoLinkCommand from "./demolinkcommand";

/**
 * The demoLink editing feature.
 */
export default class DemoLinkEditing extends Plugin {

  /**
   * @inheritDoc
   */
  init() {
    this._defineSchema();
    this._defineConverters();

    // Attaching the command to the editor.
    this.editor.commands.add(
      'demoLink',
      new DemoLinkCommand(this.editor),
    );

  }

  /**
   * Registers schema for demoLink and its child elements.
   */
  _defineSchema() {
    const schema = this.editor.model.schema;

    schema.register('demoLink', {
      inheritAllFrom: '$inlineObject',
      allowAttributes: [
        'demoLinkUrl',
        'demoBackgroundColor',
        'demoTextColor',
        'demoLinkClass'
      ],
      allowChildren: [
        'demoLinkText',
      ],
    });

    schema.register('demoLinkText', {
      allowIn: 'demoLink',
      isLimit: true,
      allowContentOf: '$block',
    });

  }

  /**
   * Defines converters.
   */
  _defineConverters() {
    const { conversion } = this.editor;

    // demoLink. View -> Model.
    conversion.for('upcast').elementToElement({
      view: {
        name: 'a',
        styles: {
          'color': true,
          'background-color': true
        }
      },
      model: (viewElement, { writer }) => {
        const classes = viewElement.getAttribute('class');
        if (!classes) {
          return null;
        }
        if (!classes.split(' ').includes('demo-link')) {
          return null;
        }
        const attrs = {
          'demoLinkUrl': viewElement.getAttribute('href'),
          'demoBackgroundColor': viewElement.getStyle('background-color'),
          'demoTextColor': viewElement.getStyle('color')
        };
        return writer.createElement('demoLink', attrs);
      }
    });

    conversion.for('downcast').elementToElement({
      model: 'demoLink',
      view: (modelElement, { writer }) => {
        const htmlAttrs = {
          'class': 'demo-link',
          'href': modelElement.getAttribute('demoLinkUrl'),
          'style': `background-color: ${modelElement.getAttribute('demoBackgroundColor')}; color: ${modelElement.getAttribute('demoTextColor')};`
        };
        return writer.createContainerElement('a', htmlAttrs);
      }
    });

    // href to demoLinkUrl. View -> Model.
    conversion.for('upcast').attributeToAttribute({
      view: {
        name: 'a',
        styles: {
          ['color']: true
        },
        attributes: {
          ['href']: true
        }
      },
      model: {
        key: 'demoLinkUrl',
        value: viewElement => {
          const attrs = {
            'demoLinkUrl': viewElement.getAttribute('href'),
            'demoTextColor': viewElement.getStyle('color')
          };
          return attrs;
        }
      }
    });

    // class" to demoLinkClass. View -> Model.
    conversion.for('upcast').attributeToAttribute({
      view: {
        name: 'a',
        attributes: {
          ['class']: true
        }
      },
      model: {
        key: 'demoLinkClass',
        value: viewElement => {
          return viewElement.getAttribute('class');
        }
      },
    });

    // demoLinkText. View -> Model.
    conversion.for('upcast').elementToElement({
      view: {
        name: 'span',
        classes: 'text',
      },
      model: ( viewElement, { writer } ) => {
        return writer.createElement('demoLinkText');
      }
    });

    // demoLinkText. Model -> View.
    conversion.for('downcast').elementToElement({
      model: 'demoLinkText',
      view: ( modelElement, { writer: viewWriter } ) => {
        return viewWriter.createContainerElement('span', {class: 'text'});
      }
    });
  }

  }
