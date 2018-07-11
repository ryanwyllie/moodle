"use strict";

var execSync = require('child_process').execSync;
var jsFileModules = null;

function loadJSFileModules() {
    var script = 'php ' + process.cwd() + '/findallamdmodules.php';
    var output = execSync(script);
    jsFileModules = JSON.parse(output);
}

function getModuleNameFromFileName(searchFileName) {
    for (var candidateFileName in jsFileModules) {
        if (candidateFileName.indexOf(searchFileName) >= 0) {
            return jsFileModules[candidateFileName];
        }
    }

    throw new Error('Unable to find module name for ' + searchFileName);
}

Object.defineProperty(exports, "__esModule", {
  value: true
});

exports.default = function (_ref) {
    var t = _ref.types;

    return {
        pre: function pre() {
            this.seenDefine = false;

            if (jsFileModules === null) {
                loadJSFileModules();
            }
        },
        visitor: {
            // Plugin ordering is only respected if we visit the "Program" node.
            // See: https://babeljs.io/docs/en/plugins.html#plugin-preset-ordering
            //
            // We require this to run after the other AMD module transformation so
            // let's visit the "Program" node.
            Program: {
                exit: function exit(path) {
                    path.traverse({
                        CallExpression: function CallExpression(path) {
                            if (!this.seenDefine && path.get('callee').isIdentifier({ name: 'define' })) {
                                // We only want to modify the first instance of define that we find.
                                this.seenDefine = true;
                                var moduleName = getModuleNameFromFileName(this.file.opts.filename);
                                // Add the module name as the first argument to the define function.
                                path.node.arguments.unshift(t.stringLiteral(moduleName));
                            }
                        }
                    }, this);
                }
            }
        }
    }
};

module.exports = exports["default"];