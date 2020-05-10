.. include:: ../../Includes.txt

.. _admin-regularExpressions:

===================
Regular Expressions
===================

The configuration values of :ref:`admin-extensionConfiguration-securedDirs`, :ref:`admin-extensionConfiguration-securedFileTypes`,
:ref:`admin-extensionConfiguration-domain`, :ref:`admin-extensionConfiguration-forcedownloadtype` and
:ref:`admin-extensionConfiguration-groupCheckDirs` allow regular expressions. All expressions will be handled case insensitive.

.. hint::
    The slash character :code:`(/)` is automatically quoted for your convenience.

.. _admin-regularExpressions-examples:

Examples
========

The following are a few examples of the :ref:`admin-configuration-securedDirs` configuration option:

For example, if you need to secure the `fileadmin` and the `typo3temp` directory, but not an uploads directory, you can simply
write::

   fileadmin|typo3temp

To secure files underneath of `fileadmin/secure` or `typo3temp`, you need to write::

   fileadmin/secure|typo3temp

You also can group some elements with regular expression, but you should be careful with grouping, because complex regular
expressions in the extension does not work if some other matches occur by the expression.

For grouping  :code:`( )` is used, but in our case you need to exclude the result of this :code:`( )`. This can be done with
:code:`(?: )`

For example, we want to secure files underneath `fileadmin/secure1`, `fileadmin/secure2`, `fileadmin/secure3` and typo3temp::

   fileadmin/secure(?:1|2|3)|typo3temp

You can achive the same result be using square brackets :code:`[ ]`, but keep in mind that all characters between the brackets are allowed here::

   fileadmin/secure[123]|typo3temp

If you need to exclude some subdirectories within a secured directory, you can use the Elvis Operator :code:`(?! )`::

   (?!fileadmin/unsecured)fileadmin

Last but not least, you can store all your protected files within different subdirectories having the same name. In this example
we use "secure" as directory name::

   fileadmin/(.*)/secure

This will secure files within `fileadmin/foo/secure` and also within `fileadmin/bar/secure`.

.. hint::

   More information can be found here: http://www.regular-expressions.info
