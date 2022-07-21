<?php

require_once './src/MessageBuilder.php';

$client = new \Pasya\OneSender\MessageBuilder([
    'api_url' => 'http://yourinstance.com/api/v1/messages',
    'api_key' => 'your_api_keys',
]);

// Kirim pesan text
$client->to('62812000000001')
    ->content('isi pesan')
    ->send();

// Kirim pesan gambar
$client->to('62812000000001')
    ->type('image')
    ->attachmentUrl('https://englishonline.britishcouncil.org/wp-content/uploads/2021/11/image2-drake-posting-meme.jpg')
    ->content('isi caption gambar')
    ->send();
