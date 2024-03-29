import {
  ButtonView,
  // Model,
  LabeledFieldView,
  View,
  createLabeledInputText,
  submitHandler,
} from "ckeditor5/src/ui";
import { icons } from "ckeditor5/src/core";

/**
 * The FormView class.
 */
export default class FormView extends View {

  /**
   * @inheritDoc
   */
  constructor( locale ) {
    super( locale );

    // Text inputs.
    this.textInputView = this._createInput('Text', { required: true });
    this.backgroundColor = this._createInput('Background', { type: 'color' });
    this.textInputColor = this._createInput('Text Color', { type: 'color' });
    this.urlInputView = this._createInput('URL', {required: true});

    this.saveButtonView = this._createButton(
      'Save', icons.check, 'ck-button-save'
    );

    this.saveButtonView.type = 'submit';

    this.cancelButtonView = this._createButton(
      'Cancel', icons.cancel, 'ck-button-cancel'
    );

    this.cancelButtonView.delegate( 'execute' ).to( this, 'cancel' );

    this.childViewsCollection = this.createCollection([
      this.textInputView,
      this.backgroundColor,
      this.textInputColor,
      this.urlInputView,
      this.saveButtonView,
      this.cancelButtonView
    ]);

    this.setTemplate( {
      tag: 'form',
      attributes: {
        class: [ 'ck', 'ck-demo-link-form' ],

        tabindex: '-1'
      },
      children: this.childViewsCollection
    } );

  }

  /**
   * @inheritDoc
   */
  render() {
    super.render();

    submitHandler( {
      view: this
    } );
  }

  /**
   * Focus on the first form element.
   */
  focus() {
    this.childViewsCollection.first.focus();
  }

  /**
   * Creates an input field.
   */
  _createInput(label, options = {}) {

    const labeledFieldView = new LabeledFieldView(this.locale, createLabeledInputText);
    labeledFieldView.label = label;

    if (options.required && options.required === true) {
      labeledFieldView.fieldView.extendTemplate({
        attributes: {
          required: true,
        }
      });
    }

    return labeledFieldView;
  }

  /**
   * Creates button.
   */
  _createButton( label, icon, className ) {
    const button = new ButtonView();

    button.set({
      label,
      icon,
      tooltip: true,
      class: className
    });

    return button;
  }

}
