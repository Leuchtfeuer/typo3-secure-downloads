[![Latest Stable Version](https://poser.pugx.org/leuchtfeuer/secure-downloads/v/stable)](https://packagist.org/packages/leuchtfeuer/secure-downloads)
[![Total Downloads](https://poser.pugx.org/leuchtfeuer/secure-downloads/downloads)](https://packagist.org/packages/leuchtfeuer/secure-downloads)
[![Latest Unstable Version](https://poser.pugx.org/leuchtfeuer/secure-downloads/v/unstable)](https://packagist.org/packages/leuchtfeuer/secure-downloads)
[![License](https://poser.pugx.org/leuchtfeuer/secure-downloads/license)](https://packagist.org/packages/leuchtfeuer/secure-downloads)

# TYPO3 Extension "Secure Downloads"

In TYPO3, assets like PDFs, TGZs or JPGs etc. are normally just 
referenced by a URL e.g. to `fileadmin/...`. The file itself is 
delivered directly by the web server, and is therefore not part of the 
TYPO3 access control scheme – files remain unprotected, since URLs can 
be re-used, emailed, Search engine included or even guessed.

The "Secure Downloads" extension (`EXT:secure_downloads`) changes this 
behavior: Files will now be accessed through a script that honors TYPO3 
access rights. The converted URL's will then look like this:

    /download/sdl-eyJ0eXAiOiJKV1QiLCJhbGciO[...]vcM5rWxIulg5tQ/protected_image.jpg

This works regardless of where the files come from and is not limited 
to special plugins, etc.

Since in most cases you will not want to protect everything (which 
means that everything undergoes rather performance-consuming access 
right checking), Secure Downloads is highly configurable. You may 
choose:

* what directories to protect (e.g. you can include typo3temp or not)
* what file types to protect (do you want to protect JPGs or not? etc.)

As a complementary measure, you will of course need to configure your 
web server not to deliver these things directly (e.g. using .htaccess 
settings).

## Documentation

A detailed documentation can be found in the 
[official TYPO3 documentation](https://docs.typo3.org/p/leuchtfeuer/secure-downloads/master/en-us/Index.html)
of this extension.

## Changelog

The changelog can be found in the changelog chapter of the
[official TYPO3 documentation](https://docs.typo3.org/p/leuchtfeuer/secure-downloads/master/en-us/Miscellaneous/ChangeLog/Index.html)
of this extension.
