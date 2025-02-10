# Automatic Translation

## [1.7.0] - 10/02/2025
### Fixed
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
