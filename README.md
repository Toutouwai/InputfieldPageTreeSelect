# Page Tree Select

An inputfield for selecting a single page from the page tree.

### Replacing InputfieldPageListSelect with InputfieldPageTreeSelect

You can use the following hook to dynamically replace InputfieldPageListSelect with InputfieldPageTreeSelect, as the inputfield is rendered.

```php
// Replace InputfieldPageListSelect with InputfieldPageTreeSelect
$wire->addHookBefore('InputfieldWrapper::renderInputfield', function(HookEvent $event) {
    $inputfield = $event->arguments(0);
    if($inputfield->className !== 'InputfieldPageListSelect') return;
    $ipts = $event->wire()->modules->get('InputfieldPageTreeSelect');
    foreach($inputfield->getArray() as $key => $value) {
        $ipts->set($key, $value);
    }
    $ipts->setAttributes($inputfield->getAttributes());
    $event->arguments(0, $ipts);
});
```
