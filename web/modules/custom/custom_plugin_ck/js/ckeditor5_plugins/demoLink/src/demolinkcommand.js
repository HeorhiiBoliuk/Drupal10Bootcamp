import { Command } from 'ckeditor5/src/core';
import {
  findElement,
  Utils,
} from "./utils";

/**
 * The demoLink command.
 */
export default class DemoLinkCommand extends Command {

  /**
   * @inheritDoc
   */
  refresh() {
    this.isEnabled = true;

    this.value = null;

    const selection = this.editor.model.document.selection;
    const Element = findElement(selection, 'demoLink');
    if (!Element) {
      return;
    }

    this.value = {};

    for (const [attrKey, attrValue] of Element.getAttributes()) {
      this.value[attrKey] = attrValue;
    }

    for (const childNode of Element.getChildren()) {
      const childTextNode = childNode.getChild(0);
      const dataNotEmpty = childTextNode && childTextNode._data;
      this.value[childNode.name] = dataNotEmpty ? childTextNode._data : '';
    }
  }


  /**
   * @inheritDoc
   */
  execute(values) {
    const { model } = this.editor;

    model.change((writer) => {

      var isNew = false;

      var Element = findElement(model.document.selection, 'demoLink');

      if (!Element) {
        Element = writer.createElement('demoLink');
        isNew = true;
      }

      this._editElement(writer, Element, values);

      if (isNew) {
        model.insertContent(Element);
      }

    });
  }

  /**
   * Create an element using the new values.
   */
  _editElement(writer, modelEl, values) {
    writer.clearAttributes(modelEl);

    var modelAttrs = {};
    modelAttrs.customLinkUrl = values['customUrl'];
    modelAttrs.customTextColor = values['customTextColor'];
    modelAttrs.customBackgroundColor = values['customBackgroundColor'];
    modelAttrs.customLinkClass = 'demo-link';
    writer.setAttributes(modelAttrs, modelEl);

    const children = [];
    Array.from(modelEl.getChildren()).forEach((el) => {
      children.push(el.name);
    });

    const customText = this._processChildTextEl(writer, values, children, modelEl, 'customText');

    if (customText) {
      writer.append(customText, modelEl);
    }

  }

  /**
   * Processes child text elements.
   */
  _processChildTextEl(writer, values, children, modelEl, childElName) {

    const childEl = this._processChildElement(
      writer,
      values[childElName],
      children,
      modelEl,
      childElName
    );

    if (childEl) {
      const textNode = childEl.getChild(0);
      if (textNode) {
        writer.remove(textNode);
      }

      writer.appendText( values[childElName], childEl );
      return childEl;
    }

    return null;
  }

  /**
   * Processes any child element.
   */
  _processChildElement (writer, value, children, modelEl, childElName) {

    const create = value && !children.includes(childElName);
    const edit = value && children.includes(childElName);
    const remove = !value && children.includes(childElName);

    var childEl = null;

    if (create) {
      childEl = writer.createElement(childElName);
    } else if (edit || remove) {
      let childrenUpdated = [];
      Array.from(modelEl.getChildren()).forEach((el) => {
        childrenUpdated.push(el.name);
      });

      var childElIndex = childrenUpdated.indexOf(childElName);
      childEl = modelEl.getChild(childElIndex);
    }

    if (children.includes(childElName) && childEl) {
      writer.remove(childEl);
    }

    if (remove) {
      return null;
    } else {
      return childEl;
    }

  }

}
