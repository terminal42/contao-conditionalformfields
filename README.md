contao-conditionalformfields
============================

Allows you to display a form field based on a condition which allows you to do something like "only display the field
when value of field 'foo' is 'bar' and 'bla' is 'yes'".

```
$foo == 'bar' && $bla == 'yes'
```

You can also check the array (e.g. multiple checkboxes or select menu):

```
in_array('bar', $foo)
```

You can also check if a field contains a string (e.g. if hidden field with multiple values need to be checked):

```
str_contains('bar', $foo)
```

To validate a single checkbox simply compare its value:

```
$foo == '1'
```
Make sure jQuery is loaded for this to work. 
