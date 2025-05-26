module.exports = {
    extends: [
        'plugin:@wordpress/eslint-plugin/recommended',
        'plugin:prettier/recommended',
        'plugin:@typescript-eslint/recommended',
    ],
    parser: '@typescript-eslint/parser',
    plugins: [
        '@typescript-eslint',
    ],
    parserOptions: {
        ecmaVersion: 2020,
        sourceType: 'module',
        ecmaFeatures: {
            jsx: true,
        },
        project: './tsconfig.json',
    },
    env: {
        browser: true,
        es6: true,
        node: true,
    },
    rules: {
        'prettier/prettier': [
            'error',
            {
                singleQuote: true,
                tabWidth: 4,
                useTabs: false,
                semi: true,
                trailingComma: 'es5',
            },
        ],
        // TypeScript specific rules
        '@typescript-eslint/explicit-function-return-type': 'off',
        '@typescript-eslint/no-explicit-any': 'warn',
        '@typescript-eslint/no-unused-vars': ['error', {
            argsIgnorePattern: '^_',
            varsIgnorePattern: '^_',
        }],
        "@wordpress/no-unsafe-wp-apis": "off",
        "@wordpress/i18n-text-domain": [
            "error",
            {
                "allowedTextDomain": ["lolly"]
            }
        ],
        "jsx-no-target-blank": "off",
        "no-unused-vars": [
            "error",
            {
                "argsIgnorePattern": "^_"
            }
        ],
        "import/order": [
            "error",
            {
                "alphabetize": {
                    "order": "asc",
                    "caseInsensitive": true
                },
                "newlines-between": "always",
                "groups": ["builtin", "external", "parent", "sibling", "index"],
                "pathGroups": [
                    {
                        "pattern": "react+(|-dom)",
                        "group": "builtin",
                        "position": "before"
                    },
                    {
                        "pattern": "@wordpress/**",
                        "group": "external"
                    }
                ],
                "pathGroupsExcludedImportTypes": [
                    "builtin",
                    "react",
                    "react-dom",
                ]
            }
        ]
    },
    "overrides": [
        {
            "files": "**/*.ts?(x)",
            "rules": {
                "no-unused-vars": "off"
            }
        }
    ],
    settings: {
        'import/resolver': {
            node: {
                extensions: ['.js', '.jsx', '.ts', '.tsx'],
            },
        },
    },
};
