# Publishem

This is a plugin for Kirby CMS that provides functionality to publish child pages of a given page. It adds an entry to the page dropdown in the panel and a dialog to confirm the action.

## Installation

You can install this plugin by adding it to your `site/plugins` directory in your Kirby installation.

## Usage

The plugin provides a dropdown in the panel that allows you to publish child pages of a given page. The dropdown text, dialog text, and dialogs button text can be customized through the page's blueprint options.

```yml
options:
  publishem:
    # The status to set for the child pages. Can be 'listed' or 'unlisted'.
    status: 'unlisted'

    # The query to select the child pages to publish.
    query: 'page.drafts'

    # The text for the dialog that confirms the publishing action.
    dialog: 'Publish child pages'

    # The text for the dropdown that triggers the publishing action.
    dropdown: 'Publish content'

    # The text for the button that confirms the publishing action.
    button: 'Publish'
```

If the `publishem` option is set to a string, it will be used as the status. If it is set to `true`, the default settings (like above) will be used.

The plugin also provides default translations for English and Italian.

## License

This project is licensed under the MIT License.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## Contact

For any questions or concerns, please open an issue on GitHub.
