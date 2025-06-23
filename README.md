# EDW document

Provides a Drupal Document content type to store organisational multilingual 
structured PDF/Word etc. documents

## Prerequisites

Before enabling this module, make sure that the following modules are present in your codebase by adding them to your composer.json and by running composer update:
In `composer.json`:

```php
"require": {
  "drupal/core": "^9.4 || ^10 || ^11",
  "drupal/better_exposed_filters": "^6.0",
  "drupal/entity_browser": "^2.9",
  "drupal/search_api_solr":"^4.3",
  "drupal/views_bulk_operations": "^4.2"
}
```
The `entity_reference_revisions` module requires the following patch to be applied:

```php
"patches": {
    "drupal/entity_reference_revisions": {
      "#2799479 - Views doesn't recognize relationship to host": "https://www.drupal.org/files/issues/2022-06-01/entity_reference_revisions-relationship_host_id-2799479-176.patch"
    }
}
```

and for core:^10 || ^11:

```php
"patches": {
    "drupal/core": {
      "#2429699 - Add Views EntityReference filter to be available for all entity reference fields":"https://git.drupalcode.org/project/drupal/-/merge_requests/3086.patch"
    }
}
```
for core:^9.4
```php
"patches": {
    "drupal/core": {
      "#2457999 - Cannot use relationship for rendered entity on Views": "https://www.drupal.org/files/issues/2023-01-04/2457999-9.5.x-309.patch",
      "#2429699 - Add Views EntityReference filter to be available for all entity reference fields":"https://git.drupalcode.org/project/drupal/-/merge_requests/3086.patch"
    }
}
```

For a better experience install `file_replace` and apply the patch:

```php
"drupal/file_replace": {
  "#3300659 - Replace files directly from file widget": "https://www.drupal.org/files/issues/2024-02-04/3300659-31-file-replace-button--seven-themes.patch"
},
```
and enable the settings at `/admin/config/file_replace/settings`.

## Installation
1. Add the following snippet to the `repositories` section of your `composer.json` file:
```
{
    "type": "git",
    "url": "https://github.com/eaudeweb/edw_document.git"
},
{
     "type": "git",
     "url": "https://github.com/eaudeweb/edw_utilities.git"
}
```

2. Run
   ```composer require eaudeweb/edw_document:^1.0```

3. Enable the module:
   ``drush en edw_document``

## Basic Configuration

- Field type
  - **File with Language** - File with description and language
- Field widget
  - File with language - default widget for the `File with Language` field type.
  - Multi Language file - display all files grouped by language.
- Field Formatter:
  - File with Language - File formatter for `file_language` field type. Extends 
generic File formatter with the 
possibility to display the language selected from a dropdown with languages. If 
`use_description_as_link_text` setting is true, then show description if 
language is not selected. If both are empty then display the filename. Use
`suppress_language` to suppress the language with description.
  - Dropdown File with Language - overrides the default File with Language 
formatter and display only files with language as a dropdown (using 
`dropdown_file_language` theme).
  - Files group by Language - Group files in tabs using available languages.
- Facet Processor **List item Language** - Display the language name instead 
of langcode.

## Other EDW modules:
* [edw_blocks](https://github.com/eaudeweb/edw_blocks)
* [edw_decoupled](https://github.com/eaudeweb/edw_decoupled)
* [edw_demo_data](https://github.com/eaudeweb/edw_demo_data)
* [edw_document](https://github.com/eaudeweb/edw_document)
* [edw_event](https://github.com/eaudeweb/edw_event)
* [edw_group](https://github.com/eaudeweb/edw_group)
* [edw_media](https://github.com/eaudeweb/edw_media)
* [edw_paragraphs](https://github.com/eaudeweb/edw_paragraphs)
* [edw_person](https://github.com/eaudeweb/edw_person)
* [edw_project](https://github.com/eaudeweb/edw_project)
* [edw_themes](https://github.com/eaudeweb/edw_themes)
* [edw_utilities](https://github.com/eaudeweb/edw_utilities)