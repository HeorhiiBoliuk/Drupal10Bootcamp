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
    const demoLinkEl = findElement(selection, 'demoLink');
    if (!demoLinkEl) {
      return;
    }

    this.value = {};

    for (const [attrKey, attrValue] of demoLinkEl.getAttributes()) {
      this.value[attrKey] = attrValue;
    }

    for (const childNode of demoLinkEl.getChildren()) {
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

      var demoLinkEl = findElement(model.document.selection, 'demoLink');

      if (!demoLinkEl) {
        demoLinkEl = writer.createElement('demoLink');
        isNew = true;
      }

      this._editElement(writer, demoLinkEl, values);

      if (isNew) {
        model.insertContent(demoLinkEl);
      }

    });
  }

  /**
   * Create an element using the new values.
   */
  _editElement(writer, modelEl, values) {
    writer.clearAttributes(modelEl);

    var modelAttrs = {};
    modelAttrs.demoLinkUrl = values['demoLinkUrl'];
    modelAttrs.demoTextColor = values['demoTextColor'];
    modelAttrs.demoBackgroundColor = values['demoBackgroundColor'];
    modelAttrs.demoLinkClass = 'demo-link';
    writer.setAttributes(modelAttrs, modelEl);

    const children = [];
    Array.from(modelEl.getChildren()).forEach((el) => {
      children.push(el.name);
    });

    const demoLinkText = this._processChildTextEl(writer, values, children, modelEl, 'demoLinkText');

    if (demoLinkText) {
      writer.append(demoLinkText, modelEl);
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
