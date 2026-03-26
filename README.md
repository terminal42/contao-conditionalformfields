contao-conditionalformfields
============================

Allows you to display a form field based on a condition which allows you to do something like "only display the field
when value of field 'foo' is 'bar' and 'bla' is 'yes'".

The condition is not entered directly at the form field, but a field set with start and end must be created.
The condition can be entered in the start of field set. The field set can also be used to control several form
fields in the view.

```
foo == 'bar' && bla == 'yes'
```

You can also check the array (e.g. multiple checkboxes or select menu):

```
in_array('bar', foo)
```

To validate a single checkbox simply compare its value:

```
foo == '1'
```

### Note for Version 3

The field names had a prefix `$` until version 3 - this is no longer necessary.
When updating to version 3 or later, the conditions are automatically adjusted.

## Support for member fields

Version 4 adds support for conditions on member fields, e.g. for the member registration.
To use conditions, you have to follow a strict setup:
1. create new DCA fields for `tl_member` with `inputType` of `fieldsetStart` and `fieldsetStop` as necessary
2. set `eval => isConditionalFormField = true` and `eval => conditionalFormFieldCondition = 'your-condition'` on the `fieldsetStart`
3. make sure to also set `feEditable` and `feGroup` accordingly
4. select the new fields in the member registration module and **sort them so the appropriate fields are within the start and stop field**.

_Known limitation: mandatory fields will not show as mandatory (asterisk) after a form submit, if they were hidden during the form submit._
