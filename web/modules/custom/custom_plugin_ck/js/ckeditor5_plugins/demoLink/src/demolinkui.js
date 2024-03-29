import { Plugin } from 'ckeditor5/src/core';
import {
  ButtonView,
  ContextualBalloon,
  clickOutsideHandler
} from 'ckeditor5/src/ui';
import demoLinkIcon from '../../../icons/demo-link.svg';
import FormView from './ui/demolinkformview';
import {
  findElement,
  Utils
} from './utils';

/**
 * The UI plugin. It introduces the button and the forms.
 */
export default class DemoLinkUI extends Plugin {

  /**
   * @inheritDoc
   */
  static get requires() {
    return [ ContextualBalloon ];
  }

  /**
   * @inheritDoc
   */
  init() {
    this._balloon = this.editor.plugins.get( ContextualBalloon );

    this._addToolbarButton();
    this.formView = this._createFormView();
    this._handleSelection();
  }

  /**
   * Adds the demoLink toolbar button.
   *
   * @private
   */
  _addToolbarButton() {
    const editor = this.editor;

    editor.ui.componentFactory.add('demoLink', (locale) => {
      const buttonView = new ButtonView(locale);

      buttonView.set({
        label: editor.t('demoLink'),
        icon: demoLinkIcon,
        tooltip: true
      });

      const command = editor.commands.get('demoLink');
      buttonView.bind( 'isEnabled' ).to( command, 'isEnabled' );
      buttonView.bind( 'isOn' ).to( command, 'value', value => !!value );

      this.listenTo(buttonView, 'execute', () =>
        this._showUI(),
      );

      return buttonView;
    });
  }

  /**
   * Creates the form view.
   */
  _createFormView() {
    const formView = new FormView(this.editor.locale);

    this.listenTo(formView, 'submit', () => {

      let values = {
        demoLinkText: formView.textInputView.fieldView.element.value,
        demoLinkUrl: formView.urlInputView.fieldView.element.value,
        demoBackgroundColor: formView.backgroundColor.fieldView.element.value,
        demoTextColor: formView.textInputColor.fieldView.element.value,
      };

      this.editor.execute('demoLink', values);

      this._hideUI();
    });

    this.listenTo( formView, 'cancel', () => {
      this._hideUI();
    } );

    clickOutsideHandler( {
      emitter: formView,
      activator: () => this._balloon.visibleView === formView,
      contextElements: [ this._balloon.view.element ],
      callback: () => this._hideUI()
    } );

    return formView;
  }

  /**
   * Adds the formview to the balloon and sets the form values.
   */
  _addFormView() {

    this._balloon.add({
      view: this.formView,
      position: this._getBalloonPositionData()
    });

    const command = this.editor.commands.get('demoLink');

    const modelToFormFields = {
      demoLinkText: 'textInputView',
      demoLinkUrl: 'urlInputView',
      demoBackgroundColor: 'backgroundColor',
      demoTextColor: 'textInputColor',
    };

    Object.entries(modelToFormFields).forEach(([modelName, formElName]) => {

      const formEl = this.formView[formElName];

      formEl.focus();

      const editorConfig = this.editor.config;

      const backgroundColor = editorConfig._config.colors_config.background_color;

      const color = editorConfig._config.colors_config.color;

      const isEmpty = !command.value || !command.value[modelName] || command.value[modelName] === '';

      if (modelName === 'demoLinkUrl' && isEmpty) {
        formEl.fieldView.element.value = '#';
        formEl.set('isEmpty', false);
        return;
      }

      if (modelName === 'demoBackgroundColor' && isEmpty) {
        formEl.fieldView.element.value = backgroundColor;
        formEl.set('isEmpty', false);
        return;
      }

      if (modelName === 'demoTextColor' && isEmpty) {
        formEl.fieldView.element.value = color;
        formEl.set('isEmpty', false);
        return;
      }

      if (!isEmpty) {
        formEl.fieldView.element.value = command.value[modelName];
      }
      formEl.set('isEmpty', isEmpty);

    });

    this.formView.focus();
  }

  /**
   * Handles the selection.
   */
  _handleSelection() {
    const editor = this.editor;

    this.listenTo(editor.editing.view.document, 'selectionChange', (eventInfo, eventData) => {
      const selection = editor.model.document.selection;

      let el = selection.getSelectedElement() ?? selection.getFirstRange().getCommonAncestor();

      if (!['demoLinkText'].includes(el.name)) {
        this._hideUI();
        return;
      }

      this._showUI();

      const positionBefore = editor.model.createPositionBefore(el);
      const positionAfter = editor.model.createPositionAfter(el);

      const position = selection.getFirstPosition();

      const afterTouchChildElName = 'demoLinkText';

      const beforeTouch = el.name == 'demoLinkText' && position.isTouching( positionBefore );
      const afterTouch = el.name == afterTouchChildElName && position.isTouching( positionAfter );

      if (beforeTouch || afterTouch) {
        editor.model.change(writer => {
          writer.setSelection(el.findAncestor('demoLink'), 'on');
        });
      }

    });
  }

  /**
   * Shows the UI.
   */
  _showUI() {
    this._addFormView();
  }

  /**
   * Hide the UI.
   */
  _hideUI() {
    const formView = this.formView;

    if (formView.element) {
      formView.element.reset();
    }

    if (this._balloon.hasView(formView)) {
      this._balloon.remove(formView);
    }

    this.editor.editing.view.focus();
  }

  /**
   * Gets balloon position.
   */
  _getBalloonPositionData() {
    const view = this.editor.editing.view;
    const viewDocument = view.document;
    let target = null;

    target = () => view.domConverter.viewRangeToDom(
      viewDocument.selection.getFirstRange()
    );

    return {
      target
    };
  }

}
