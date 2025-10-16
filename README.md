# Page Tree Select

An inputfield for selecting a single page from the page tree.

![Screenshot](https://github.com/user-attachments/assets/1fbdd703-15d0-4ead-a7ee-2501c90c9ccc)

This inputfield is similar to the core InputfieldPageListSelect, but it has the following advantages:

- It automatically expands the tree to the currently selected page. This avoids having to drill down through the tree when you want to change the selection to a sibling or child of the currently selected page. This was the primary motivation for creating the module.
- It's faster to navigate through because the whole tree is rendered at once rather than branch by branch.
- It provides a filter feature to locate pages by title anywhere in the tree. When the tree is filtered you can hover a page title to see the breadcrumb path to the page in a tooltip.
- It provides buttons to clear the current selection, to restore a changed selection, and to scroll to the selected page.

## Configuration

The following config options are available when using the module as an inputfield for a Page Reference field:

- Exclude admin pages: excludes pages from the tree that have the `admin` template.
- Exclude pages by template: pages having any of the templates you select here will be excluded from the tree. Descendants of any excluded pages are also excluded.
- Limit for total pages in the tree: this limit is applied to the selector that finds pages for the tree (default is 5000).

## Limitations and considerations

- Performance seems to be reasonable when the tree consists of up to 5000 pages. Your mileage may vary and the module may not be suitable for sites with a very large number of pages (unless excluding pages by template in the inputfield configuration).
- Pages in the tree show their titles rather than any custom setting defined for the template "List of fields to display in the admin Page List". 
- Page titles are only shown in the default language.
- The module does not reproduce some of the quirks/features of ProcessPageList such as excluding pages that are hidden and non-editable, and forcing the sort position of special pages like Admin and Trash.
- ProcessWire >= v3.0.248 is needed for the inputfield to appear as an option in Add Field due to [this now fixed core issue](https://github.com/processwire/processwire-issues/issues/2058).

## Replacing InputfieldPageListSelect in the ProcessWire admin

An autoload module named ReplacePageListSelect is bundled with InputfieldPageTreeSelect. Install the module if you would like to replace all instances of InputfieldPageListSelect in the ProcessWire admin with InputfieldPageTreeSelect.

For advanced use cases there are two hookable methods:

- `ReplacePageListSelect::allowReplacement($inputfield)`: set the event return to `false` to disable replacement on particular instances of `InputfieldPageListSelect`.
- `ReplacePageListSelect::getPageTreeSelect($inputfield)`: set `excludeAdminPages`, `excludeTemplates` and `limitTotalPages` properties on the event return `InputfieldPageTreeSelect` object when replacing particular instances of `InputfieldPageListSelect`.
