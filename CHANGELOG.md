# Automatic Translation
## [2.1.0] - 20/03/2026
### Changed
- Minimum PHP version lowered from 8.3 to 8.2
- Removed typed constants (PHP 8.3 feature) for 8.2 compatibility

## [2.0.2] - 20/03/2026
### Fixed
- Fixed CMS page plain text fields (title, heading, meta title, meta keywords, meta description) not being translated
- Added URL key (identifier) to CMS page translatable fields
- Fixed URL key slug sanitization using correct field name (identifier instead of url_key)
- Fixed TranslateParsedContent for string input: translates text before handling widget directives

## [2.0.1] - 20/03/2026
### Fixed
- Fixed product translate button passing null instead of 0 to isEnable() for default store

## [2.0.0] - 12/03/2026
### Changed
- **BREAKING**: Minimum PHP version raised to 8.3
- Constructor promotion across all classes
- Typed constants (const string, const array, const int) across all classes
- Modern PHP 8.0+ functions: str_contains, str_starts_with, str_ends_with replacing legacy equivalents
- Non-capturing catch blocks where exception variable is unused
- Explicit return types on all methods
- Union types and mixed type hints where appropriate
- First-class callable syntax replacing array-style callables
- Replaced empty() checks on typed nullable properties with explicit null comparisons
- Changed private methods/properties to protected for Magento interceptor compatibility
- Replaced inline FQCNs with use imports for exceptions
- General code cleanup and refactoring

## [1.11.2] - 12/03/2026
### Fixed
- Fixed widget content_settings translation using JSON decode/encode instead of unreliable regex on raw encoded string
- Added translation of repeatable items (title, content, button, image_alt) inside content_settings
- Added translation of widget preview HTML inside content_settings for Page Builder editor consistency

## [1.11.1] - 12/03/2026
### Fixed
- Fixed encodePageBuilderHtmlBox corrupting text/heading content types (double HTML-encoding, style block encoding, spurious newlines)
- Expanded parsePageBuilderHtmlBox XPath to extract text from text and heading content types, not just html
- Fixed is_string branch sending entire structural HTML to translator — now only translates widget parameters
- Fixed log message not showing exception details (sprintf instead of __())

## [1.11.0] - 11/03/2026
### Fixed
- Fixed chunking fallback for segments without block-level HTML tags (hard split on sentence/word boundaries)

### Added
- Widget directives are now excluded from translation payload and their translatable parameters are translated individually

## [1.10.1] - 10/03/2026
### Fixed
- Fixed translation failing on long texts by adding chunking plugin for translation API limits
- Fixed double-encoding of HTML entities in PageBuilder HTML Code blocks
- Fixed greedy string replacement corrupting content when identical text appeared multiple times
- Fixed DOMDocument corrupting Widget content_settings JSON attributes during translation

## [1.10.0] - 05/03/2026
### Changed
- Retry DeepL translation with 2-char target language only on deprecated target language error
- Updated popup behaviour
- Extracted parsed content translation to service, reducing code duplication
- Removed unnecessary dependency on PageBuilder module
- Loosened dependency constraints in composer.json

## [1.9.1] - 14/10/2025
### Fixed
- Fixed product and category translation buttons appearing when translation is disabled

## [1.9.0] - 10/10/2025
### Fixed
- Fixed php 8.4 compatibility by @dadolun95 in #34
- Fixed code standards issues by @rhoerr in #38 and @SamueleMartini in #30 and #35
- Changed date formatting by @SamueleMartini in #32
- Feature/config modifications by @SamueleMartini in #33
- Code refactoring by @dadolun95 in #36
- Improved README.md by @SamueleMartini in #37

## [1.8.1] - 19/02/2025
### Fixed
- Changed product and category buttons to 'secondary' actions and fixed button visualization on category adminhtml form page

## [1.8.0] - 19/02/2025
### Fixed
- Fixed configurable products translation made from adminhtml

## [1.7.1] - 11/02/2025
### Fixed
- Fixed url rewrite generation after url key translation in translation via cron job

## [1.7.0] - 10/02/2025
### Added
- Updated minimum version of the OpenAI SDK to accommodate the new format of the APIs response
- Translated product attributes only if they are not empty, to save calls and avoid incorrect engine responses
- Improved prompt for translation with OpenAI 
- Skipped translation of empty attributes when translating from admin panel

## [1.6.0] - 16/12/2024
### Fixed
- Added url rewrite generation after url translation in automatic procedure

## [1.5.1] - 16/12/2024
### Fixed
- Restored translate button for store as primary
- Fix Google Gemini prompt

## [1.5.0] - 14/12/2024
### Added
- Product gallery images alt text translation

## [1.4.0] - 13/12/2024
### Added
- Translate button as secondary type in product and category edit form
- Uncheck instructions as an attribute note instead of in the label

## [1.3.0] - 30/11/2024
### Added
- CHANGELOG file
- Better filtering of attributes to translate

### Removed
- Mandatory of 'Product select/multiselect attributes to translate' in the system.xml
- Some variables and constants not used

## [1.2.1] - 25/11/2024
### Fixed
- Fixed product saving from admin panel, which did not remove “use default” checkmark automatically

## [1.2.0] - 22/11/2024
### Changed
- Updated readme with Google Gemini

## [1.1.3] - 19/11/2024
### Changed
- Adjusted composer.json

## [1.1.2] - 15/11/2024
### Changed
- Update to 1.1.2 version

## [1.1.1] - 15/11/2024
### Fixed
- Fix some instructions

## [1.1.0] - 15/11/2024
### Added
- Implemented use of project id in OpenAI integration

## [1.0.1] - 15/11/2024
### Fixed
- Fixed parsing of OpenAI response for model list

## [1.0.0] - 15/10/2024
### Added
- First module version
