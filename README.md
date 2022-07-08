contao-conditionalformfields
============================

Allows you to display a form field based on a condition which allows you to do something like "only display the field
when value of field 'foo' is 'bar' and 'bla' is 'yes'".

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
When updating to version 3, the conditions are automatically adjusted.