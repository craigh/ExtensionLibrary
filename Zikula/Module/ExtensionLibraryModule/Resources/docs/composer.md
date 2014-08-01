Zikula Extension Composer Specification
=======================================
(With sincere thanks and apologies to the jQuery crew, from whom this concept was basically reverse-engineered)

This document is all you need to know about what's required in your `composer.json` file(s).

[View the sample][sample-composer] `composer.json` file

The `composer.json` file is required by the [core specification](https://github.com/zikula/core/blob/1.3/UPGRADE-1.3.7.md#module-composerjson).
The `composer.json` file typically lives at the 'namespace' level of the extension. The files must be actual JSON, not
just a JavaScript object literal.


Fields
======

**ALL fields are required for core 1.4-only modules. Modules compatible with core 1.3 should omit `autoload` and `extra` .**

 - [name](#name)
 - [description](#description) *
 - [type](#type) *
 - [license](#license) *
 - [authors](#authors) *
 - [require](#require)
 - [autoload](#autoload) **
 - [extra](#extra) **

Additional optional fields are allowed but not validated. Please see the entire spec at [the composer website](https://getcomposer.org/doc/04-schema.md#properties).

*utilized by ExtensionLibrary

**required for core 1.4-only modules

<a name="name"></a>Name
------

Package name, including 'vendor-name/' prefix.

<a name="description"></a>Description*
------

A short (one sentence) package description.

<a name="type"></a>Type*
------

Must be one of the following strings: "zikula-module", "zikula-theme", "zikula-plugin".

<a name="license"></a>License*
------

License name (string) or an array of license names (array of strings) under which the extension is provided. You must
use the standardized identifier acronym for the license as defined by [Software Package Data Exchange](http://spdx.org/licenses/)

<a name="authors"></a>Authors*
------

An array of people that have contributed to the project in some way. The array should have at least one person listed
and that person's **name** is required. (The role of "owner" is suggested.) See [People Fields](#people-fields)

<a name="require"></a>Require
------

This can be used to require vendor projects in your extension. It must contain at least a requirement for `"php": ">5.3.3"`

<a name="autoload"></a>Autoload**
------

A description of how the package can be autoloaded. The object should have only **one** property: either `psr-0` or `psr-4`.
The property must be an object and contain a hash of namespaces (keys) and the PSR-0 (or PSR-4) directories they can map
to (values, can be arrays of paths) by the autoloader.

<a name="extra"></a>Extra**
------

**You MUST omit this field if your module is compatible with core-1.3.** Must contain one property named `zikula`. This property must be an object with one property named `class`. The value for
this property must be a string value of the escaped classname for the extension class.


Additional Details
==================

<a name="people-fields"></a>People Fields
-------------
A "person" is an object with a "name" field and optional "homepage", "email" and "role" fields:

<pre>
{
    "name": "Susan Miller",
    "email": "smiller@acme.com",
    "homepage": "http://www.acme.com/smiller",
    "role": "owner"
}
</pre>

note: Suggested roles are `owner`, `contributor`, `translator`, `manager`, etc.


*NOTICE: the urls in this document are generated in the controller and are therefore not available if reading offline.*
