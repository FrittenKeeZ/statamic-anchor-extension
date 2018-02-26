# Extends the default Bard (MediumEditor) anchor functionality with internal links selection.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

## Installation

Download or clone the repository, then copy the folder `Anchor` to your site's `Addons` directory.

## Settings

Head to `/cp/addons/anchor/settings` and change what you feel like.

## Usage

Replace the internal links generated by the extension, by using the `anchor` modifier.
Passing `true` as argument will generate absolute URLs, otherwise relative are outputted.

```html
<div>{{ content | anchor:true }}</div>
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
