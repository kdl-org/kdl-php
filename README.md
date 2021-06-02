[![Latest Stable Version](https://poser.pugx.org/kdl/kdl/v)](//packagist.org/packages/kdl/kdl) [![License](https://poser.pugx.org/kdl/kdl/license)](//packagist.org/packages/kdl/kdl) ![Status](https://github.com/kdl-org/kdl-php/workflows/Project%20checks/badge.svg?branch=main)

# KDL-PHP

A PHP library for the [KDL Document Language](https://kdl.dev) (pronounced like "cuddle").

![alt text](./kdl.svg "KDL logo")

## Installation

The KDL library can be included to your project using Composer:

`composer require kdl/kdl`

## Limitations

For now, this library only supports parsing.

Parsing a 45 line file with a tree depth of 5 is likely to take about 30ms currently - this speed is improving all the time with releases of the underlying parser library. This library favours correctness over performance - however, the aim is for parse speed to improve to a point of being reasonable.

The parser uses [Parsica](https://parsica.verraes.net/) as an underlying parsing library in order to map fairly directly and clearly onto the published KDL grammar - Parsica uses FP principles, and one result of this is that the call stack depth used during parsing may be high. Be warned if you are using e.g. xdebug, as parsing may exceed any normal maximum stack depth that you may set.

## Examples

```php
$document = Kdl\Kdl\Kdl::parse($kdlString);
foreach ($document as $node) {
    $node->getName(); //gets the name for the node @see https://github.com/kdl-org/kdl/blob/main/SPEC.md#node
    $node->getValues(); //gets a list of values for a node @see https://github.com/kdl-org/kdl/blob/main/SPEC.md#value
    $node->getProperties(); //gets an associative array of properties @see https://github.com/kdl-org/kdl/blob/main/SPEC.md#property
    foreach ($node->getChildren() as $child) {
        $child->getName(); //name for the child
       //etc
    }
}
```

## License

The code is available under the [MIT license](LICENSE).