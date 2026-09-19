import js from "@eslint/js";
import globals from "globals";

export default [
  js.configs.recommended,
  {
    files: ["public/assets/js/**/*.js", "scripts/**/*.mjs"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
      globals: {
        ...globals.browser,
        ...globals.node,
        ...globals.jquery,
        lucide: "readonly",
        HSOverlay: "readonly",
        HSTabs: "readonly",
        QRCode: "readonly",
        DataTable: "readonly",
        bootstrap: "readonly"
      }
    },
    rules: {
      "no-unused-vars": [
        "warn",
        {
          vars: "all",
          args: "after-used",
          ignoreRestSiblings: true,
          varsIgnorePattern: "^_"
        }
      ],
      "no-unreachable": "error",
      "no-undef": "warn"
    }
  },
  {
    files: ["ai_worker/**/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
      globals: {
        ...globals.node
      }
    },
    rules: {
      "no-unused-vars": [
        "warn",
        {
          vars: "all",
          args: "after-used",
          ignoreRestSiblings: true,
          varsIgnorePattern: "^_"
        }
      ],
      "no-unreachable": "error",
      "no-undef": "error"
    }
  },
  {
    ignores: [
      "**/node_modules/**",
      "**/vendor/**",
      "writable/**"
    ]
  }
];
