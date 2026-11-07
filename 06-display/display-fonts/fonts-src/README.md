# fonts-src

Source fonts that phpbm bakes into the firmware at build time (`extra.baremetal.fonts.bake` in `composer.json`).

| File | What it is |
| --- | --- |
| `DejaVuSans.ttf` | DejaVu Sans 2.37, the source of the `title` font (28 px, 2 bits per pixel). |
| `LICENSE-DejaVu.txt` | The Bitstream Vera and DejaVu license that covers it. |

Any TTF (TrueType outlines) or BDF file can replace `DejaVuSans.ttf`: change `source` in the bake entry, and
`size` for a TTF. An OpenType font with CFF outlines must be converted to a TTF first.

The entry's `chars` lists exactly the characters the script draws with `title`; a character that is not in
`chars` is not in the font.
