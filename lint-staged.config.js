module.exports = {
	'**/*.php': (filenames) => {
		const files = filenames.join(' ');
		return [
			`php -d display_errors=1 -l ${files}`,
			`vendor/bin/phpcs --standard=phpcs.xml.dist -n ${files}`,
			`vendor/bin/phpstan analyse --memory-limit=4G --no-progress`,
		];
	},
	'**/*.{js,jsx,ts,tsx}': (filenames) => {
		const files = filenames.join(' ');
		return [`npx wp-scripts lint-js ${files}`];
	},
	'**/*.{css,scss}': (filenames) => {
		const files = filenames.join(' ');
		return [`npx wp-scripts lint-style ${files}`];
	},
	'**/*.{json,yml,yaml}': (filenames) => {
		const files = filenames.join(' ');
		return [`prettier --write ${files}`];
	},
};
