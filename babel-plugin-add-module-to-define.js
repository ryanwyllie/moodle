// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a babel plugin to add the Moodle module names to the AMD modules
 * as part of the transpiling process.
 *
 * @copyright  2018 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

var execSync = require('child_process').execSync;
// Static variable to hold the modules.
var jsFileModules = null;

/**
 * Execute a PHP script to get all of the AMD scripts and their corresponding
 * Moodle components.
 * 
 * The script returns a JSON formatted object of AMD files (keys) and Moodle
 * component (values).
 * 
 * The values are stored in the jsFileModules static variable because we
 * only need to load them once per transpiling run.
 */
function loadJSFileModules() {
    var script = 'php ' + process.cwd() + '/findallamdmodules.php';
    var output = execSync(script);
    jsFileModules = JSON.parse(output);
}

/**
 * Search the list of AMD files for the given file name and return
 * the Moodle component for that file, if found.
 * 
 * Throw an exception if no matching file is found.
 * 
 * @throws {Error}
 * @param {string} searchFileName The file name to look for.
 * @return {string} Moodle component
 */
function getModuleNameFromFileName(searchFileName) {
    for (var candidateFileName in jsFileModules) {
        if (candidateFileName.indexOf(searchFileName) >= 0) {
            // If we've found the file then return the Moodle component.
            return jsFileModules[candidateFileName];
        }
    }

    // This matches the previous PHP behaviour that would throw an exception
    // if it couldn't parse an AMD file.
    throw new Error('Unable to find module name for ' + searchFileName);
}

Object.defineProperty(exports, "__esModule", {
  value: true
});

exports.default = function(_ref) {
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
                            // If we find a "define" function call.
                            if (!this.seenDefine && path.get('callee').isIdentifier({name: 'define'})) {
                                // We only want to modify the first instance of define that we find.
                                this.seenDefine = true;
                                // Get the Moodle component for the file being processed.
                                var moduleName = getModuleNameFromFileName(this.file.opts.filename);
                                // Add the module name as the first argument to the define function.
                                path.node.arguments.unshift(t.stringLiteral(moduleName));
                            }
                        }
                    }, this);
                }
            }
        }
    };
};

module.exports = exports.default;