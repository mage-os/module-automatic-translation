# MageOS Automatic Translation Module for Magento

## Overview

The **MageOS Automatic Translation** module allows you to automatically translate content in your Magento store, such as products, categories, pages, and static blocks, using AI-based translation engines. The module is natively integrated with DeepL, OpenAI and Google Gemini, but it can be easily extended to support other translation engines.

## Installation

1. Install the module via Composer:
   ```bash
   composer require mage-os/module-automatic-translation
   ```

2. Enable the module:
   ```bash
   bin/magento module:enable MageOS_AutomaticTranslation
   ```

3. Run the setup upgrade command:
   ```bash
   bin/magento setup:upgrade
   ```

## Features

### Product Translation
The product translation process is divided into two parts:
1. **Textual attributes** – The translated value is directly set in the product entity.
2. **Select and Multiselect attributes** – The translation of these attributes involves translating the labels of the options, not the option IDs, as option IDs remain consistent across languages.

The module automatically translates products via scheduled cron jobs for both textual and select/multiselect attributes. You can also manually translate products through a backoffice button available in the product editing page.

**Cron Jobs for Product Translation**:
- The product translation process runs according to a cron schedule, which can be configured via the `Product translation cron expression` in the configuration.
- For select/multiselect attribute options, another cron job is scheduled, configurable via the `Select attributes translation cron expression`.

### Category Translation
Categories are translated only manually via a backoffice button. This is because the number of categories is usually much smaller than the number of products, and thus the manual translation process is more efficient.

For categories, only the following fields are translated:
- Name
- Description
- URL Key
- Meta information

**Note**: If you add custom attributes to categories programmatically, it is the developer’s responsibility to decide whether to translate these attributes and add them to the translation list programmatically.

### Pages and Static Blocks Translation
Similar to categories, pages and static blocks can only be translated manually via a backoffice button. However, because Magento does not support multiple language versions for pages and static blocks, translation will overwrite the original content when saving. To prevent losing the original content, it is recommended to use the "Save & Duplicate" feature in the backoffice to create a copy of the page or block before translating it.

### Retranslation of Products
When the module translates a product for a storeview, it values two attributes specific to that product and that storeview: "skip translation" set to "yes" and "last translation date" with the date of the translation. These attributes are automatically created by the module during installation and are updated each time the product is translated.

This process is used to "flag" the product as "already translated" preventing it from being translated again in future executions, thus improving performance.

However, if the merchant changes the basic content of the product after translation, it may need to be retranslated. The merchant can do this manually by using the button in the backoffice or by setting the "skip translation" attribute to "no" and saving the product. These operations are feasible if there are few products to be edited.

If, however, there are many products to be retranslated or the underlying content changes frequently, it may be useful to enable automatic retranslation. When enabled, this feature also includes products with the "skip translation" attribute set to "yes" in the translation process if the date in the "last translation date" attribute is older than a specified number of days, which can be configured in the settings.

### Translation Engines
- **DeepL**, **OpenAI** and **Google Gemini** are the supported engines by default. You can easily extend the module to support additional translation engines by creating a class that implements the `MageOS\AutomaticTranslation\Api\TranslatorInterface`.
- The engine is selected under **Stores > Configuration > MageOS > Automatic translation with AI > Translation engine**.

### Configuration Options

The module provides several configuration options under **Stores > Configuration > MageOS > Automatic translation with AI**:

#### General Configuration
- **Enable**: Enables or disables the module. This setting is configurable per store view, allowing you to translate only certain languages.
- **Source Language**: Defines the source language of your content. This is typically set to the language in which your products were initially created.
- **Destination Language**: This is the target language for translation, which corresponds to the store view's language.

#### Catalog Translation Options
- **Product Text Attributes to Translate**: Select the textual attributes you want to translate (e.g., name, description).
- **Product Select/Multiselect Attributes to Translate**: Select which select/multiselect attributes to translate.
- **Translate Disabled Products**: Skips disabled products during translation to improve performance.
- **Product Translation Cron Expression**: Schedules the product translation process.
- **Select Attributes Translation Cron Expression**: Schedules the translation of select/multiselect attributes.
- **Enable Periodic Retranslation & Retranslation Period (in days)**: Enables automatic retranslation for products if their translation is outdated (older than a set number of days).

#### Translation Engine Configuration
- **Engine**: Choose the translation engine (DeepL or OpenAI or Google Gemini).
- **API Credentials**: Configure API credentials for the selected engine.
- **Model Language** (for OpenAI & Google Gemini): This field is dynamic and will be populated once valid API credentials are entered.

### Adding Additional Translation Engines
To add a new translation engine, you need to:
1. Create a class implementing `MageOS\AutomaticTranslation\Api\TranslatorInterface`.
2. Extend the module to add the new engine's API configuration in `system.xml`.
3. Add an after plugin to modify the list of selectable engines in `\MageOS\AutomaticTranslation\Model\Config\Source\TranslationEngineList::toOptionArray`.
