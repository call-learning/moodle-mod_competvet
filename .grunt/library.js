/* eslint-env node */
/* jshint node: true */
/* jshint esversion: 6 */

const path = require('path');
const fs = require('fs');
const {existsSync} = fs;

/**
 * Find the Moodle root directory by looking for a specific file.
 *
 * @param {string} startDir
 * @param {string} fileName
 * @return {string}
 */
const findRoot = (startDir, fileName) => {
    while (!existsSync(path.join(startDir, fileName))) {
        const parent = path.dirname(startDir);
        if (parent === startDir) {
            break;
        }
        startDir = parent;
    }
    return startDir;
};

const getModulePath = (moodleRoot) => {
    return __dirname.replace(moodleRoot + path.sep, '').replace('/.grunt', '');
};

const getModuleName = (modulePath, moodleRoot) => {
    const ComponentsList = require(path.join(moodleRoot, '.grunt', 'components.js'));
    const currentDir = process.cwd();
    process.chdir(moodleRoot);
    try {
        return ComponentsList.getComponentFromPath(modulePath);
    } finally {
        process.chdir(currentDir);
    }
};

const buildSass = (grunt) => {
    const moodleRoot = findRoot(__dirname, 'config-dist.php');
    const modulePath = getModulePath(moodleRoot);
    const moduleName = getModuleName(modulePath, moodleRoot);
    const rootGruntfile = path.join(moodleRoot, 'Gruntfile.js');
    const stylesCss = path.join(moodleRoot, modulePath, 'styles.css');
    const stylesScss = path.join(moodleRoot, modulePath, 'scss', 'styles.scss');
    const scssDir = path.join(moodleRoot, modulePath, 'scss');

    if (grunt.file.exists(rootGruntfile)) {
        process.chdir(moodleRoot);
        require(rootGruntfile)(grunt);
    }

    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-stylelint');

    const sassConfig = {};
    sassConfig[moduleName] = {
        files: {
            [stylesCss]: stylesScss,
        },
        options: {
            implementation: require('sass'),
            includePaths: [
                scssDir,
            ],
            outputStyle: 'expanded',
        },
    };

    const stylelintConfig = {};
    stylelintConfig[moduleName] = {
        options: {
            quietDeprecationWarnings: true,
            cache: false,
            failOnError: true,
            fix: false,
        },
        src: [
            path.join(scssDir, '**/*.scss'),
        ],
    };

    grunt.config.merge({
        sass: sassConfig,
        stylelint: stylelintConfig,
    });

    const formatSelectors = filePath => {
        const css = fs.readFileSync(filePath, 'utf8');
        const formatted = css.replace(/(^|\n)([^\{\n]+)\{/g, (match, prefix, selectors) => {
            const trimmedSelectors = selectors.trim();
            if (!trimmedSelectors) {
                return match;
            }

            const indentMatch = selectors.match(/^(\s*)/);
            const indent = indentMatch ? indentMatch[1] : '';
            const parts = trimmedSelectors
                .split(',')
                .map(selector => selector.trim())
                .filter(Boolean);

            if (parts.length <= 1) {
                return match;
            }

            const formattedSelectors = parts
                .map(selector => `${indent}${selector}`)
                .join(',\n');
            const effectivePrefix = prefix || '\n';
            return `${effectivePrefix}${formattedSelectors} {`;
        });

        fs.writeFileSync(filePath, formatted);
    };

    const formatTaskName = `${moduleName}_formatSelectors`;
    grunt.registerTask(formatTaskName, function() {
        formatSelectors(stylesCss);
    });

    grunt.registerTask('rawscss', [`stylelint:${moduleName}`]);
    grunt.registerTask('scss', ['rawscss', `sass:${moduleName}`, formatTaskName]);
    grunt.registerTask('default', ['scss']);
};

module.exports = {
    buildSass,
};
