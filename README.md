# OneSender PHP Library

Library ini digunakan untuk mengirim pesan WhatsApp menggunakan [API OneSender](https://onesender.net)

## Single message

```php

$client = new \Pasya\OneSender\MessageBuilder([
    'api_url' => 'http://yourinstance.com/api/v1/messages',
    'api_key' => 'your_api_keys',
]);

```

**Send to number**
```php
$client->to('62812000000001')
    ->content('Testing')
    ->send();
```

**Send to group**
```php
$client->to('123456789321456@g.us')
    ->content('Testing')
    ->send();
```


## Send bulk message

Mengirim beberapa pesan dengan 1 kali request.

```php

$client = new \Pasya\OneSender\MessageBuilder([
    'api_url' => 'http://yourinstance.com/api/v1/messages',
    'api_key' => 'your_api_keys',
]);

$text = $client->to('62812000000001')
    ->content('Testing')
    ->save();

$image = $client->to('62812000000001')
    ->type('image')
    ->attachmentUrl('https://englishonline.britishcouncil.org/wp-content/uploads/2021/11/image2-drake-posting-meme.jpg')
    ->content('Testing kirim gambar')
    ->save();

$templateDevText = $client->to('62812000000001')
    ->type('interactive_dev')
    ->header('Informasi')
    ->content('Testing kirim gambar dengan heading text')
    ->footer('dikirim dengan onesender')
    ->addButtonLink('Go to website', 'https://www.google.com')
    ->addButtonCall('Telepon Admin', '+6281200000001')
    ->save();

$templateDevImage = $client->to('62812000000001')
    ->type('interactive_dev')
    ->header('https://englishonline.britishcouncil.org/wp-content/uploads/2021/11/image2-drake-posting-meme.jpg')
    ->content('Testing kirim gambar dengan link')
    ->footer('dikirim dengan onesender')
    ->addButtonLink('Go to website', 'https://www.google.com')
    ->addButtonCall('Telepon Admin', '+6281200000001')
    ->save();

$messages = [
    $text,
    $templateDevImage,
    $templateDevText,
    $image,
];



list($delivery, $errors) = $client->send($messages);
var_dump($delivery);
var_dump($errors);
```
