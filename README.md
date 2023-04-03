# Crawlman

A simple web crawler to collect data about musicians, albums, songs and lyrics.

## Requirements

- php >= 7.2.14
- [Composer](https://getcomposer.org/download/)

## How to code

This is just a sample code that shows how it works. For more information and to see the complete code, please check the `./examples/` directory where a working example code is located.

```php
$url = 'https://en.wikipedia.org/wiki/Michael_Jackson';
$crawler = $manager->getCrawler($url, 'ART');

$crawler->getArtistRealName(); // Michael Joseph Jackson
$crawler->getArtistBirthPlace(); // "Gary, Indiana, US"
$crawler->getArtistOccupations(); // Singer, songwriter, dancer, ...
```

## Manual

Command | Description
------- | -----------
`composer install` | Installs all required packages.
`composer example` | Runs the example code located in ./examples/ directory.
`composer test` | Runs all of the tests located in ./tests/ directory using PHPUnit.

## TODO

- Tests
- Implementing more crawlers.
- This is an old project. It seems the whole code needs a good review.

## License

The Chakavang/Crawlman is open-source library licensed under the [MIT license](https://opensource.org/licenses/MIT). For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
