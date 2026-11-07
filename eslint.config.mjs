import js from '@eslint/js';
import globals from 'globals';

export default [
	{
		ignores: [
			'**/sortable.min.js',
			'node_modules/**'
		]
	},
	js.configs.recommended,
	{
		languageOptions: {
			ecmaVersion: 2015,
			sourceType: 'script',
			globals: {
				...globals.browser,
				...globals.jquery,
				SORTABLE_CATEGORIES_URL: 'readonly',
				Sortable: 'readonly'
			}
		},
		rules: {
			'no-unused-vars': ['error', { argsIgnorePattern: '^_' }]
		}
	}
];

