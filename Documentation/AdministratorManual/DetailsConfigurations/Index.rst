.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

.. _detailsconfigurations:

Details on configuration evaluation
===================================

The three basic config fields *securedDirs*, *securedFiletypes*, *domain* allow regular expressions.

.. hint::
	Some characters (slash, backslash, dot, blank) are automatically quoted for your convenience.

For filetype (meaning actually the file extension), all upper/lowercase combinations are automatically included (e.g. “gif” would also cover “giF”.)

The pipe (|) stands for “OR”.

Regex examples for securedDirs:

If for example you need to secure fileadmin and typo3temp, but not uploads: ::

	fileadmin|typo3temp

To secure everything under fileadmin/secure or typo3temp, you need to write ::

	fileadmin/secure|typo3temp

You also can group some elements with regular expression, but you should be carefull with grouping because the complex regex in the extension does not work if some other matches were output by the regex.

For grouping  :code:`( )` is used, but in our case you need to exclude the result of this :code:`( )`. This can be done with :code:`(?: )`

For example we need to find all under fileadmin/secure1 fileadmin/secure2 fileadmin/secure3 or typo3temp ::

	fileadmin/secure(?:1|2|3)|typo3temp

for the same need you can use also :code:`[ ]` but all chars between :code:`[ ]` are allowed here ::

	fileadmin/secure[123]|typo3temp

If you need to exclude some subfolders in a secured directory you can do this by :code:`(?! )` For example like ::

	(?!fileadmin/unsecured)fileadmin

.. hint::

	More information can be found here: http://www.regular-expressions.info