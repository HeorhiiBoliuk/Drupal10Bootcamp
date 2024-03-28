/**
 * @file
 * Defines a helper class and functions.
 */

/**
 * Finds a closest element of a model name in a given selection.
 */
export function findElement(modelSelection, modelName) {
  const selectedElement = modelSelection.getSelectedElement();
  if (selectedElement && selectedElement.name == modelName) {
    return selectedElement;
  } else {
    return modelSelection
      .getFirstRange()
      .getCommonAncestor()
      .findAncestor(modelName);
  }
}
