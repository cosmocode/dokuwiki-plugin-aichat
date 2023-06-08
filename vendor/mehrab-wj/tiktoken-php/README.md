# TikToken clone for PHP - PHP GPT3 tokenizer

PHP Text Tokenizer for GPT models

## About

A PHP toolkit to tokenize text like GPT family of models process it.

Forked from [semji/gpt3-tokenizer-php](https://github.com/semji/gpt3-tokenizer-php) to bug fixes and improvement.

## Requirements
* PHP 8.1
* mbstring extension [details here on how to install mbstring](https://www.php.net/manual/en/mbstring.installation.php)
## Usage

```php
use TikToken\Encoder;
$prompt = "Ai is cool";
$encoder = new Encoder();

$tokens = $encoder->encode($prompt); // [32, 72, 318, 3608]

// Get tokens count:
echo count($tokens); // 4
```
