Description of the import of libraries associated with the Atto editor.

1)  Rangy (version 1.3.0)
    * Download the latest stable release;
    * Delete all files in yui/src/rangy/js
    * Copy the content of the 'currentrelease/uncompressed' folder into yui/src/rangy/js
    * Run shifter against yui/src/rangy

    Notes:
    * We have patched 1.3.0 with a fix which addresses an incompatibility between Rangy and HTML5Shiv
      which is used in the bootstrapclean theme. See MDL-44798 for further information.
