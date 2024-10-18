const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        'index': './src/',
		'blocks/year-make-model-and-trim/index': './src/blocks/year-make-model-and-trim/',
    },
};
